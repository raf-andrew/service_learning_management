<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestResult;

// Create test suite
$suite = new TestSuite('MCP Agentic Tests');

// Add test files
$suite->addTestFile(__DIR__ . '/AgenticServerTest.php');

// Create test result
$result = new TestResult();

// Run tests
$runner = new TestRunner();
$runner->run($suite, $result);

// Output results
echo "\nTest Results:\n";
echo "Tests: " . $result->count() . "\n";
echo "Assertions: " . $result->assertionCount() . "\n";
echo "Failures: " . count($result->failures()) . "\n";
echo "Errors: " . count($result->errors()) . "\n";

// Handle failures
if (count($result->failures()) > 0) {
    echo "\nFailures:\n";
    foreach ($result->failures() as $failure) {
        file_put_contents(
            __DIR__ . '/../../../../.failures',
            $failure->toString() . "\n",
            FILE_APPEND
        );
    }
}

// Handle errors
if (count($result->errors()) > 0) {
    echo "\nErrors:\n";
    foreach ($result->errors() as $error) {
        file_put_contents(
            __DIR__ . '/../../../../.errors',
            $error->toString() . "\n",
            FILE_APPEND
        );
    }
}

// Exit with appropriate status code
exit(count($result->failures()) + count($result->errors()) > 0 ? 1 : 0); 