<?php

namespace App\Console\Commands\.environment;

use Illuminate\Console\Command;
use App\Models\EnvironmentVariable;
use Illuminate\Support\Facades\File;

class EnvRestore extends Command
{
    protected $signature = 'env:restore {--force : Force restore even if .env exists}';
    protected $description = 'Restore environment variables from database to .env file';

    public function handle()
    {
        $envPath = base_path('.env');

        if (File::exists($envPath) && !$this->option('force')) {
            if (!$this->confirm('The .env file already exists. Do you want to overwrite it?')) {
                return 1;
            }
        }

        $variables = EnvironmentVariable::all();
        $envContents = '';

        foreach ($variables as $variable) {
            $envContents .= "{$variable->key}={$variable->value}\n";
        }

        File::put($envPath, $envContents);
        $this->info('Environment variables restored successfully!');
        return 0;
    }
} 