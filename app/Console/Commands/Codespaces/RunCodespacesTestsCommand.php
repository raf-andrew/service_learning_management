<?php

namespace App\Console\Commands\Codespaces;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class RunCodespacesTestsCommand extends Command
{
    protected $signature = 'codespaces:run-tests';
    protected $description = 'Run tests in Codespaces environment';

    protected $testDir = '.codespaces/testing';
    protected $resultsDir;
    protected $failuresDir;
    protected $completeDir;
    protected $timestamp;
    protected $testLogFile;

    public function handle()
    {
        $this->initializeDirectories();
        $this->runHealthCheck();
        $this->runTests();
    }

    protected function initializeDirectories()
    {
        $this->timestamp = now()->format('Ymd-His');
        $this->resultsDir = "{$this->testDir}/results";
        $this->failuresDir = "{$this->testDir}/failures";
        $this->completeDir = "{$this->testDir}/complete";
        $this->testLogFile = "{$this->testDir}/test-run-{$this->timestamp}.log";

        // Create directories
        File::makeDirectory($this->testDir, 0755, true, true);
        File::makeDirectory($this->resultsDir, 0755, true, true);
        File::makeDirectory($this->failuresDir, 0755, true, true);
        File::makeDirectory($this->completeDir, 0755, true, true);
    }

    protected function runHealthCheck()
    {
        $this->log("Running health check...");
        
        $exitCode = Artisan::call('codespaces:health-check');
        
        if ($exitCode !== 0) {
            $this->log("❌ Health check failed - aborting test run");
            $this->moveLogToFailures();
            return 1;
        }

        $this->log("✅ Health check passed - proceeding with tests");
        return 0;
    }

    protected function runTests()
    {
        $this->log("Running Laravel tests...");
        
        $testResult = Artisan::call('test', [
            '--log-junit' => "{$this->resultsDir}/test-results-{$this->timestamp}.xml"
        ]);

        if ($testResult !== 0) {
            $this->log("❌ Tests failed");
            $this->moveLogToFailures();
            $this->generateFailureReport($testResult);
            return 1;
        }

        $this->generateCompletionReport($testResult);
        $this->moveLogToComplete();
        $this->log("✅ All tests completed successfully");
        return 0;
    }

    protected function log($message)
    {
        $logMessage = now()->format('Y-m-d H:i:s') . ": {$message}";
        File::append($this->testLogFile, $logMessage . PHP_EOL);
        $this->info($logMessage);
    }

    protected function moveLogToFailures()
    {
        File::move(
            $this->testLogFile,
            "{$this->failuresDir}/test-run-{$this->timestamp}.log"
        );
    }

    protected function moveLogToComplete()
    {
        File::move(
            $this->testLogFile,
            "{$this->completeDir}/test-run-{$this->timestamp}.log"
        );
    }

    protected function generateFailureReport($testResult)
    {
        $report = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'status' => 'failed',
            'testResults' => $testResult
        ];

        File::put(
            "{$this->failuresDir}/test-failure-{$this->timestamp}.json",
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }

    protected function generateCompletionReport($testResult)
    {
        $report = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'status' => 'completed',
            'testResults' => $testResult
        ];

        File::put(
            "{$this->completeDir}/test-completion-{$this->timestamp}.json",
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
} 