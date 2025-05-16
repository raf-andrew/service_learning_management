<?php

require_once __DIR__ . '/vendor/autoload.php';

use Codespaces\Tests\CodespacesTestRunner;

// Parse command line options
$options = getopt('', ['environment:', 'config:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php run-tests.php [options]\n";
    echo "Options:\n";
    echo "  --environment=<env>    Environment to run tests in (local or codespaces)\n";
    echo "  --config=<path>        Path to configuration file\n";
    echo "  --help                 Show this help message\n";
    exit(0);
}

// Set default values
$environment = $options['environment'] ?? 'codespaces';
$configPath = $options['config'] ?? __DIR__ . '/config/services.json';

// Validate environment
if (!in_array($environment, ['local', 'codespaces'])) {
    echo "Error: Invalid environment. Must be 'local' or 'codespaces'\n";
    exit(1);
}

// Validate config file
if (!file_exists($configPath)) {
    echo "Error: Configuration file not found: {$configPath}\n";
    exit(1);
}

try {
    // Create test runner
    $runner = new CodespacesTestRunner($configPath, $environment);

    // Run tests
    echo "Starting test suite in {$environment} environment...\n";
    $runner->runTests();

    // Get results
    $results = $runner->getTestResults();
    $failedTests = $runner->getFailedTests();
    $performanceMetrics = $runner->getPerformanceMetrics();
    $securityFindings = $runner->getSecurityFindings();

    // Print summary
    echo "\nTest Summary:\n";
    echo "=============\n";
    echo "Total Tests: " . count($results) . "\n";
    echo "Failed Tests: " . count($failedTests) . "\n";
    echo "Performance Metrics: " . count($performanceMetrics) . " categories\n";
    echo "Security Findings: " . count($securityFindings) . "\n";

    // Print failed tests
    if (!empty($failedTests)) {
        echo "\nFailed Tests:\n";
        echo "=============\n";
        foreach ($failedTests as $test) {
            echo "{$test['category']}/{$test['test']}: {$test['error']}\n";
        }
    }

    // Print performance metrics
    if (!empty($performanceMetrics)) {
        echo "\nPerformance Metrics:\n";
        echo "===================\n";
        foreach ($performanceMetrics as $category => $tests) {
            echo "\n{$category}:\n";
            foreach ($tests as $test => $metrics) {
                echo "  {$test}:\n";
                echo "    Execution Time: {$metrics['execution_time']}s\n";
                echo "    Memory Usage: " . round($metrics['memory_usage'] / 1024 / 1024, 2) . "MB\n";
            }
        }
    }

    // Print security findings
    if (!empty($securityFindings)) {
        echo "\nSecurity Findings:\n";
        echo "=================\n";
        foreach ($securityFindings as $finding) {
            echo "{$finding['service']}: {$finding['finding']}\n";
        }
    }

    // Exit with appropriate status code
    exit(empty($failedTests) ? 0 : 1);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 