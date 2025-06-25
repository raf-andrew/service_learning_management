<?php

class TestReportGenerator
{
    private $reportDir;
    private $summaryFile;
    private $psr12File;
    private $phpmdFile;

    public function __construct()
    {
        $this->reportDir = __DIR__ . '/../.reports/tests';
        $this->summaryFile = $this->reportDir . '/summary.json';
        $this->psr12File = $this->reportDir . '/psr12.json';
        $this->phpmdFile = $this->reportDir . '/phpmd.json';
    }

    public function generate()
    {
        if (!file_exists($this->summaryFile)) {
            die("Test summary file not found.\n");
        }

        $summary = json_decode(file_get_contents($this->summaryFile), true);
        $psr12 = file_exists($this->psr12File) ? json_decode(file_get_contents($this->psr12File), true) : [];
        $phpmd = file_exists($this->phpmdFile) ? json_decode(file_get_contents($this->phpmdFile), true) : [];

        $report = $this->generateMarkdownReport($summary, $psr12, $phpmd);
        file_put_contents($this->reportDir . '/test-report.md', $report);

        echo "Test report generated successfully.\n";
    }

    private function generateMarkdownReport($summary, $psr12, $phpmd)
    {
        $report = "# Test Execution Report\n\n";
        $report .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

        // Summary Section
        $report .= "## Summary\n\n";
        $report .= "- Total Tests: {$summary['total']['tests']}\n";
        $report .= "- Passed: {$summary['total']['passed']}\n";
        $report .= "- Failed: {$summary['total']['failed']}\n";
        $report .= "- Errors: {$summary['total']['errors']}\n";
        $report .= "- Execution Time: {$summary['total']['time']} seconds\n\n";

        // Test Suites Section
        $report .= "## Test Suites\n\n";
        foreach ($summary['suites'] as $suite => $results) {
            $report .= "### $suite\n\n";
            $report .= "- Tests: {$results['tests']}\n";
            $report .= "- Passed: {$results['passed']}\n";
            $report .= "- Failed: {$results['failed']}\n";
            $report .= "- Errors: {$results['errors']}\n";
            $report .= "- Time: {$results['time']} seconds\n\n";
        }

        // Failed Tests Section
        if (!empty($summary['failed_tests'])) {
            $report .= "## Failed Tests\n\n";
            foreach ($summary['failed_tests'] as $test) {
                $report .= "### {$test['name']}\n\n";
                $report .= "- Suite: {$test['suite']}\n";
                $report .= "- Message: {$test['message']}\n\n";
            }
        }

        // Code Style Section
        if (!empty($psr12)) {
            $report .= "## Code Style Issues\n\n";
            foreach ($psr12 as $file => $issues) {
                $report .= "### $file\n\n";
                foreach ($issues as $issue) {
                    $report .= "- Line {$issue['line']}: {$issue['message']}\n";
                }
                $report .= "\n";
            }
        }

        // Code Quality Section
        if (!empty($phpmd)) {
            $report .= "## Code Quality Issues\n\n";
            foreach ($phpmd as $file => $issues) {
                $report .= "### $file\n\n";
                foreach ($issues as $issue) {
                    $report .= "- Priority {$issue['priority']}: {$issue['message']}\n";
                }
                $report .= "\n";
            }
        }

        // Recommendations Section
        $report .= "## Recommendations\n\n";
        if (!empty($summary['failed_tests'])) {
            $report .= "1. Fix failed tests:\n";
            foreach ($summary['failed_tests'] as $test) {
                $report .= "   - {$test['name']}: {$test['message']}\n";
            }
        }
        if (!empty($psr12)) {
            $report .= "2. Address code style issues\n";
        }
        if (!empty($phpmd)) {
            $report .= "3. Improve code quality\n";
        }

        return $report;
    }
}

// Generate the report
$generator = new TestReportGenerator();
$generator->generate(); 