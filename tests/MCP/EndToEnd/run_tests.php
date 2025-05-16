<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Warning;
use PHPUnit\Framework\TestFailure;

class EndToEndTestListener implements TestListener
{
    use TestListenerDefaultImplementation;

    private $logFile;
    private $errorDir;
    private $failureDir;
    private $startTime;
    private $testCount = 0;
    private $failureCount = 0;
    private $errorCount = 0;
    private $warningCount = 0;

    public function __construct()
    {
        $this->logFile = __DIR__ . '/../../logs/end_to_end_tests.log';
        $this->errorDir = __DIR__ . '/../../.errors';
        $this->failureDir = __DIR__ . '/../../.failures';
        $this->startTime = microtime(true);

        // Create directories if they don't exist
        if (!is_dir($this->errorDir)) {
            mkdir($this->errorDir, 0777, true);
        }
        if (!is_dir($this->failureDir)) {
            mkdir($this->failureDir, 0777, true);
        }
    }

    public function startTestSuite(TestSuite $suite): void
    {
        $this->log("Starting test suite: " . $suite->getName());
    }

    public function endTestSuite(TestSuite $suite): void
    {
        $duration = microtime(true) - $this->startTime;
        $this->log("\nTest Suite Summary:");
        $this->log("Total Tests: " . $this->testCount);
        $this->log("Failures: " . $this->failureCount);
        $this->log("Errors: " . $this->errorCount);
        $this->log("Warnings: " . $this->warningCount);
        $this->log("Duration: " . number_format($duration, 2) . " seconds");
    }

    public function startTest(Test $test): void
    {
        $this->testCount++;
        $this->log("\nRunning test: " . $test->getName());
    }

    public function endTest(Test $test, float $time): void
    {
        $this->log("Test completed in " . number_format($time, 2) . " seconds");
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        $this->failureCount++;
        $this->logError("FAILURE", $test, $e);
        $this->writeFailure($test, $e);
    }

    public function addError(Test $test, Throwable $e, float $time): void
    {
        $this->errorCount++;
        $this->logError("ERROR", $test, $e);
        $this->writeError($test, $e);
    }

    public function addWarning(Test $test, Warning $e, float $time): void
    {
        $this->warningCount++;
        $this->logError("WARNING", $test, $e);
    }

    private function logError(string $type, Test $test, Throwable $e): void
    {
        $this->log("\n{$type} in test: " . $test->getName());
        $this->log("Message: " . $e->getMessage());
        $this->log("Stack trace:");
        $this->log($e->getTraceAsString());
    }

    private function writeError(Test $test, Throwable $e): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $testName = $test->getName();
        $className = get_class($test);
        
        $errorData = [
            'timestamp' => $timestamp,
            'test_class' => $className,
            'test_name' => $testName,
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString()
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

    private function writeFailure(Test $test, AssertionFailedError $e): void
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

    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
}

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0777, true);
}

// Clear previous log file
file_put_contents(__DIR__ . '/../../logs/end_to_end_tests.log', '');

// Run the tests
$command = sprintf(
    '%s --configuration %s --testdox --colors=always %s',
    escapeshellarg(PHP_BINARY),
    escapeshellarg(__DIR__ . '/../../phpunit.xml'),
    escapeshellarg(__DIR__)
);

$process = proc_open($command, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes);

if (is_resource($process)) {
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);

    foreach ($pipes as $pipe) {
        fclose($pipe);
    }

    $returnValue = proc_close($process);

    if ($returnValue !== 0) {
        echo "Tests failed with return value: {$returnValue}\n";
        if ($errors) {
            echo "Errors:\n{$errors}\n";
        }
        exit(1);
    }
} else {
    echo "Failed to execute test command\n";
    exit(1);
} 