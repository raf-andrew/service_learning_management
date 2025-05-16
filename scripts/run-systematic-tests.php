#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestResult;

class SystematicTestRunner
{
    protected $testSuites = [
        'Unit' => [
            'Agent' => 'tests/Unit/Agent',
            'Service' => 'tests/Unit/Service',
            'Tenant' => 'tests/Unit/Tenant',
            'Security' => 'tests/Unit/Security'
        ],
        'Integration' => [
            'API' => 'tests/Integration/Api',
            'Service' => 'tests/Integration/Service',
            'Agent' => 'tests/Integration/Agent'
        ],
        'EndToEnd' => [
            'Workflow' => 'tests/EndToEnd/Workflow',
            'UserInteraction' => 'tests/EndToEnd/UserInteraction',
            'SystemIntegration' => 'tests/EndToEnd/SystemIntegration'
        ],
        'Feature' => [
            'Queue' => 'tests/Feature/QueueTest.php',
            'Mail' => 'tests/Feature/MailTest.php',
            'RateLimit' => 'tests/Feature/RateLimitTest.php',
            'FileUpload' => 'tests/Feature/FileUploadTest.php',
            'ConcurrentRequest' => 'tests/Feature/ConcurrentRequestTest.php',
            'Api' => 'tests/Feature/ApiTest.php'
        ]
    ];

    protected $reports = [];
    protected $startTime;

    public function run()
    {
        $this->startTime = microtime(true);
        $this->createReportDirectories();
        
        foreach ($this->testSuites as $suiteType => $suites) {
            $this->runTestSuite($suiteType, $suites);
        }

        $this->generateSummaryReport();
    }

    protected function createReportDirectories()
    {
        $directories = [
            '.reports/tests',
            '.reports/coverage',
            '.reports/performance',
            '.reports/security'
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    protected function runTestSuite($suiteType, $suites)
    {
        echo "Running {$suiteType} tests...\n";
        
        $suite = new TestSuite($suiteType);
        
        foreach ($suites as $name => $path) {
            if (is_dir($path)) {
                $this->addTestsFromDirectory($suite, $path);
            } else {
                $this->addTestFromFile($suite, $path);
            }
        }

        $result = new TestResult();
        $runner = new TestRunner();
        $runner->run($suite, $result);

        $this->reports[$suiteType] = [
            'total' => $result->count(),
            'passed' => $result->count() - $result->failureCount() - $result->errorCount(),
            'failed' => $result->failureCount(),
            'errors' => $result->errorCount(),
            'time' => $result->time()
        ];

        $this->generateSuiteReport($suiteType, $result);
    }

    protected function addTestsFromDirectory($suite, $directory)
    {
        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            $this->addTestFromFile($suite, $file);
        }
    }

    protected function addTestFromFile($suite, $file)
    {
        if (file_exists($file)) {
            require_once $file;
            $className = $this->getClassNameFromFile($file);
            if ($className) {
                $suite->addTestSuite($className);
            }
        }
    }

    protected function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        if (preg_match('/namespace\s+([^;]+);.*?class\s+(\w+)/s', $content, $matches)) {
            return $matches[1] . '\\' . $matches[2];
        }
        return null;
    }

    protected function generateSuiteReport($suiteType, $result)
    {
        $report = [
            'suite' => $suiteType,
            'date' => date('Y-m-d H:i:s'),
            'environment' => getenv('APP_ENV') ?: 'testing',
            'results' => [
                'total' => $result->count(),
                'passed' => $result->count() - $result->failureCount() - $result->errorCount(),
                'failed' => $result->failureCount(),
                'errors' => $result->errorCount(),
                'time' => $result->time()
            ],
            'coverage' => $result->getCodeCoverage() ? [
                'line' => $result->getCodeCoverage()->getReport()->getNumExecutedLines(),
                'branch' => $result->getCodeCoverage()->getReport()->getNumExecutedBranches(),
                'function' => $result->getCodeCoverage()->getReport()->getNumExecutedFunctions()
            ] : null,
            'performance' => [
                'memory' => memory_get_peak_usage(true),
                'time' => $result->time()
            ]
        ];

        file_put_contents(
            ".reports/tests/{$suiteType}_report.json",
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }

    protected function generateSummaryReport()
    {
        $summary = [
            'date' => date('Y-m-d H:i:s'),
            'duration' => microtime(true) - $this->startTime,
            'suites' => $this->reports,
            'total' => [
                'tests' => array_sum(array_column($this->reports, 'total')),
                'passed' => array_sum(array_column($this->reports, 'passed')),
                'failed' => array_sum(array_column($this->reports, 'failed')),
                'errors' => array_sum(array_column($this->reports, 'errors'))
            ]
        ];

        file_put_contents(
            '.reports/tests/summary.json',
            json_encode($summary, JSON_PRETTY_PRINT)
        );

        // Generate markdown report
        $markdown = $this->generateMarkdownReport($summary);
        file_put_contents('.reports/tests/summary.md', $markdown);
    }

    protected function generateMarkdownReport($summary)
    {
        $markdown = "# Test Execution Summary\n\n";
        $markdown .= "## Execution Information\n";
        $markdown .= "- Date: {$summary['date']}\n";
        $markdown .= "- Duration: " . number_format($summary['duration'], 2) . " seconds\n\n";

        $markdown .= "## Test Results\n";
        $markdown .= "- Total Tests: {$summary['total']['tests']}\n";
        $markdown .= "- Passed: {$summary['total']['passed']}\n";
        $markdown .= "- Failed: {$summary['total']['failed']}\n";
        $markdown .= "- Errors: {$summary['total']['errors']}\n\n";

        $markdown .= "## Suite Results\n";
        foreach ($summary['suites'] as $suite => $results) {
            $markdown .= "### {$suite}\n";
            $markdown .= "- Total: {$results['total']}\n";
            $markdown .= "- Passed: {$results['passed']}\n";
            $markdown .= "- Failed: {$results['failed']}\n";
            $markdown .= "- Errors: {$results['errors']}\n";
            $markdown .= "- Duration: " . number_format($results['time'], 2) . " seconds\n\n";
        }

        return $markdown;
    }
}

// Run the tests
$runner = new SystematicTestRunner();
$runner->run(); 