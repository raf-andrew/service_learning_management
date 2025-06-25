<?php

namespace App\Console\Commands\.environment;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EnvSync extends Command
{
    protected $signature = 'env:sync {--force : Force sync even if .env exists}';
    protected $description = 'Sync environment variables between .env and database';

    public function handle()
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        // Create .env from .env.example if it doesn't exist
        if (!File::exists($envPath)) {
            if (!File::exists($envExamplePath)) {
                $this->error('.env.example file not found!');
                return 1;
            }
            File::copy($envExamplePath, $envPath);
            $this->info('Created .env from .env.example');
        }

        // Read current .env file
        $envContents = File::get($envPath);
        $envLines = explode("\n", $envContents);
        $envVars = [];

        foreach ($envLines as $line) {
            if (Str::startsWith($line, '#') || empty(trim($line))) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $envVars[$key] = $value;
            }
        }

        // Try to sync to database if EnvironmentVariable model exists
        try {
            if (class_exists('App\Models\EnvironmentVariable')) {
                $this->syncToDatabase($envVars);
            }
        } catch (\Exception $e) {
            $this->warn('Database sync skipped: ' . $e->getMessage());
        }

        $this->info('Environment variables synced successfully!');
        return 0;
    }

    private function syncToDatabase($envVars)
    {
        foreach ($envVars as $key => $value) {
            \App\Models\EnvironmentVariable::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $this->determineGroup($key),
                    'is_encrypted' => $this->shouldEncrypt($key),
                    'description' => $this->getEnvVarDescription($key)
                ]
            );
        }
    }

    private function determineGroup($key)
    {
        $groups = [
            'database' => ['DB_'],
            'mail' => ['MAIL_'],
            'queue' => ['QUEUE_'],
            'cache' => ['CACHE_'],
            'session' => ['SESSION_'],
            'filesystem' => ['FILESYSTEM_'],
            'services' => ['SERVICES_'],
            'app' => ['APP_'],
        ];

        foreach ($groups as $group => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (Str::startsWith($key, $prefix)) {
                    return $group;
                }
            }
        }

        return 'general';
    }

    private function shouldEncrypt($key)
    {
        $encryptedKeys = [
            'APP_KEY',
            'DB_PASSWORD',
            'MAIL_PASSWORD',
            'REDIS_PASSWORD',
            'AWS_SECRET_ACCESS_KEY',
        ];

        return in_array($key, $encryptedKeys);
    }

    public function getEnvVarDescription($key)
    {
        $descriptions = [
            'APP_NAME' => 'The name of your application',
            'APP_ENV' => 'The environment your application is running in',
            'APP_KEY' => 'The encryption key for your application',
            'APP_DEBUG' => 'Whether debug mode is enabled',
            'APP_URL' => 'The URL of your application',
            'DB_CONNECTION' => 'The database connection type',
            'DB_HOST' => 'The database host',
            'DB_PORT' => 'The database port',
            'DB_DATABASE' => 'The database name',
            'DB_USERNAME' => 'The database username',
            'DB_PASSWORD' => 'The database password',
        ];

        return $descriptions[$key] ?? null;
    }
} 