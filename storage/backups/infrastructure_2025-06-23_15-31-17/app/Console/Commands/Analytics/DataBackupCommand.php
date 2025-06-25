<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataBackupCommand extends Command
{
    protected $signature = 'analytics:backup
                          {action : Action to perform (create|restore|list|clean)}
                          {--type=all : Type of data to backup (all|contract|transaction|event|metric)}
                          {--start= : Start date for backup (YYYY-MM-DD)}
                          {--end= : End date for backup (YYYY-MM-DD)}
                          {--destination= : Backup destination (local|s3|gcs)}
                          {--compress : Compress backup data}
                          {--encrypt : Encrypt backup data}
                          {--retention=30d : Backup retention period}
                          {--verify : Verify backup integrity}';

    protected $description = 'Manage analytics data backup and recovery';

    protected $web3Path;
    protected $analyticsPath;
    protected $backupPath;
    protected $dataTypes = ['contract', 'transaction', 'event', 'metric'];
    protected $destinations = ['local', 's3', 'gcs'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
        $this->backupPath = base_path('storage/analytics/backups');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create backup directory if it doesn't exist
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        $action = $this->argument('action');
        $type = $this->option('type');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(7);
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();
        $destination = $this->option('destination') ?? 'local';
        $compress = $this->option('compress');
        $encrypt = $this->option('encrypt');
        $retention = $this->option('retention');
        $verify = $this->option('verify');

        switch ($action) {
            case 'create':
                return $this->createBackup($type, $start, $end, $destination, $compress, $encrypt, $verify);
            case 'restore':
                return $this->restoreBackup($type, $start, $end, $destination);
            case 'list':
                return $this->listBackups($destination);
            case 'clean':
                return $this->cleanBackups($retention, $destination);
            default:
                $this->error('Invalid action specified');
                return 1;
        }
    }

    protected function createBackup($type, $start, $end, $destination, $compress, $encrypt, $verify)
    {
        $this->info('Creating analytics data backup...');
        $this->info("Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')}");
        $this->info("Destination: {$destination}");

        $types = $type === 'all' ? $this->dataTypes : [$type];
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupResults = [];

        foreach ($types as $dataType) {
            $result = $this->backupDataType($dataType, $start, $end, $destination, $compress, $encrypt, $timestamp);
            $backupResults[$dataType] = $result;
        }

        if ($verify) {
            $this->verifyBackup($backupResults, $destination, $timestamp);
        }

        $this->info('Backup completed successfully');
        return 0;
    }

    protected function backupDataType($type, $start, $end, $destination, $compress, $encrypt, $timestamp)
    {
        $this->info("\nBacking up {$type} data...");

        $outputPath = "{$this->backupPath}/{$timestamp}/{$type}";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute backup script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/backup-{$type}-data.js " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
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
            return [
                'success' => false,
                'message' => 'Backup failed',
                'details' => $output
            ];
        }

        return [
            'success' => true,
            'path' => $outputPath,
            'timestamp' => $timestamp
        ];
    }

    protected function restoreBackup($type, $start, $end, $destination)
    {
        $this->info('Restoring analytics data backup...');

        $types = $type === 'all' ? $this->dataTypes : [$type];
        $restoreResults = [];

        foreach ($types as $dataType) {
            $result = $this->restoreDataType($dataType, $start, $end, $destination);
            $restoreResults[$dataType] = $result;
        }

        $this->info('Restore completed successfully');
        return 0;
    }

    protected function restoreDataType($type, $start, $end, $destination)
    {
        $this->info("\nRestoring {$type} data...");

        // Execute restore script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/restore-{$type}-data.js " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--destination {$destination}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Restore failed',
                'details' => $output
            ];
        }

        return [
            'success' => true,
            'message' => 'Restore completed'
        ];
    }

    protected function listBackups($destination)
    {
        $this->info('Listing available backups...');

        // Execute list script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/list-backups.js " .
                  "--destination {$destination}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to list backups');
            return 1;
        }

        // Parse and display backup list
        $backups = $this->parseBackupList($output);
        $this->table(
            ['Timestamp', 'Type', 'Size', 'Status'],
            $backups
        );

        return 0;
    }

    protected function cleanBackups($retention, $destination)
    {
        $this->info('Cleaning up old backups...');

        // Execute cleanup script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/clean-backups.js " .
                  "--retention {$retention} " .
                  "--destination {$destination}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to clean backups');
            return 1;
        }

        $this->info('Backup cleanup completed');
        return 0;
    }

    protected function verifyBackup($results, $destination, $timestamp)
    {
        $this->info('Verifying backup integrity...');

        // Execute verification script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/verify-backup.js " .
                  "--destination {$destination} " .
                  "--timestamp {$timestamp}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Backup verification failed');
            return false;
        }

        $this->info('Backup verification completed successfully');
        return true;
    }

    protected function parseBackupList($output)
    {
        $backups = [];
        $currentBackup = null;

        foreach ($output as $line) {
            if (preg_match('/Backup: (.+)/', $line, $matches)) {
                if ($currentBackup) {
                    $backups[] = $currentBackup;
                }
                $currentBackup = [
                    'timestamp' => $matches[1],
                    'type' => '',
                    'size' => '',
                    'status' => ''
                ];
            } elseif ($currentBackup) {
                if (preg_match('/Type: (.+)/', $line, $matches)) {
                    $currentBackup['type'] = $matches[1];
                } elseif (preg_match('/Size: (.+)/', $line, $matches)) {
                    $currentBackup['size'] = $matches[1];
                } elseif (preg_match('/Status: (.+)/', $line, $matches)) {
                    $currentBackup['status'] = $matches[1];
                }
            }
        }

        if ($currentBackup) {
            $backups[] = $currentBackup;
        }

        return $backups;
    }
} 