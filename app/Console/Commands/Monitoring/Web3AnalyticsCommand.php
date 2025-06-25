<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class Web3AnalyticsCommand extends Command
{
    protected $signature = 'analytics:web3
                          {action : The action to perform (collect|analyze|report|export)}
                          {--type= : Type of analytics data to process (contract|transaction|event)}
                          {--start= : Start date for data collection (YYYY-MM-DD)}
                          {--end= : End date for data collection (YYYY-MM-DD)}
                          {--format=json : Output format (json|csv|html)}
                          {--network= : Target network (mainnet|testnet|local)}';

    protected $description = 'Manage Web3 analytics data collection and analysis';

    protected $web3Path;
    protected $analyticsPath;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
    }

    public function handle()
    {
        $action = $this->argument('action');
        $type = $this->option('type');
        $start = $this->option('start');
        $end = $this->option('end');
        $format = $this->option('format');
        $network = $this->option('network');

        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create analytics directory if it doesn't exist
        if (!File::exists($this->analyticsPath)) {
            File::makeDirectory($this->analyticsPath, 0755, true);
        }

        switch ($action) {
            case 'collect':
                return $this->collectData($type, $start, $end, $network);
            case 'analyze':
                return $this->analyzeData($type, $start, $end, $format);
            case 'report':
                return $this->generateReport($type, $start, $end, $format);
            case 'export':
                return $this->exportData($type, $start, $end, $format);
            default:
                $this->error('Invalid action specified');
                return 1;
        }
    }

    protected function collectData($type, $start, $end, $network)
    {
        $this->info('Collecting Web3 analytics data...');

        // Execute Hardhat script for data collection
        $command = "cd {$this->web3Path} && npx hardhat run scripts/collect-analytics.js " .
                  "--network {$network} " .
                  "--type {$type} " .
                  "--start {$start} " .
                  "--end {$end}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to collect analytics data');
            return 1;
        }

        $this->info('Analytics data collected successfully');
        return 0;
    }

    protected function analyzeData($type, $start, $end, $format)
    {
        $this->info('Analyzing Web3 analytics data...');

        // Execute analysis script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/analyze-analytics.js " .
                  "--type {$type} " .
                  "--start {$start} " .
                  "--end {$end} " .
                  "--format {$format}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to analyze analytics data');
            return 1;
        }

        $this->info('Analytics data analyzed successfully');
        return 0;
    }

    protected function generateReport($type, $start, $end, $format)
    {
        $this->info('Generating Web3 analytics report...');

        // Execute report generation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/generate-analytics-report.js " .
                  "--type {$type} " .
                  "--start {$start} " .
                  "--end {$end} " .
                  "--format {$format}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to generate analytics report');
            return 1;
        }

        $this->info('Analytics report generated successfully');
        return 0;
    }

    protected function exportData($type, $start, $end, $format)
    {
        $this->info('Exporting Web3 analytics data...');

        // Execute export script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/export-analytics.js " .
                  "--type {$type} " .
                  "--start {$start} " .
                  "--end {$end} " .
                  "--format {$format}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to export analytics data');
            return 1;
        }

        $this->info('Analytics data exported successfully');
        return 0;
    }
} 