<?php

namespace Scripts;

require_once __DIR__ . '/../vendor/autoload.php';

class TestRunner
{
    private array $testSuites = [
        'unit' => 'tests/Unit',
        'feature' => 'tests/Feature',
        'integration' => 'tests/Integration',
        'performance' => 'tests/Performance',
        'security' => 'tests/Security'
    ];

    private array $reports = [];
    private string $startTime;
    private float $memoryStart;

    public function __construct()
    {
        $this->startTime = date('Y-m-d H:i:s');
        $this->memoryStart = memory_get_usage(true);
    }

    public function run(): void
    {
        // Verify test environment
        $verifier = new TestEnvironmentVerifier();
        $verifier->verify();

        // Run each test suite
        foreach ($this->testSuites as $suite => $path) {
            $this->runTestSuite($suite, $path);
        }

        // Generate reports
        $this->generateReports();
    }

    private function runTestSuite(string $suite, string $path): void
    {
        echo "Running {$suite} tests...\n";
        
        $command = sprintf(
            'vendor/bin/phpunit --configuration phpunit.xml --testsuite %s --coverage-html .reports/coverage/%s --log-junit .reports/tests/%s.xml',
            $suite,
            $suite,
            $suite
        );

        exec($command, $output, $returnCode);
        
        $this->reports[$suite] = [
            'output' => $output,
            'returnCode' => $returnCode,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($returnCode !== 0) {
            echo "Warning: {$suite} tests had issues\n";
        }
    }

    private function generateReports(): void
    {
        $report = [
            'timestamp' => $this->startTime,
            'duration' => time() - strtotime($this->startTime),
            'memory_usage' => memory_get_usage(true) - $this->memoryStart,
            'suites' => []
        ];

        foreach ($this->reports as $suite => $data) {
            $report['suites'][$suite] = [
                'status' => $data['returnCode'] === 0 ? 'passed' : 'failed',
                'timestamp' => $data['timestamp']
            ];

            // Parse PHPUnit XML report if it exists
            $xmlPath = ".reports/tests/{$suite}.xml";
            if (file_exists($xmlPath)) {
                $xml = simplexml_load_file($xmlPath);
                $report['suites'][$suite]['tests'] = (int)$xml->testsuite['tests'];
                $report['suites'][$suite]['failures'] = (int)$xml->testsuite['failures'];
                $report['suites'][$suite]['errors'] = (int)$xml->testsuite['errors'];
                $report['suites'][$suite]['time'] = (float)$xml->testsuite['time'];
            }
        }

        // Save report
        file_put_contents(
            '.reports/tests/summary.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );

        // Generate markdown report
        $this->generateMarkdownReport($report);
    }

    private function generateMarkdownReport(array $report): void
    {
        $markdown = "# Test Execution Report\n\n";
        $markdown .= "## Summary\n\n";
        $markdown .= "- Start Time: {$report['timestamp']}\n";
        $markdown .= "- Duration: {$report['duration']} seconds\n";
        $markdown .= "- Memory Usage: " . round($report['memory_usage'] / 1024 / 1024, 2) . " MB\n\n";

        $markdown .= "## Test Suites\n\n";
        foreach ($report['suites'] as $suite => $data) {
            $markdown .= "### {$suite}\n\n";
            $markdown .= "- Status: {$data['status']}\n";
            $markdown .= "- Timestamp: {$data['timestamp']}\n";
            
            if (isset($data['tests'])) {
                $markdown .= "- Total Tests: {$data['tests']}\n";
                $markdown .= "- Failures: {$data['failures']}\n";
                $markdown .= "- Errors: {$data['errors']}\n";
                $markdown .= "- Time: {$data['time']} seconds\n";
            }
            
            $markdown .= "\n";
        }

        file_put_contents('.reports/tests/report.md', $markdown);
    }
}

// Run tests
$runner = new TestRunner();
$runner->run(); 