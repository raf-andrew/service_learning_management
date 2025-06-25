<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Running Command Tests...\n";

// Test suites to run
$testSuites = [
    'Web3CommandsTest',
    'HealthMonitorCommandsTest', 
    'InfrastructureCommandsTest',
    'CodespaceCommandsTest',
    'UtilityCommandsTest',
    'AnalyticsCommandsTest',
    'ConfigCommandsTest',
    'SniffingCommandsTest'
];

$results = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testSuites as $suite) {
    echo "\n=== Testing {$suite} ===\n";
    
    try {
        $testClass = "Tests\\Feature\\Commands\\{$suite}";
        $reflection = new ReflectionClass($testClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $testMethods = array_filter($methods, function($method) {
            return strpos($method->getName(), 'test_') === 0;
        });
        
        $suiteTests = count($testMethods);
        $totalTests += $suiteTests;
        
        echo "Found {$suiteTests} test methods\n";
        
        // Run each test method
        foreach ($testMethods as $method) {
            $testName = $method->getName();
            echo "  Running {$testName}... ";
            
            try {
                $testInstance = new $testClass();
                $testInstance->setUp();
                $testInstance->{$testName}();
                $testInstance->tearDown();
                
                echo "PASSED\n";
                $passedTests++;
            } catch (Exception $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
                $failedTests++;
            }
        }
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

echo "\n=== Test Summary ===\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: {$failedTests}\n";
echo "Success Rate: " . ($totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0) . "%\n";

// Generate report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $totalTests,
    'passed_tests' => $passedTests,
    'failed_tests' => $failedTests,
    'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0,
    'test_suites' => $testSuites
];

if (!is_dir('.reports/command-tests')) {
    mkdir('.reports/command-tests', 0755, true);
}

file_put_contents('.reports/command-tests/test-results.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nReport saved to .reports/command-tests/test-results.json\n"; 