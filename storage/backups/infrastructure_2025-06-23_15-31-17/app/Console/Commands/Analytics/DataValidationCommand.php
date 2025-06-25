<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataValidationCommand extends Command
{
    protected $signature = 'analytics:validate
                          {--type=all : Type of data to validate (all|contract|transaction|event|metric)}
                          {--start= : Start date for validation (YYYY-MM-DD)}
                          {--end= : End date for validation (YYYY-MM-DD)}
                          {--rules= : Custom validation rules file}
                          {--fix : Automatically fix validation issues}
                          {--report : Generate validation report}
                          {--threshold=90 : Minimum validation score (0-100)}';

    protected $description = 'Validate and ensure quality of analytics data';

    protected $web3Path;
    protected $analyticsPath;
    protected $validationPath;
    protected $dataTypes = ['contract', 'transaction', 'event', 'metric'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
        $this->validationPath = base_path('storage/analytics/validation');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create validation directory if it doesn't exist
        if (!File::exists($this->validationPath)) {
            File::makeDirectory($this->validationPath, 0755, true);
        }

        $type = $this->option('type');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(7);
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();
        $rules = $this->option('rules');
        $fix = $this->option('fix');
        $report = $this->option('report');
        $threshold = $this->option('threshold');

        $this->info('Starting analytics data validation...');
        $this->info("Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')}");
        $this->info("Threshold: {$threshold}%");

        $types = $type === 'all' ? $this->dataTypes : [$type];
        $validationResults = [];

        foreach ($types as $dataType) {
            $result = $this->validateDataType($dataType, $start, $end, $rules, $fix);
            $validationResults[$dataType] = $result;
        }

        // Generate validation report if requested
        if ($report) {
            $this->generateValidationReport($validationResults, $threshold);
        }

        // Check if any validation failed below threshold
        $failedValidations = array_filter($validationResults, function($result) use ($threshold) {
            return $result['score'] < $threshold;
        });

        if (!empty($failedValidations)) {
            $this->error('Some data types failed validation:');
            foreach ($failedValidations as $type => $result) {
                $this->error("{$type}: {$result['score']}% - {$result['message']}");
            }
            return 1;
        }

        $this->info('All data passed validation');
        return 0;
    }

    protected function validateDataType($type, $start, $end, $rules, $fix)
    {
        $this->info("\nValidating {$type} data...");

        $outputPath = "{$this->validationPath}/{$type}";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute validation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/validate-{$type}-data.js " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--output {$outputPath}";

        if ($rules) {
            $command .= " --rules {$rules}";
        }

        if ($fix) {
            $command .= ' --fix';
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'score' => 0,
                'message' => 'Validation failed',
                'details' => $output
            ];
        }

        // Parse validation results
        $result = $this->parseValidationResults($output);

        $this->info("{$type} data validation completed: {$result['score']}%");
        return $result;
    }

    protected function parseValidationResults($output)
    {
        // Parse the output to extract validation score and details
        $score = 0;
        $message = '';
        $details = [];

        foreach ($output as $line) {
            if (preg_match('/Score: (\d+)%/', $line, $matches)) {
                $score = (int)$matches[1];
            } elseif (preg_match('/Message: (.+)/', $line, $matches)) {
                $message = $matches[1];
            } elseif (preg_match('/Detail: (.+)/', $line, $matches)) {
                $details[] = $matches[1];
            }
        }

        return [
            'score' => $score,
            'message' => $message,
            'details' => $details
        ];
    }

    protected function generateValidationReport($results, $threshold)
    {
        $this->info('Generating validation report...');

        $reportPath = "{$this->validationPath}/report";
        if (!File::exists($reportPath)) {
            File::makeDirectory($reportPath, 0755, true);
        }

        // Execute report generation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-validation-report.js " .
                  "--results " . json_encode($results) . " " .
                  "--threshold {$threshold} " .
                  "--output {$reportPath}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to generate validation report');
            return;
        }

        $this->info('Validation report generated successfully');
    }
} 