<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearSniffingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sniff:clear {--all} {--results} {--logs} {--database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear sniffing analysis data and logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clearAll = $this->option('all');
        $clearResults = $this->option('results') || $clearAll;
        $clearLogs = $this->option('logs') || $clearAll;
        $clearDatabase = $this->option('database') || $clearAll;

        if (!$clearAll && !$clearResults && !$clearLogs && !$clearDatabase) {
            $this->error('Please specify what to clear. Use --all or specific options.');
            return 1;
        }

        $this->info('Clearing sniffing data...');

        try {
            $cleared = [];

            if ($clearResults) {
                $this->clearResults();
                $cleared[] = 'results';
            }

            if ($clearLogs) {
                $this->clearLogs();
                $cleared[] = 'logs';
            }

            if ($clearDatabase) {
                $this->clearDatabase();
                $cleared[] = 'database';
            }

            $this->info('Successfully cleared: ' . implode(', ', $cleared));

        } catch (\Exception $e) {
            $this->error("Error clearing data: {$e->getMessage()}");
            Log::error('ClearSniffingDataCommand error', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Clear sniffing results
     */
    protected function clearResults(): void
    {
        $resultsPath = storage_path('app/sniffing/results');
        if (File::exists($resultsPath)) {
            $files = File::files($resultsPath);
            foreach ($files as $file) {
                File::delete($file->getPathname());
            }
            $this->info('Cleared ' . count($files) . ' result files');
        } else {
            $this->info('No results directory found');
        }
    }

    /**
     * Clear sniffing logs
     */
    protected function clearLogs(): void
    {
        $logsPath = storage_path('logs/sniffing');
        if (File::exists($logsPath)) {
            $files = File::files($logsPath);
            foreach ($files as $file) {
                File::delete($file->getPathname());
            }
            $this->info('Cleared ' . count($files) . ' log files');
        } else {
            $this->info('No sniffing logs directory found');
        }
    }

    /**
     * Clear sniffing database
     */
    protected function clearDatabase(): void
    {
        $dbPath = storage_path('database/sniffing.sqlite');
        if (File::exists($dbPath)) {
            File::delete($dbPath);
            $this->info('Cleared sniffing database');
        } else {
            $this->info('No sniffing database found');
        }
    }
} 