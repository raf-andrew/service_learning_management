<?php

require_once __DIR__ . '/../vendor/autoload.php';

class TestRunner
{
    private array $config;
    private LoggerInterface $logger;
    private RemoteServiceManager $serviceManager;
    private array $testResults = [];
    private array $failedTests = [];

    public function __construct()
    {
        $this->config = require __DIR__ . '/config/remote-services.php';
        $this->setupLogger();
        $this->serviceManager = new RemoteServiceManager($this->logger);
    }

    private function setupLogger(): void
    {
        $logDir = $this->config['logging']['test_log_dir'];
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logger = new \MCP\Core\Logger\Logger(
            'test_runner',
            $logDir,
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    }

    public function run(): void
    {
        try {
            $this->logger->info('Starting test run');
            $this->setupEnvironment();
            $this->initializeServices();
            $this->runTests();
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
        foreach ($this->config['logging'] as $dir) {
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
        $envFile = __DIR__ . '/../.env';
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

        foreach ($this->config['logging'] as $dir) {
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

    private function runTests(): void
    {
        $this->logger->info('Running tests');
        $testFiles = $this->findTestFiles();

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

    private function findTestFiles(): array
    {
        $testFiles = [];
        $directories = [
            __DIR__ . '/MCP/Unit',
            __DIR__ . '/MCP/Integration',
            __DIR__ . '/MCP/EndToEnd'
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob("{$dir}/*Test.php");
                $testFiles = array_merge($testFiles, $files);
            }
        }

        return $testFiles;
    }

    private function runTestFile(string $file): void
    {
        $this->logger->info("Running test file: {$file}");
        $command = sprintf(
            'php vendor/bin/phpunit %s --configuration %s/phpunit.xml',
            escapeshellarg($file),
            __DIR__
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

        $reportFile = $this->config['logging']['test_log_dir'] . '/test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        $this->logger->info("Test report generated: {$reportFile}");
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

// Run the tests
$runner = new TestRunner();
$runner->run(); 