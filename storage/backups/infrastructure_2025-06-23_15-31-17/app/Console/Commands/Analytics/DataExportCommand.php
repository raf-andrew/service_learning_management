<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataExportCommand extends Command
{
    protected $signature = 'analytics:export
                          {--type=all : Type of data to export (all|contract|transaction|event|metric)}
                          {--start= : Start date for data export (YYYY-MM-DD)}
                          {--end= : End date for data export (YYYY-MM-DD)}
                          {--format=json : Export format (json|csv|excel|parquet)}
                          {--destination= : Export destination (local|s3|bigquery|snowflake)}
                          {--compress : Compress exported data}
                          {--encrypt : Encrypt exported data}';

    protected $description = 'Export analytics data to various destinations';

    protected $web3Path;
    protected $analyticsPath;
    protected $exportPath;
    protected $dataTypes = ['contract', 'transaction', 'event', 'metric'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
        $this->exportPath = base_path('storage/analytics/exports');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create export directory if it doesn't exist
        if (!File::exists($this->exportPath)) {
            File::makeDirectory($this->exportPath, 0755, true);
        }

        $type = $this->option('type');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(7);
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();
        $format = $this->option('format');
        $destination = $this->option('destination') ?? 'local';
        $compress = $this->option('compress');
        $encrypt = $this->option('encrypt');

        $this->info('Starting analytics data export...');
        $this->info("Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')}");
        $this->info("Format: {$format}");
        $this->info("Destination: {$destination}");

        $types = $type === 'all' ? $this->dataTypes : [$type];

        foreach ($types as $dataType) {
            $this->exportDataType($dataType, $start, $end, $format, $destination, $compress, $encrypt);
        }

        $this->info('Analytics data export completed');
        return 0;
    }

    protected function exportDataType($type, $start, $end, $format, $destination, $compress, $encrypt)
    {
        $this->info("\nExporting {$type} data...");

        $outputPath = "{$this->exportPath}/{$type}";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute data export script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/export-{$type}-data.js " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--format {$format} " .
                  "--destination {$destination} " .
                  "--output {$outputPath}";

        if ($compress) {
            $command .= ' --compress';
        }

        if ($encrypt) {
            $command .= ' --encrypt';
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Failed to export {$type} data");
            return;
        }

        // Verify export
        $this->verifyExport($type, $outputPath, $format, $destination);

        $this->info("{$type} data export completed");
    }

    protected function verifyExport($type, $path, $format, $destination)
    {
        $this->info("Verifying {$type} data export...");

        // Execute verification script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/verify-export.js " .
                  "--type {$type} " .
                  "--path {$path} " .
                  "--format {$format} " .
                  "--destination {$destination}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Failed to verify {$type} data export");
            return;
        }

        $this->info("{$type} data export verified successfully");
    }
} 