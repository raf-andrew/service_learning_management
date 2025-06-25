<?php

function parseJUnitXml($file) {
    if (!file_exists($file)) {
        return ['total' => 0, 'passed' => 0, 'failed' => 0, 'failures' => []];
    }
    
    $xml = simplexml_load_file($file);
    $total = (int)$xml->testsuite['tests'];
    $failures = (int)$xml->testsuite['failures'];
    $passed = $total - $failures;
    
    $failureDetails = [];
    foreach ($xml->testsuite->testcase as $testcase) {
        if (isset($testcase->failure)) {
            $failureDetails[] = [
                'name' => (string)$testcase['name'],
                'message' => (string)$testcase->failure
            ];
        }
    }
    
    return [
        'total' => $total,
        'passed' => $passed,
        'failed' => $failures,
        'failures' => $failureDetails
    ];
}

function parsePHPMDXml($file) {
    if (!file_exists($file)) {
        return ['violations' => []];
    }
    
    $xml = simplexml_load_file($file);
    $violations = [];
    
    foreach ($xml->file as $file) {
        foreach ($file->violation as $violation) {
            $violations[] = [
                'file' => (string)$file['name'],
                'line' => (int)$violation['beginline'],
                'rule' => (string)$violation['rule'],
                'message' => (string)$violation
            ];
        }
    }
    
    return ['violations' => $violations];
}

// Parse test results
$unitTestResults = parseJUnitXml('.temp/unit-tests/junit.xml');
$psr12Results = parseJUnitXml('.temp/code-sniffs/psr12.xml');
$phpmdResults = parsePHPMDXml('.temp/phpmd/phpmd.xml');

// Generate summary report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'unit_tests' => $unitTestResults,
    'code_quality' => [
        'psr12' => $psr12Results,
        'phpmd' => $phpmdResults
    ]
];

// Save report
file_put_contents('.temp/summary.json', json_encode($report, JSON_PRETTY_PRINT));

// Update test plan
$testPlan = file_get_contents('.reports/test_plan.md');
$testPlan = preg_replace(
    '/```json\n\{\n    "total": \d+,\n    "passed": \d+,\n    "failed": \d+,\n    "failures": \[\]\n\}\n```/',
    '```json' . PHP_EOL . json_encode($unitTestResults, JSON_PRETTY_PRINT) . PHP_EOL . '```',
    $testPlan
);

$testPlan = preg_replace(
    '/```json\n\{\n    "total": \d+,\n    "passed": \d+,\n    "failed": \d+,\n    "violations": \{\n        "psr12": \{\n            "tests": \d+,\n            "failures": \d+,\n            "errors": \d+\n        \},\n        "phpmd": \{\n            "violations": \d+\n        \}\n    \}\n\}\n```/',
    '```json' . PHP_EOL . json_encode([
        'total' => $psr12Results['total'] + count($phpmdResults['violations']),
        'passed' => $psr12Results['passed'],
        'failed' => $psr12Results['failed'] + count($phpmdResults['violations']),
        'violations' => [
            'psr12' => [
                'tests' => $psr12Results['total'],
                'failures' => $psr12Results['failed'],
                'errors' => 0
            ],
            'phpmd' => [
                'violations' => count($phpmdResults['violations'])
            ]
        ]
    ], JSON_PRETTY_PRINT) . PHP_EOL . '```',
    $testPlan
);

file_put_contents('.reports/test_plan.md', $testPlan);

echo "Report generation complete.\n"; 