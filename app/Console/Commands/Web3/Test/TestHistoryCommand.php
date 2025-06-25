<?php

namespace App\Console\Commands\Web3\Test;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class TestHistoryCommand extends Command
{
    protected $signature = 'web3:test:history
                          {action : Action to perform (list|show|compare|clean)}
                          {--type=all : Type of test history (all|unit|integration|e2e)}
                          {--start= : Start date for history (YYYY-MM-DD)}
                          {--end= : End date for history (YYYY-MM-DD)}
                          {--format=json : Output format (json|table|csv)}
                          {--limit=10 : Number of entries to show}
                          {--filter= : Filter results by status or name}';

    protected $description = 'Manage test history and results';

    protected $web3Path;
    protected $historyPath;
    protected $testTypes = ['unit', 'integration', 'e2e'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->historyPath = base_path('storage/test-history');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create history directory if it doesn't exist
        if (!File::exists($this->historyPath)) {
            File::makeDirectory($this->historyPath, 0755, true);
        }

        $action = $this->argument('action');
        $type = $this->option('type');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(30);
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();
        $format = $this->option('format');
        $limit = $this->option('limit');
        $filter = $this->option('filter');

        switch ($action) {
            case 'list':
                return $this->listHistory($type, $start, $end, $format, $limit, $filter);
            case 'show':
                return $this->showHistory($type, $start, $end, $format, $limit, $filter);
            case 'compare':
                return $this->compareHistory($type, $start, $end, $format);
            case 'clean':
                return $this->cleanHistory($type, $start, $end);
            default:
                $this->error('Invalid action specified');
                return 1;
        }
    }

    protected function listHistory($type, $start, $end, $format, $limit, $filter)
    {
        $this->info('Listing test history...');
        $this->info("Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')}");
        $this->info("Type: {$type}");
        $this->info("Format: {$format}");
        $this->info("Limit: {$limit}");

        // Execute history script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/test-history.js " .
                  "--action list " .
                  "--type {$type} " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--format {$format} " .
                  "--limit {$limit}";

        if ($filter) {
            $command .= " --filter {$filter}";
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to list test history');
            return 1;
        }

        // Parse and display history
        $history = $this->parseHistoryOutput($output, $format);
        $this->displayHistory($history, $format);

        return 0;
    }

    protected function showHistory($type, $start, $end, $format, $limit, $filter)
    {
        $this->info('Showing detailed test history...');

        // Execute history script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/test-history.js " .
                  "--action show " .
                  "--type {$type} " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--format {$format} " .
                  "--limit {$limit}";

        if ($filter) {
            $command .= " --filter {$filter}";
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to show test history');
            return 1;
        }

        // Parse and display detailed history
        $history = $this->parseHistoryOutput($output, $format);
        $this->displayDetailedHistory($history, $format);

        return 0;
    }

    protected function compareHistory($type, $start, $end, $format)
    {
        $this->info('Comparing test history...');

        // Execute history script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/test-history.js " .
                  "--action compare " .
                  "--type {$type} " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--format {$format}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to compare test history');
            return 1;
        }

        // Parse and display comparison
        $comparison = $this->parseComparisonOutput($output, $format);
        $this->displayComparison($comparison, $format);

        return 0;
    }

    protected function cleanHistory($type, $start, $end)
    {
        $this->info('Cleaning test history...');

        // Execute history script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/test-history.js " .
                  "--action clean " .
                  "--type {$type} " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to clean test history');
            return 1;
        }

        $this->info('Test history cleaned successfully');
        return 0;
    }

    protected function parseHistoryOutput($output, $format)
    {
        if ($format === 'json') {
            return json_decode(implode("\n", $output), true);
        }

        $history = [];
        $currentEntry = null;

        foreach ($output as $line) {
            if (preg_match('/Test: (.+)/', $line, $matches)) {
                if ($currentEntry) {
                    $history[] = $currentEntry;
                }
                $currentEntry = [
                    'test' => $matches[1],
                    'type' => '',
                    'status' => '',
                    'date' => '',
                    'duration' => '',
                    'details' => []
                ];
            } elseif ($currentEntry) {
                if (preg_match('/Type: (.+)/', $line, $matches)) {
                    $currentEntry['type'] = $matches[1];
                } elseif (preg_match('/Status: (.+)/', $line, $matches)) {
                    $currentEntry['status'] = $matches[1];
                } elseif (preg_match('/Date: (.+)/', $line, $matches)) {
                    $currentEntry['date'] = $matches[1];
                } elseif (preg_match('/Duration: (.+)/', $line, $matches)) {
                    $currentEntry['duration'] = $matches[1];
                } elseif (preg_match('/Detail: (.+)/', $line, $matches)) {
                    $currentEntry['details'][] = $matches[1];
                }
            }
        }

        if ($currentEntry) {
            $history[] = $currentEntry;
        }

        return $history;
    }

    protected function parseComparisonOutput($output, $format)
    {
        if ($format === 'json') {
            return json_decode(implode("\n", $output), true);
        }

        $comparison = [];
        $currentEntry = null;

        foreach ($output as $line) {
            if (preg_match('/Test: (.+)/', $line, $matches)) {
                if ($currentEntry) {
                    $comparison[] = $currentEntry;
                }
                $currentEntry = [
                    'test' => $matches[1],
                    'type' => '',
                    'current_status' => '',
                    'previous_status' => '',
                    'change' => '',
                    'trend' => ''
                ];
            } elseif ($currentEntry) {
                if (preg_match('/Type: (.+)/', $line, $matches)) {
                    $currentEntry['type'] = $matches[1];
                } elseif (preg_match('/Current Status: (.+)/', $line, $matches)) {
                    $currentEntry['current_status'] = $matches[1];
                } elseif (preg_match('/Previous Status: (.+)/', $line, $matches)) {
                    $currentEntry['previous_status'] = $matches[1];
                } elseif (preg_match('/Change: (.+)/', $line, $matches)) {
                    $currentEntry['change'] = $matches[1];
                } elseif (preg_match('/Trend: (.+)/', $line, $matches)) {
                    $currentEntry['trend'] = $matches[1];
                }
            }
        }

        if ($currentEntry) {
            $comparison[] = $currentEntry;
        }

        return $comparison;
    }

    protected function displayHistory($history, $format)
    {
        if ($format === 'json') {
            $this->line(json_encode($history, JSON_PRETTY_PRINT));
            return;
        }

        if ($format === 'csv') {
            $this->table(
                ['Test', 'Type', 'Status', 'Date', 'Duration'],
                array_map(function ($entry) {
                    return [
                        $entry['test'],
                        $entry['type'],
                        $entry['status'],
                        $entry['date'],
                        $entry['duration']
                    ];
                }, $history)
            );
            return;
        }

        foreach ($history as $entry) {
            $this->info("\nTest: {$entry['test']}");
            $this->line("Type: {$entry['type']}");
            $this->line("Status: {$entry['status']}");
            $this->line("Date: {$entry['date']}");
            $this->line("Duration: {$entry['duration']}");
            if (!empty($entry['details'])) {
                $this->line("\nDetails:");
                foreach ($entry['details'] as $detail) {
                    $this->line("- {$detail}");
                }
            }
        }
    }

    protected function displayDetailedHistory($history, $format)
    {
        if ($format === 'json') {
            $this->line(json_encode($history, JSON_PRETTY_PRINT));
            return;
        }

        if ($format === 'csv') {
            $this->table(
                ['Test', 'Type', 'Status', 'Date', 'Duration', 'Details'],
                array_map(function ($entry) {
                    return [
                        $entry['test'],
                        $entry['type'],
                        $entry['status'],
                        $entry['date'],
                        $entry['duration'],
                        implode("\n", $entry['details'])
                    ];
                }, $history)
            );
            return;
        }

        foreach ($history as $entry) {
            $this->info("\nTest: {$entry['test']}");
            $this->line("Type: {$entry['type']}");
            $this->line("Status: {$entry['status']}");
            $this->line("Date: {$entry['date']}");
            $this->line("Duration: {$entry['duration']}");
            if (!empty($entry['details'])) {
                $this->line("\nDetails:");
                foreach ($entry['details'] as $detail) {
                    $this->line("- {$detail}");
                }
            }
            $this->line('----------------------------------------');
        }
    }

    protected function displayComparison($comparison, $format)
    {
        if ($format === 'json') {
            $this->line(json_encode($comparison, JSON_PRETTY_PRINT));
            return;
        }

        if ($format === 'csv') {
            $this->table(
                ['Test', 'Type', 'Current Status', 'Previous Status', 'Change', 'Trend'],
                array_map(function ($entry) {
                    return [
                        $entry['test'],
                        $entry['type'],
                        $entry['current_status'],
                        $entry['previous_status'],
                        $entry['change'],
                        $entry['trend']
                    ];
                }, $comparison)
            );
            return;
        }

        foreach ($comparison as $entry) {
            $this->info("\nTest: {$entry['test']}");
            $this->line("Type: {$entry['type']}");
            $this->line("Current Status: {$entry['current_status']}");
            $this->line("Previous Status: {$entry['previous_status']}");
            $this->line("Change: {$entry['change']}");
            $this->line("Trend: {$entry['trend']}");
            $this->line('----------------------------------------');
        }
    }
} 