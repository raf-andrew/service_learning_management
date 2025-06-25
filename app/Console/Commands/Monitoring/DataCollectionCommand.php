<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataCollectionCommand extends Command
{
    protected $signature = 'analytics:collect
                          {--type=all : Type of data to collect (all|contract|transaction|event|metric)}
                          {--start= : Start date for data collection (YYYY-MM-DD)}
                          {--end= : End date for data collection (YYYY-MM-DD)}
                          {--network= : Target network (mainnet|testnet|local)}
                          {--batch=1000 : Batch size for data collection}
                          {--force : Force recollect existing data}';

    protected $description = 'Collect and process analytics data from Web3 infrastructure';

    protected $web3Path;
    protected $analyticsPath;
    protected $dataTypes = ['contract', 'transaction', 'event', 'metric'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create analytics directory if it doesn't exist
        if (!File::exists($this->analyticsPath)) {
            File::makeDirectory($this->analyticsPath, 0755, true);
        }

        $type = $this->option('type');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(7);
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();
        $network = $this->option('network') ?? 'mainnet';
        $batchSize = $this->option('batch');
        $force = $this->option('force');

        $this->info('Starting analytics data collection...');
        $this->info("Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')}");
        $this->info("Network: {$network}");
        $this->info("Batch size: {$batchSize}");

        $types = $type === 'all' ? $this->dataTypes : [$type];

        foreach ($types as $dataType) {
            $this->collectDataType($dataType, $start, $end, $network, $batchSize, $force);
        }

        $this->info('Analytics data collection completed');
        return 0;
    }

    protected function collectDataType($type, $start, $end, $network, $batchSize, $force)
    {
        $this->info("\nCollecting {$type} data...");

        $outputPath = "{$this->analyticsPath}/{$type}";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute data collection script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/collect-{$type}-data.js " .
                  "--network {$network} " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--batch {$batchSize} " .
                  "--output {$outputPath}";

        if ($force) {
            $command .= ' --force';
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Failed to collect {$type} data");
            return;
        }

        // Process collected data
        $this->processCollectedData($type, $outputPath);

        $this->info("{$type} data collection completed");
    }

    protected function processCollectedData($type, $path)
    {
        $this->info("Processing {$type} data...");

        // Execute data processing script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/process-{$type}-data.js " .
                  "--input {$path}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Failed to process {$type} data");
            return;
        }

        $this->info("{$type} data processing completed");
    }
} 