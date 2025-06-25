<?php

namespace App\Console\Commands\.codespaces;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RunCodespacesTests extends Command
{
    protected $signature = 'codespaces:test {--filter=} {--group=}';
    protected $description = 'Run tests in Codespaces environment';

    protected $testEnv = [
        'APP_ENV' => 'codespaces',
        'APP_DEBUG' => 'true',
        'LOG_CHANNEL' => 'codespaces',
        'LOG_LEVEL' => 'debug',
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => 'mysql',
        'DB_PORT' => '3306',
        'DB_DATABASE' => 'service_learning_test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => 'root',
        'CACHE_DRIVER' => 'redis',
        'QUEUE_CONNECTION' => 'redis',
        'SESSION_DRIVER' => 'redis',
        'REDIS_HOST' => 'redis',
        'REDIS_PORT' => '6379',
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => 'mailhog',
        'MAIL_PORT' => '1025',
        'CODESPACES_ENABLED' => 'true',
        'CODESPACES_DB_HOST' => 'mysql',
        'CODESPACES_DB_PORT' => '3306',
        'CODESPACES_DB_DATABASE' => 'service_learning_test',
        'CODESPACES_DB_USERNAME' => 'root',
        'CODESPACES_DB_PASSWORD' => 'root',
        'CODESPACES_REDIS_HOST' => 'redis',
        'CODESPACES_REDIS_PORT' => '6379',
        'CODESPACES_MAIL_HOST' => 'mailhog',
        'CODESPACES_MAIL_PORT' => '1025',
    ];

    public function handle()
    {
        // Set environment variables
        foreach ($this->testEnv as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        // Load test configuration
        Config::set('codespaces', require config_path('codespaces.testing.php'));

        if (!Config::get('codespaces.enabled')) {
            $this->error('Codespaces is not enabled');
            return 1;
        }

        $timestamp = now()->format('Ymd-His');
        $reportPath = Config::get('codespaces.testing.report_path');
        $logPath = Config::get('codespaces.testing.log_path');
        
        // Ensure directories exist
        File::makeDirectory($reportPath, 0755, true, true);
        File::makeDirectory($logPath, 0755, true, true);

        $reportFile = "{$reportPath}/test-report-{$timestamp}.md";
        $logFile = "{$logPath}/test-{$timestamp}.log";

        // Build test command with environment variables
        $envVars = '';
        foreach ($this->testEnv as $key => $value) {
            $envVars .= "{$key}={$value} ";
        }

        $command = "{$envVars}php artisan test --env=codespaces";
        if ($filter = $this->option('filter')) {
            $command .= " --filter={$filter}";
        }
        if ($group = $this->option('group')) {
            $command .= " --group={$group}";
        }

        // Run tests and capture output
        $process = proc_open(
            $command,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );

        $output = '';
        while (($line = fgets($pipes[1])) !== false) {
            $output .= $line;
            $this->line($line);
        }
        while (($line = fgets($pipes[2])) !== false) {
            $output .= $line;
            $this->error($line);
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        $returnCode = proc_close($process);

        // Generate report
        $report = $this->generateReport($output, $returnCode);
        File::put($reportFile, $report);
        File::put($logFile, $output);

        // Process test results
        $this->processTestResults($logFile, $reportFile);

        return $returnCode;
    }

    protected function generateReport($output, $returnCode)
    {
        $report = "# Test Report\n";
        $report .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        $report .= "## Test Results\n";
        $report .= "- Status: " . ($returnCode === 0 ? "Passed" : "Failed") . "\n";
        $report .= "- Return Code: {$returnCode}\n\n";
        
        $report .= "## Output\n";
        $report .= "```\n{$output}\n```\n";
        
        return $report;
    }

    protected function processTestResults($logFile, $reportFile)
    {
        $completePath = Config::get('codespaces.testing.complete_path');
        $failuresPath = Config::get('codespaces.testing.failures_path');
        
        File::makeDirectory($completePath, 0755, true, true);
        File::makeDirectory($failuresPath, 0755, true, true);

        $content = File::get($logFile);
        $timestamp = now()->format('Ymd-His');

        if (Str::contains($content, 'PASSED')) {
            $completeFile = "{$completePath}/test-{$timestamp}.complete";
            File::move($logFile, $completeFile);
            $this->updateChecklist($completeFile, $reportFile);
        } else {
            $failureFile = "{$failuresPath}/test-{$timestamp}.failure";
            File::move($logFile, $failureFile);
            $this->updateChecklist($failureFile, $reportFile, false);
        }
    }

    protected function updateChecklist($file, $reportFile, $success = true)
    {
        $checklistFile = '.codespaces/testing/.test/checklist-tracking.json';
        
        if (!File::exists($checklistFile)) {
            $checklist = ['items' => []];
        } else {
            $checklist = json_decode(File::get($checklistFile), true);
        }

        $checklist['items'][] = [
            'test_file' => $file,
            'report_file' => $reportFile,
            'status' => $success ? 'completed' : 'failed',
            'timestamp' => now()->toIso8601String()
        ];

        File::put($checklistFile, json_encode($checklist, JSON_PRETTY_PRINT));
    }
} 