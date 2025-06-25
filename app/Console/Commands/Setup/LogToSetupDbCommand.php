<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LogToSetupDbCommand extends Command
{
    protected $signature = 'setup:log 
                            {level : Log level (INFO, WARN, ERROR, SUCCESS)}
                            {message : Log message}';

    protected $description = 'Log messages to setup database';

    public function handle()
    {
        $dbPath = base_path('.setup/setup.sqlite');
        
        if (!File::exists($dbPath)) {
            $this->error('Setup database not found');
            return 1;
        }

        try {
            config(['database.connections.setup' => [
                'driver' => 'sqlite',
                'database' => $dbPath,
                'prefix' => '',
            ]]);

            DB::connection('setup')->table('logs')->insert([
                'timestamp' => now(),
                'level' => $this->argument('level'),
                'message' => $this->argument('message')
            ]);

            $this->info('Log entry created successfully');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create log entry: ' . $e->getMessage());
            return 1;
        }
    }
} 