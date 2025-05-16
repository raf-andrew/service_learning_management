<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestResult;

// Create error and failure directories if they don't exist
$errorDir = __DIR__ . '/../../.errors';
$failureDir = __DIR__ . '/../../.failures';

if (!file_exists($errorDir)) {
    mkdir($errorDir, 0755, true);
}

if (!file_exists($failureDir)) {
    mkdir($failureDir, 0755, true);
}

// Custom test listener for error and failure logging
class ErrorFailureListener implements \PHPUnit\Framework\TestListener
{
    private $errorDir;
    private $failureDir;

    public function __construct(string $errorDir, string $failureDir)
    {
        $this->errorDir = $errorDir;
        $this->failureDir = $failureDir;
    }

    public function addError(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        $this->logError($test, $t);
    }

    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        $this->logFailure($test, $e);
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        // Handle warnings if needed
    }

    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        // Handle incomplete tests if needed
    }

    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        // Handle risky tests if needed
    }

    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        // Handle skipped tests if needed
    }

    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        // Handle test suite start if needed
    }

    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        // Handle test suite end if needed
    }

    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        // Handle test start if needed
    }

    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
        // Handle test end if needed
    }

    private function logError(\PHPUnit\Framework\Test $test, \Throwable $t): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $testName = $test->getName();
        $className = get_class($test);
        
        $errorData = [
            'timestamp' => $timestamp,
            'test_class' => $className,
            'test_name' => $testName,
            'error_type' => get_class($t),
            'error_message' => $t->getMessage(),
            'error_trace' => $t->getTraceAsString()
        ];

        $filename = sprintf(
            '%s/%s_%s_%s.json',
            $this->errorDir,
            $timestamp,
            str_replace('\\', '_', $className),
            $testName
        );

        file_put_contents($filename, json_encode($errorData, JSON_PRETTY_PRINT));
    }

    private function logFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $testName = $test->getName();
        $className = get_class($test);
        
        $failureData = [
            'timestamp' => $timestamp,
            'test_class' => $className,
            'test_name' => $testName,
            'failure_type' => get_class($e),
            'failure_message' => $e->getMessage(),
            'failure_trace' => $e->getTraceAsString()
        ];

        $filename = sprintf(
            '%s/%s_%s_%s.json',
            $this->failureDir,
            $timestamp,
            str_replace('\\', '_', $className),
            $testName
        );

        file_put_contents($filename, json_encode($failureData, JSON_PRETTY_PRINT));
    }
}

// Create test suite
$suite = new TestSuite();
$suite->addTestSuite('Tests\MCP\Integration\Api\AgentManagementTest');
$suite->addTestSuite('Tests\MCP\Integration\Api\ServiceManagementTest');

// Create test result
$result = new TestResult();

// Add custom listener
$result->addListener(new ErrorFailureListener($errorDir, $failureDir));

// Run tests
$runner = new TestRunner();
$runner->run($suite, [], $result);

// Output results
echo "\nTest Results:\n";
echo "Tests: " . $result->count() . "\n";
echo "Failures: " . $result->failureCount() . "\n";
echo "Errors: " . $result->errorCount() . "\n";
echo "Skipped: " . $result->skippedCount() . "\n";
echo "Incomplete: " . $result->notImplementedCount() . "\n";
echo "Risky: " . $result->riskyCount() . "\n";
echo "Time: " . $result->time() . " seconds\n";

// Exit with appropriate status code
exit($result->wasSuccessful() ? 0 : 1); 