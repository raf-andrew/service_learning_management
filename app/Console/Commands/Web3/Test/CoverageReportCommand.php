<?php

namespace App\Console\Commands\Web3\Test;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class CoverageReportCommand extends Command
{
    protected $signature = 'web3:test:coverage
                          {action : Action to perform (generate|show|compare)}
                          {--type=all : Type of coverage to generate (all|unit|integration|e2e)}
                          {--format=html : Report format (html|json|lcov)}
                          {--threshold=80 : Coverage threshold percentage}
                          {--compare : Compare with previous coverage}';

    protected $description = 'Manage test coverage reports';

    protected $web3Path;
    protected $coveragePath;
    protected $coverageTypes = ['unit', 'integration', 'e2e'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->coveragePath = base_path('storage/coverage');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create coverage directory if it doesn't exist
        if (!File::exists($this->coveragePath)) {
            File::makeDirectory($this->coveragePath, 0755, true);
        }

        $action = $this->argument('action');
        $type = $this->option('type');
        $format = $this->option('format');
        $threshold = $this->option('threshold');
        $compare = $this->option('compare');

        switch ($action) {
            case 'generate':
                return $this->generateCoverage($type, $format, $threshold);
            case 'show':
                return $this->showCoverage($type, $format);
            case 'compare':
                return $this->compareCoverage($type, $format, $threshold);
            default:
                $this->error('Invalid action specified');
                return 1;
        }
    }

    protected function generateCoverage($type, $format, $threshold)
    {
        $this->info('Generating test coverage report...');
        $this->info("Type: {$type}");
        $this->info("Format: {$format}");
        $this->info("Threshold: {$threshold}%");

        $types = $type === 'all' ? $this->coverageTypes : [$type];
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $coverageResults = [];

        foreach ($types as $coverageType) {
            $result = $this->generateCoverageForType($coverageType, $format, $threshold, $timestamp);
            $coverageResults[$coverageType] = $result;
        }

        $this->generateCoverageSummary($coverageResults, $timestamp);

        $this->info('Coverage report generation completed');
        return 0;
    }

    protected function generateCoverageForType($type, $format, $threshold, $timestamp)
    {
        $this->info("\nGenerating {$type} coverage...");

        $outputPath = "{$this->coveragePath}/{$timestamp}/{$type}";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute coverage script
        $command = "cd {$this->web3Path} && npx hardhat coverage " .
                  "--testfiles test/{$type}/**/*.js " .
                  "--format {$format} " .
                  "--output {$outputPath} " .
                  "--threshold {$threshold}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Coverage generation failed',
                'details' => $output
            ];
        }

        return [
            'success' => true,
            'path' => $outputPath,
            'timestamp' => $timestamp
        ];
    }

    protected function showCoverage($type, $format)
    {
        $this->info('Showing coverage report...');

        // Execute coverage summary script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-coverage-summary.js " .
                  "--type {$type} " .
                  "--format {$format}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to show coverage report');
            return 1;
        }

        // Parse and display coverage summary
        $summary = $this->parseCoverageSummary($output);
        $this->table(
            ['Type', 'Statements', 'Branches', 'Functions', 'Lines'],
            $summary
        );

        return 0;
    }

    protected function compareCoverage($type, $format, $threshold)
    {
        $this->info('Comparing coverage reports...');

        // Execute coverage comparison script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-coverage-summary.js " .
                  "--type {$type} " .
                  "--format {$format} " .
                  "--compare " .
                  "--threshold {$threshold}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to compare coverage reports');
            return 1;
        }

        // Parse and display comparison results
        $comparison = $this->parseCoverageComparison($output);
        $this->table(
            ['Type', 'Current', 'Previous', 'Change', 'Status'],
            $comparison
        );

        return 0;
    }

    protected function generateCoverageSummary($results, $timestamp)
    {
        $this->info('Generating coverage summary...');

        // Execute coverage summary script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-coverage-summary.js " .
                  "--timestamp {$timestamp}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to generate coverage summary');
            return false;
        }

        $this->info('Coverage summary generated successfully');
        return true;
    }

    protected function parseCoverageSummary($output)
    {
        $summary = [];
        $currentType = null;

        foreach ($output as $line) {
            if (preg_match('/Type: (.+)/', $line, $matches)) {
                if ($currentType) {
                    $summary[] = $currentType;
                }
                $currentType = [
                    'type' => $matches[1],
                    'statements' => '',
                    'branches' => '',
                    'functions' => '',
                    'lines' => ''
                ];
            } elseif ($currentType) {
                if (preg_match('/Statements: (.+)/', $line, $matches)) {
                    $currentType['statements'] = $matches[1];
                } elseif (preg_match('/Branches: (.+)/', $line, $matches)) {
                    $currentType['branches'] = $matches[1];
                } elseif (preg_match('/Functions: (.+)/', $line, $matches)) {
                    $currentType['functions'] = $matches[1];
                } elseif (preg_match('/Lines: (.+)/', $line, $matches)) {
                    $currentType['lines'] = $matches[1];
                }
            }
        }

        if ($currentType) {
            $summary[] = $currentType;
        }

        return $summary;
    }

    protected function parseCoverageComparison($output)
    {
        $comparison = [];
        $currentType = null;

        foreach ($output as $line) {
            if (preg_match('/Type: (.+)/', $line, $matches)) {
                if ($currentType) {
                    $comparison[] = $currentType;
                }
                $currentType = [
                    'type' => $matches[1],
                    'current' => '',
                    'previous' => '',
                    'change' => '',
                    'status' => ''
                ];
            } elseif ($currentType) {
                if (preg_match('/Current: (.+)/', $line, $matches)) {
                    $currentType['current'] = $matches[1];
                } elseif (preg_match('/Previous: (.+)/', $line, $matches)) {
                    $currentType['previous'] = $matches[1];
                } elseif (preg_match('/Change: (.+)/', $line, $matches)) {
                    $currentType['change'] = $matches[1];
                } elseif (preg_match('/Status: (.+)/', $line, $matches)) {
                    $currentType['status'] = $matches[1];
                }
            }
        }

        if ($currentType) {
            $comparison[] = $currentType;
        }

        return $comparison;
    }
} 