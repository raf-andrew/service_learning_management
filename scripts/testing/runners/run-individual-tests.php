<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Create temp directory if it doesn't exist
$tempDir = __DIR__ . '/../.temp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Function to run a single test file
function runTest($testFile) {
    $output = [];
    $returnVar = 0;
    $command = "php vendor/bin/phpunit --log-junit .temp/{$testFile}.xml {$testFile}";
    exec($command, $output, $returnVar);
    
    return [
        'success' => $returnVar === 0,
        'output' => implode("\n", $output),
        'xml_file' => ".temp/{$testFile}.xml"
    ];
}

// Function to run a single code sniff
function runCodeSniff($file) {
    $output = [];
    $returnVar = 0;
    $command = "php vendor/bin/phpcs --standard=PSR12 --report=junit --report-file=.temp/{$file}.xml {$file}";
    exec($command, $output, $returnVar);
    
    return [
        'success' => $returnVar === 0,
        'output' => implode("\n", $output),
        'xml_file' => ".temp/{$file}.xml"
    ];
}

// Run individual tests
$testFiles = [
    'tests/Unit/ServiceHealthAgentTest.php',
    'tests/Unit/DeploymentAutomationAgentTest.php'
];

$results = [];

foreach ($testFiles as $testFile) {
    echo "Running test: {$testFile}\n";
    $results[$testFile] = runTest($testFile);
}

// Run code sniffs
$filesToSniff = [
    'src/ServiceHealthAgent.php',
    'tests/Unit/ServiceHealthAgentTest.php',
    'src/DeploymentAutomationAgent.php',
    'tests/Unit/DeploymentAutomationAgentTest.php'
];

foreach ($filesToSniff as $file) {
    echo "Running code sniff: {$file}\n";
    $results[$file] = runCodeSniff($file);
}

// Generate report
$report = "# Test Results Report\n\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($results as $file => $result) {
    $status = $result['success'] ? '✅ PASSED' : '❌ FAILED';
    $report .= "## {$file}\n";
    $report .= "Status: {$status}\n";
    $report .= "XML Report: {$result['xml_file']}\n\n";
    if (!$result['success']) {
        $report .= "### Output\n```\n{$result['output']}\n```\n\n";
    }
}

file_put_contents('.temp/test_report.md', $report);
echo "Report generated at .temp/test_report.md\n"; 