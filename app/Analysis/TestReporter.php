<?php

namespace App\Analysis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TestReporter
{
    protected array $metrics = [];
    protected array $results = [];
    protected string $reportPath;
    protected string $checklistItem;

    public function __construct(string $checklistItem)
    {
        $this->checklistItem = $checklistItem;
        $this->reportPath = storage_path('app/test-reports');
        $this->ensureReportDirectory();
    }

    /**
     * Add a test result.
     *
     * @param string $testName
     * @param bool $passed
     * @param array $details
     * @return void
     */
    public function addResult(string $testName, bool $passed, array $details = []): void
    {
        $this->results[] = [
            'test_name' => $testName,
            'passed' => $passed,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Add a metric.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addMetric(string $name, $value): void
    {
        $this->metrics[$name] = $value;
    }

    /**
     * Generate the test report.
     *
     * @return array
     */
    public function generateReport(): array
    {
        $report = [
            'checklist_item' => $this->checklistItem,
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'total_tests' => count($this->results),
                'passed_tests' => count(array_filter($this->results, fn($r) => $r['passed'])),
                'failed_tests' => count(array_filter($this->results, fn($r) => !$r['passed'])),
                'coverage_percentage' => $this->calculateCoverage(),
            ],
            'metrics' => $this->metrics,
            'results' => $this->results,
        ];

        $this->saveReport($report);
        return $report;
    }

    /**
     * Save the report to disk.
     *
     * @param array $report
     * @return void
     */
    protected function saveReport(array $report): void
    {
        $filename = sprintf(
            '%s/%s_%s.json',
            $this->reportPath,
            $this->checklistItem,
            now()->format('Y-m-d_His')
        );

        File::put($filename, json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Calculate test coverage percentage.
     *
     * @return float
     */
    protected function calculateCoverage(): float
    {
        if (empty($this->results)) {
            return 0.0;
        }

        $passedTests = count(array_filter($this->results, fn($r) => $r['passed']));
        return ($passedTests / count($this->results)) * 100;
    }

    /**
     * Ensure the report directory exists.
     *
     * @return void
     */
    protected function ensureReportDirectory(): void
    {
        if (!File::exists($this->reportPath)) {
            File::makeDirectory($this->reportPath, 0755, true);
        }
    }
} 