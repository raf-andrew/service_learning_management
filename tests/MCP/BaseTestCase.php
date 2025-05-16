<?php

namespace Tests\MCP;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use App\Services\RemoteServiceManager;

/**
 * Base test case class for MCP tests.
 * 
 * This class provides common functionality for all MCP tests, including:
 * - Error and failure logging
 * - Test coverage tracking
 * - Documentation reference tracking
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
abstract class BaseTestCase extends TestCase
{
    protected LoggerInterface $logger;
    protected string $testDocumentation;
    protected array $testCoverage = [];
    protected RemoteServiceManager $serviceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupLogging();
        $this->setupDocumentation();
        $this->setupServiceManager();
        $this->attemptSelfHealing();
    }

    protected function tearDown(): void
    {
        $this->logTestResults();
        $this->serviceManager->cleanup();
        parent::tearDown();
    }

    /**
     * Set up logging for the test
     */
    protected function setupLogging(): void
    {
        $logDir = __DIR__ . '/../../storage/logs/tests';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logger = new \MCP\Core\Logger\Logger(
            'mcp_test',
            $logDir,
            [
                'test_name' => $this->getName(),
                'test_class' => get_class($this)
            ]
        );
        $this->logger->setLogLevel(\Psr\Log\LogLevel::DEBUG);
    }

    /**
     * Set up documentation reference for the test
     */
    protected function setupDocumentation(): void
    {
        $this->testDocumentation = $this->getTestDocumentation();
    }

    /**
     * Get the documentation reference for this test
     */
    protected function getTestDocumentation(): string
    {
        $reflection = new \ReflectionClass($this);
        $docComment = $reflection->getDocComment();
        
        if ($docComment === false) {
            return '';
        }

        // Extract @see references from doc comment
        preg_match_all('/@see\s+([^\s]+)/', $docComment, $matches);
        return implode("\n", $matches[1] ?? []);
    }

    /**
     * Log test results including errors and failures
     */
    protected function logTestResults(): void
    {
        $status = $this->getStatus();
        $testName = $this->getName();
        $timestamp = date('Y-m-d H:i:s');
        
        if ($status === 'error') {
            $this->logError($testName, $this->getStatusMessage());
        } elseif ($status === 'failure') {
            $this->logFailure($testName, $this->getStatusMessage());
        }
        
        $this->logCoverage($testName);
    }

    /**
     * Log an error to the .errors directory
     */
    protected function logError(string $testName, string $message): void
    {
        $errorLog = sprintf(
            "[%s] Test: %s\nError: %s\nDocumentation: %s\n\n",
            date('Y-m-d H:i:s'),
            $testName,
            $message,
            $this->testDocumentation
        );
        
        file_put_contents(
            __DIR__ . '/../../.errors/' . date('Y-m-d') . '.log',
            $errorLog,
            FILE_APPEND
        );
    }

    /**
     * Log a failure to the .failures directory
     */
    protected function logFailure(string $testName, string $message): void
    {
        $failureLog = sprintf(
            "[%s] Test: %s\nFailure: %s\nDocumentation: %s\n\n",
            date('Y-m-d H:i:s'),
            $testName,
            $message,
            $this->testDocumentation
        );
        
        file_put_contents(
            __DIR__ . '/../../.failures/' . date('Y-m-d') . '.log',
            $failureLog,
            FILE_APPEND
        );
    }

    /**
     * Log test coverage information
     */
    protected function logCoverage(string $testName): void
    {
        if (empty($this->testCoverage)) {
            return;
        }

        $coverageLog = sprintf(
            "[%s] Test: %s\nCoverage: %s\n\n",
            date('Y-m-d H:i:s'),
            $testName,
            json_encode($this->testCoverage, JSON_PRETTY_PRINT)
        );
        
        file_put_contents(
            __DIR__ . '/../../.coverage/' . date('Y-m-d') . '.log',
            $coverageLog,
            FILE_APPEND
        );
    }

    /**
     * Get the current test status
     */
    protected function getStatus(): string
    {
        if ($this->hasFailed()) {
            return 'failure';
        }
        if ($this->getStatus() === 'error') {
            return 'error';
        }
        return 'success';
    }

    /**
     * Get the status message for the current test
     */
    protected function getStatusMessage(): string
    {
        if ($this->hasFailed()) {
            return $this->getFailureMessage();
        }
        if ($this->getStatus() === 'error') {
            return $this->getErrorMessage();
        }
        return 'Test passed successfully';
    }

    /**
     * Assert that the test has documentation
     */
    protected function assertHasDocumentation(): void
    {
        $this->assertNotEmpty(
            $this->testDocumentation,
            'Test must have documentation reference'
        );
    }

    /**
     * Assert that the test has coverage
     */
    protected function assertHasCoverage(): void
    {
        $this->assertNotEmpty(
            $this->testCoverage,
            'Test must have coverage information'
        );
    }

    /**
     * Set up the remote service manager
     */
    protected function setupServiceManager(): void
    {
        $this->serviceManager = new RemoteServiceManager($this->logger);
        $this->serviceManager->initialize();
    }

    /**
     * Check remote service health before running tests
     */
    protected function checkRemoteServices(): void
    {
        if (!$this->serviceManager->checkHealth()) {
            throw new \RuntimeException("One or more remote services are not healthy");
        }
    }

    /**
     * Attempt to self-heal any issues before running tests
     */
    protected function attemptSelfHealing(): void
    {
        $this->healEnvironment();
        $this->healConnections();
        $this->healTestData();
    }

    /**
     * Heal environment issues
     */
    protected function healEnvironment(): void
    {
        // Create required directories if they don't exist
        $directories = [
            __DIR__ . '/../../storage/logs/tests',
            __DIR__ . '/../../.errors',
            __DIR__ . '/../../.failures',
            __DIR__ . '/../../.coverage'
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
                $this->logger->info("Created directory: {$directory}");
            }
        }

        // Ensure environment variables are set
        $requiredEnvVars = [
            'MCP_API_KEY',
            'CODESPACES_DB_HOST',
            'CODESPACES_DB_USERNAME',
            'CODESPACES_DB_PASSWORD',
            'CODESPACES_REDIS_HOST',
            'CODESPACES_REDIS_PASSWORD',
            'CODESPACES_MAIL_HOST',
            'CODESPACES_MAIL_USERNAME',
            'CODESPACES_MAIL_PASSWORD'
        ];

        foreach ($requiredEnvVars as $var) {
            if (!getenv($var)) {
                $this->logger->warning("Missing environment variable: {$var}");
                // Attempt to load from .env file if exists
                $this->loadEnvVariable($var);
            }
        }
    }

    /**
     * Heal connection issues
     */
    protected function healConnections(): void
    {
        $maxRetries = 3;
        $retryDelay = 2; // seconds

        foreach (['database', 'redis', 'mail', 'mcp'] as $service) {
            $attempts = 0;
            while ($attempts < $maxRetries) {
                if ($this->checkServiceConnection($service)) {
                    break;
                }
                $attempts++;
                if ($attempts < $maxRetries) {
                    $this->logger->warning("Retrying {$service} connection in {$retryDelay} seconds...");
                    sleep($retryDelay);
                }
            }
        }
    }

    /**
     * Heal test data issues
     */
    protected function healTestData(): void
    {
        try {
            // Clean up any stale test data
            $this->cleanupStaleTestData();
            
            // Ensure test database is in a clean state
            $this->resetTestDatabase();
            
            // Seed required test data
            $this->seedTestData();
        } catch (\Exception $e) {
            $this->logger->error("Failed to heal test data: " . $e->getMessage());
        }
    }

    /**
     * Check service connection with retry logic
     */
    protected function checkServiceConnection(string $service): bool
    {
        $method = "check" . ucfirst($service) . "Connection";
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return false;
    }

    /**
     * Load environment variable from .env file
     */
    protected function loadEnvVariable(string $var): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, $var . '=') === 0) {
                    $value = substr($line, strlen($var) + 1);
                    putenv("{$var}={$value}");
                    $this->logger->info("Loaded {$var} from .env file");
                    break;
                }
            }
        }
    }

    /**
     * Clean up stale test data
     */
    protected function cleanupStaleTestData(): void
    {
        // Implement cleanup logic for stale test data
        // This could include removing old test records, clearing caches, etc.
    }

    /**
     * Reset test database to a clean state
     */
    protected function resetTestDatabase(): void
    {
        try {
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $tables = \DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $tableName = reset($table);
                \DB::table($tableName)->truncate();
            }
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            $this->logger->error("Failed to reset test database: " . $e->getMessage());
        }
    }

    /**
     * Seed required test data
     */
    protected function seedTestData(): void
    {
        // Implement test data seeding logic
        // This could include creating required test users, configurations, etc.
    }
} 