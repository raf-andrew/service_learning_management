<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CodespacesTestReporter
{
    protected $resultsPath;
    protected $failuresPath;
    protected $completePath;
    protected $currentTest;

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
        $this->currentTest = [
            'name' => $testName,
            'started_at' => now()->toIso8601String(),
            'status' => 'running',
            'steps' => []
        ];
    }

    public function addStep(string $step, string $status, ?string $message = null): void
    {
        if (!$this->currentTest) {
            throw new \RuntimeException('No test is currently running');
        }

        $this->currentTest['steps'][] = [
            'name' => $step,
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toIso8601String()
        ];
    }

    public function completeTest(bool $success, ?string $message = null): void
    {
        if (!$this->currentTest) {
            throw new \RuntimeException('No test is currently running');
        }

        $this->currentTest['status'] = $success ? 'completed' : 'failed';
        $this->currentTest['completed_at'] = now()->toIso8601String();
        $this->currentTest['message'] = $message;

        $timestamp = now()->format('Ymd-His');
        $testId = Str::slug($this->currentTest['name']) . '-' . $timestamp;
        
        if ($success) {
            $this->saveCompletedTest($testId);
        } else {
            $this->saveFailedTest($testId);
        }

        $this->currentTest = null;
    }

    protected function saveCompletedTest(string $testId): void
    {
        $reportFile = "{$this->completePath}/test-{$testId}.json";
        File::put($reportFile, json_encode($this->currentTest, JSON_PRETTY_PRINT));

        Log::channel('codespaces')->info(
            "Test completed successfully: {$this->currentTest['name']}",
            $this->currentTest
        );
    }

    protected function saveFailedTest(string $testId): void
    {
        $reportFile = "{$this->failuresPath}/test-{$testId}.json";
        File::put($reportFile, json_encode($this->currentTest, JSON_PRETTY_PRINT));

        Log::channel('codespaces')->error(
            "Test failed: {$this->currentTest['name']}",
            $this->currentTest
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
        if (!$this->currentTest) {
            throw new \RuntimeException('No test is currently running');
        }

        $this->currentTest['checklist_item'] = $checklistItem;
    }
} 