<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class MCPTestRunner
{
    private array $config;
    private LoggerInterface $logger;
    private RemoteServiceManager $serviceManager;
    private array $testResults = [];
    private array $failedTests = [];
    private array $performanceMetrics = [];
    private array $securityFindings = [];
    private string $reportDir;

    public function __construct()
    {
        $this->config = require __DIR__ . '/config/remote-services.php';
        $this->setupLogger();
        $this->serviceManager = new RemoteServiceManager($this->logger);
        $this->reportDir = __DIR__ . '/../reports';
        $this->initializeReportDirectories();
    }

    private function setupLogger(): void
    {
        $logDir = $this->config['logging']['test_log_dir'];
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logger = new \MCP\Core\Logger\Logger(
            'mcp_test_runner',
            $logDir,
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    }

    private function initializeReportDirectories(): void
    {
        $directories = [
            $this->reportDir . '/test-results',
            $this->reportDir . '/coverage',
            $this->reportDir . '/issues',
            $this->reportDir . '/performance',
            $this->reportDir . '/security'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function run(array $options = []): void
    {
        try {
            $this->logger->info('Starting MCP system test run');
            $this->setupEnvironment();
            $this->initializeServices();
            $this->runTests($options);
            $this->generateReports();
        } catch (\Exception $e) {
            $this->logger->error('Test run failed: ' . $e->getMessage());
            $this->attemptRecovery();
        } finally {
            $this->cleanup();
        }
    }

    private function setupEnvironment(): void
    {
        $this->logger->info('Setting up test environment');
        
        // Create required directories
        foreach ($this->config['logging'] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->logger->info("Created directory: {$dir}");
            }
        }

        // Load environment variables
        $this->loadEnvironmentVariables();

        // Clean up old reports if configured
        if ($this->config['self_healing']['cleanup_old_reports']) {
            $this->cleanupOldReports();
        }
    }

    private function loadEnvironmentVariables(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    putenv("{$key}={$value}");
                    $this->logger->info("Loaded environment variable: {$key}");
                }
            }
        }
    }

    private function cleanupOldReports(): void
    {
        $retentionDays = $this->config['self_healing']['report_retention_days'];
        $cutoffDate = strtotime("-{$retentionDays} days");

        foreach (glob($this->reportDir . '/*/*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoffDate) {
                unlink($file);
                $this->logger->info("Removed old report file: {$file}");
            }
        }
    }

    private function initializeServices(): void
    {
        $this->logger->info('Initializing remote services');
        $maxRetries = $this->config['self_healing']['max_retries'];
        $retryDelay = $this->config['self_healing']['retry_delay'];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->serviceManager->initialize();
                if ($this->serviceManager->checkHealth()) {
                    $this->logger->info('All services initialized successfully');
                    return;
                }
            } catch (\Exception $e) {
                $this->logger->warning("Service initialization attempt {$attempt} failed: " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }

        throw new \RuntimeException('Failed to initialize services after ' . $maxRetries . ' attempts');
    }

    private function runTests(array $options): void
    {
        $this->logger->info('Running tests');
        $testFiles = $this->findTestFiles($options);

        foreach ($testFiles as $file) {
            try {
                $this->runTestFile($file);
            } catch (\Exception $e) {
                $this->logger->error("Test file {$file} failed: " . $e->getMessage());
                $this->failedTests[] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }
    }

    private function findTestFiles(array $options): array
    {
        $testFiles = [];
        $categories = [
            'integration' => __DIR__ . '/integration',
            'health' => __DIR__ . '/health',
            'edge' => __DIR__ . '/edge',
            'security' => __DIR__ . '/security'
        ];

        if (isset($options['category'])) {
            $category = $options['category'];
            if (isset($categories[$category])) {
                $testFiles = glob($categories[$category] . '/*Test.php');
            }
        } elseif (isset($options['test'])) {
            foreach ($categories as $dir) {
                $file = $dir . '/' . $options['test'] . '.php';
                if (file_exists($file)) {
                    $testFiles[] = $file;
                    break;
                }
            }
        } else {
            foreach ($categories as $dir) {
                $testFiles = array_merge($testFiles, glob($dir . '/*Test.php'));
            }
        }

        return $testFiles;
    }

    private function runTestFile(string $file): void
    {
        $this->logger->info("Running test file: {$file}");
        $startTime = microtime(true);

        $command = sprintf(
            'php vendor/bin/phpunit %s --configuration %s/phpunit.xml',
            escapeshellarg($file),
            __DIR__
        );

        exec($command, $output, $returnCode);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->testResults[$file] = [
            'output' => $output,
            'returnCode' => $returnCode,
            'executionTime' => $executionTime
        ];

        $this->performanceMetrics[$file] = [
            'executionTime' => $executionTime,
            'memoryUsage' => memory_get_peak_usage(true)
        ];

        if ($returnCode !== 0) {
            throw new \RuntimeException("Test file {$file} failed with return code {$returnCode}");
        }
    }

    private function generateReports(): void
    {
        $this->logger->info('Generating test reports');
        
        // Test Results Report
        $this->generateTestResultsReport();
        
        // Performance Report
        $this->generatePerformanceReport();
        
        // Security Report
        $this->generateSecurityReport();
        
        // Coverage Report
        $this->generateCoverageReport();
        
        // Issue Report
        $this->generateIssueReport();
    }

    private function generateTestResultsReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => count($this->testResults),
            'failed_tests' => count($this->failedTests),
            'test_results' => $this->testResults,
            'failed_tests_details' => $this->failedTests
        ];

        $reportFile = $this->reportDir . '/test-results/test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        $this->logger->info("Test results report generated: {$reportFile}");
    }

    private function generatePerformanceReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'performance_metrics' => $this->performanceMetrics,
            'summary' => [
                'average_execution_time' => array_sum(array_column($this->performanceMetrics, 'executionTime')) / count($this->performanceMetrics),
                'total_memory_usage' => array_sum(array_column($this->performanceMetrics, 'memoryUsage'))
            ]
        ];

        $reportFile = $this->reportDir . '/performance/performance_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        $this->logger->info("Performance report generated: {$reportFile}");
    }

    private function generateSecurityReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'security_findings' => $this->securityFindings,
            'summary' => [
                'total_findings' => count($this->securityFindings),
                'critical_findings' => count(array_filter($this->securityFindings, fn($f) => $f['severity'] === 'critical'))
            ]
        ];

        $reportFile = $this->reportDir . '/security/security_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        $this->logger->info("Security report generated: {$reportFile}");
    }

    private function generateCoverageReport(): void
    {
        $command = sprintf(
            'php vendor/bin/phpunit --coverage-html %s/coverage --coverage-clover %s/coverage/clover.xml',
            $this->reportDir,
            $this->reportDir
        );

        exec($command, $output, $returnCode);
        $this->logger->info("Coverage report generated");
    }

    private function generateIssueReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'issues' => array_merge(
                $this->failedTests,
                $this->securityFindings
            ),
            'summary' => [
                'total_issues' => count($this->failedTests) + count($this->securityFindings),
                'failed_tests' => count($this->failedTests),
                'security_findings' => count($this->securityFindings)
            ]
        ];

        $reportFile = $this->reportDir . '/issues/issue_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        $this->logger->info("Issue report generated: {$reportFile}");
    }

    private function attemptRecovery(): void
    {
        $this->logger->info('Attempting recovery');
        
        // Attempt to reinitialize services
        try {
            $this->serviceManager->initialize();
        } catch (\Exception $e) {
            $this->logger->error('Service recovery failed: ' . $e->getMessage());
        }

        // Clean up any stale connections
        $this->serviceManager->cleanup();
    }

    private function cleanup(): void
    {
        $this->logger->info('Cleaning up');
        $this->serviceManager->cleanup();
    }
}

// Parse command line options
$options = [];
$longopts = ['category:', 'test:'];
$opts = getopt('', $longopts);

if (isset($opts['category'])) {
    $options['category'] = $opts['category'];
}
if (isset($opts['test'])) {
    $options['test'] = $opts['test'];
}

// Run the tests
$runner = new MCPTestRunner();
$runner->run($options); 