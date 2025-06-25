<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Log\LoggerInterface;
use Tests\Helpers\RemoteServiceManager;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class TestRunner
{
    private array $config;
    private LoggerInterface $logger;
    private RemoteServiceManager $serviceManager;
    private array $testResults = [];
    private array $failedTests = [];
    private array $testSuites = [
        'auth' => [
            'tests/Feature/Auth/*Test.php'
        ],
        'api' => [
            'tests/Feature/Api/*Test.php'
        ],
        'unit' => [
            'tests/Unit/*Test.php'
        ]
    ];

    public function __construct()
    {
        $this->config = require __DIR__ . '/config/remote-services.php';
        $this->setupLogger();
        $this->serviceManager = new RemoteServiceManager($this->logger);
    }

    private function setupLogger(): void
    {
        $logDir = storage_path('logs/tests');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logger = new \MCP\Core\Logger\Logger(
            'test_runner',
            $logDir,
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    }

    public function run(string $suite = null): void
    {
        try {
            $this->logger->info('Starting test run' . ($suite ? " for suite: {$suite}" : ''));
            $this->setupEnvironment();
            $this->initializeServices();
            
            if ($suite) {
                $this->runTestSuite($suite);
            } else {
                foreach (array_keys($this->testSuites) as $suiteName) {
                    $this->runTestSuite($suiteName);
                }
            }
            
            $this->generateReport();
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
        $directories = [
            storage_path('logs/tests'),
            base_path('.errors'),
            base_path('.failures'),
            base_path('.coverage')
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->logger->info("Created directory: {$dir}");
            }
        }

        // Load environment variables
        $this->loadEnvironmentVariables();

        // Clean up old logs if configured
        if ($this->config['self_healing']['cleanup_old_logs']) {
            $this->cleanupOldLogs();
        }
    }

    private function loadEnvironmentVariables(): void
    {
        $envFile = base_path('.env');
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

    private function cleanupOldLogs(): void
    {
        $retentionDays = $this->config['self_healing']['log_retention_days'];
        $cutoffDate = strtotime("-{$retentionDays} days");

        $directories = [
            storage_path('logs/tests'),
            base_path('.errors'),
            base_path('.failures'),
            base_path('.coverage')
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob("{$dir}/*");
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoffDate) {
                        unlink($file);
                        $this->logger->info("Removed old log file: {$file}");
                    }
                }
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

    private function runTestSuite(string $suite): void
    {
        $this->logger->info("Running test suite: {$suite}");
        
        if (!isset($this->testSuites[$suite])) {
            throw new \RuntimeException("Test suite {$suite} not found");
        }

        $testFiles = [];
        foreach ($this->testSuites[$suite] as $pattern) {
            $testFiles = array_merge($testFiles, glob(base_path($pattern)));
        }

        if (empty($testFiles)) {
            $this->logger->warning("No test files found for suite: {$suite}");
            return;
        }

        foreach ($testFiles as $file) {
            try {
                $this->runTestFile($file);
            } catch (\Exception $e) {
                $this->logger->error("Test file {$file} failed: " . $e->getMessage());
                $this->failedTests[] = [
                    'file' => $file,
                    'error' => $e->getMessage()
                ];
            }
        }
    }

    private function runTestFile(string $file): void
    {
        $this->logger->info("Running test file: {$file}");
        $command = sprintf(
            'php artisan test %s --configuration %s/phpunit.xml --testdox',
            escapeshellarg($file),
            base_path('tests')
        );

        exec($command, $output, $returnCode);
        $this->testResults[$file] = [
            'output' => $output,
            'returnCode' => $returnCode
        ];

        if ($returnCode !== 0) {
            throw new \RuntimeException("Test file {$file} failed with return code {$returnCode}");
        }
    }

    private function generateReport(): void
    {
        $this->logger->info('Generating test report');
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => count($this->testResults),
            'failed_tests' => count($this->failedTests),
            'test_results' => $this->testResults,
            'failed_tests_details' => $this->failedTests
        ];

        // Generate JSON report
        $reportFile = storage_path('logs/tests/test_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        $this->logger->info("Test report generated: {$reportFile}");

        // Generate HTML report
        $htmlReport = $this->generateHtmlReport($report);
        $htmlReportFile = storage_path('logs/tests/test_report_' . date('Y-m-d_H-i-s') . '.html');
        file_put_contents($htmlReportFile, $htmlReport);
        $this->logger->info("HTML test report generated: {$htmlReportFile}");
    }

    private function generateHtmlReport(array $report): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>Test Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .success { color: green; }
                .failure { color: red; }
                .test-file { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
                .test-output { background: #f5f5f5; padding: 10px; margin: 5px 0; }
            </style>
        </head>
        <body>
            <h1>Test Report</h1>
            <p>Generated: ' . $report['timestamp'] . '</p>
            <p>Total Tests: ' . $report['total_tests'] . '</p>
            <p>Failed Tests: ' . $report['failed_tests'] . '</p>';

        if (!empty($report['failed_tests_details'])) {
            $html .= '<h2>Failed Tests</h2>';
            foreach ($report['failed_tests_details'] as $failure) {
                $html .= '<div class="test-file failure">
                    <h3>' . basename($failure['file']) . '</h3>
                    <p>Error: ' . htmlspecialchars($failure['error']) . '</p>
                </div>';
            }
        }

        $html .= '<h2>Test Results</h2>';
        foreach ($report['test_results'] as $file => $result) {
            $status = $result['returnCode'] === 0 ? 'success' : 'failure';
            $html .= '<div class="test-file ' . $status . '">
                <h3>' . basename($file) . '</h3>
                <div class="test-output">' . implode('<br>', array_map('htmlspecialchars', $result['output'])) . '</div>
            </div>';
        }

        $html .= '</body></html>';
        return $html;
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

// Get the test suite from command line argument if provided
$suite = $argv[1] ?? null;

// Run the tests
$runner = new TestRunner();
$runner->run($suite); 