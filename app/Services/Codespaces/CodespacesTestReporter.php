<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CodespacesTestReporter
{
    protected $resultsPath;
    protected $failuresPath;
    protected $completePath;
    protected $currentTestId;
    protected $steps = [];
    protected array $lastResults = [];

    public function __construct()
    {
        $this->resultsPath = base_path('.codespaces/testing/results');
        $this->failuresPath = base_path('.codespaces/testing/failures');
        $this->completePath = base_path('.codespaces/testing/complete');

        $this->ensureDirectoriesExist();
    }

    protected function ensureDirectoriesExist(): void
    {
        foreach ([$this->resultsPath, $this->failuresPath, $this->completePath] as $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    public function startTest(string $testName): void
    {
        $this->currentTestId = $testName;
        $this->steps = [];
        Log::info("Test started: {$testName}");
    }

    public function addStep(string $step, string $status, ?string $message = null): void
    {
        $this->steps[] = [
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'timestamp' => now()
        ];
        Log::info("Test step: {$step} - {$status}", ['message' => $message]);
    }

    public function completeTest(bool $success, ?string $message = null): void
    {
        $status = $success ? 'passed' : 'failed';
        Log::info("Test completed: {$this->currentTestId} - {$status}", ['message' => $message]);
        
        // Reset for next test
        $this->currentTestId = null;
        $this->steps = [];
    }

    protected function saveCompletedTest(string $testId): void
    {
        $reportFile = "{$this->completePath}/test-{$testId}.json";
        File::put($reportFile, json_encode($this->steps, JSON_PRETTY_PRINT));

        Log::channel('codespaces')->info(
            "Test completed successfully: {$this->currentTestId}",
            $this->steps
        );
    }

    protected function saveFailedTest(string $testId): void
    {
        $reportFile = "{$this->failuresPath}/test-{$testId}.json";
        File::put($reportFile, json_encode($this->steps, JSON_PRETTY_PRINT));

        Log::channel('codespaces')->error(
            "Test failed: {$this->currentTestId}",
            $this->steps
        );
    }

    public function generateSummary(): array
    {
        $summary = [
            'total' => 0,
            'completed' => 0,
            'failed' => 0,
            'timestamp' => now()->toIso8601String()
        ];

        // Count completed tests
        $completedTests = File::files($this->completePath);
        $summary['completed'] = count($completedTests);
        $summary['total'] += $summary['completed'];

        // Count failed tests
        $failedTests = File::files($this->failuresPath);
        $summary['failed'] = count($failedTests);
        $summary['total'] += $summary['failed'];

        // Save summary
        $timestamp = now()->format('Ymd-His');
        $summaryFile = "{$this->resultsPath}/summary-{$timestamp}.json";
        File::put($summaryFile, json_encode($summary, JSON_PRETTY_PRINT));

        return $summary;
    }

    public function cleanupOldReports(int $days = 7): void
    {
        $cutoff = now()->subDays($days);
        
        foreach ([$this->resultsPath, $this->failuresPath, $this->completePath] as $path) {
            $files = File::files($path);
            
            foreach ($files as $file) {
                if (File::lastModified($file) < $cutoff->timestamp) {
                    File::delete($file);
                }
            }
        }
    }

    public function linkToChecklist(string $testId, string $checklistItem): void
    {
        Log::info("Test linked to checklist: {$testId} -> {$checklistItem}");
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getCurrentTestId(): ?string
    {
        return $this->currentTestId;
    }

    public function generateReport(array $results): string
    {
        $this->lastResults = $results;
        
        $html = $this->generateHtmlReport($results);
        return $html;
    }

    public function saveReport(array $results): string
    {
        $this->lastResults = $results;
        
        // Generate report
        $html = $this->generateHtmlReport($results);
        
        // Create filename
        $timestamp = now()->format('Y-m-d_H-i-s');
        $suite = $results['suite'] ?? 'all';
        $filename = "test-reports/{$suite}-{$timestamp}.html";
        
        // Save report
        Storage::put($filename, $html);
        
        return $filename;
    }

    protected function generateHtmlReport(array $results): string
    {
        $passed = $results['passed'] ?? 0;
        $failed = $results['failed'] ?? 0;
        $skipped = $results['skipped'] ?? 0;
        $duration = $results['duration'] ?? 0;
        $memory = $results['memory'] ?? '0MB';
        $suite = $results['suite'] ?? 'all';
        $details = $results['details'] ?? [];
        $timestamp = $results['timestamp'] ?? now()->toIso8601String();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Report - {$suite}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-item {
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .passed { background: #d4edda; color: #155724; }
        .failed { background: #f8d7da; color: #721c24; }
        .skipped { background: #fff3cd; color: #856404; }
        .details {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
        }
        .test-case {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 3px;
        }
        .test-case.failed {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .test-case.passed {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .test-case.skipped {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .error-message {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            margin-top: 10px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Test Report - {$suite}</h1>
        <p>Generated at: {$timestamp}</p>
    </div>

    <div class="summary">
        <div class="summary-item passed">
            <h3>Passed</h3>
            <p>{$passed}</p>
        </div>
        <div class="summary-item failed">
            <h3>Failed</h3>
            <p>{$failed}</p>
        </div>
        <div class="summary-item skipped">
            <h3>Skipped</h3>
            <p>{$skipped}</p>
        </div>
        <div class="summary-item">
            <h3>Duration</h3>
            <p>{$duration}s</p>
        </div>
        <div class="summary-item">
            <h3>Memory</h3>
            <p>{$memory}</p>
        </div>
    </div>

    <div class="details">
        <h2>Test Details</h2>
HTML;

        foreach ($details as $test) {
            $status = $test['status'] ?? 'unknown';
            $name = $test['name'] ?? 'Unknown Test';
            $duration = $test['duration'] ?? 0;
            $memory = $test['memory'] ?? '0MB';
            $error = $test['error'] ?? '';

            $html .= <<<HTML
        <div class="test-case {$status}">
            <h3>{$name}</h3>
            <p>Status: {$status}</p>
            <p>Duration: {$duration}s</p>
            <p>Memory: {$memory}</p>
HTML;

            if ($error) {
                $html .= <<<HTML
            <div class="error-message">{$error}</div>
HTML;
            }

            $html .= <<<HTML
        </div>
HTML;
        }

        $html .= <<<HTML
    </div>
</body>
</html>
HTML;

        return $html;
    }
} 