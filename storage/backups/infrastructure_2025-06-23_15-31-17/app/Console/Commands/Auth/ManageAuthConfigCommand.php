<?php

namespace App\Console\Commands\Auth;

class ManageAuthConfigCommand extends BaseAuthCommand
{
    protected $signature = 'auth:config
        {action : The action to perform (show|set|reset)}
        {--key= : Configuration key}
        {--value= : Configuration value}
        {--file= : Configuration file}';

    protected $description = 'Manage authentication configuration';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'show':
                return $this->showConfig();
            case 'set':
                return $this->setConfig();
            case 'reset':
                return $this->resetConfig();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function showConfig()
    {
        $key = $this->option('key');
        $file = $this->option('file');

        try {
            if ($key) {
                $value = $this->authService->getConfig($key);
                $this->info("{$key}: {$value}");
            } else {
                $config = $this->authService->getAllConfig($file);
                $this->table(
                    ['Key', 'Value'],
                    collect($config)->map(fn($value, $key) => [$key, $value])
                );
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to show config: {$e->getMessage()}");
            return 1;
        }
    }

    protected function setConfig()
    {
        $key = $this->option('key');
        $value = $this->option('value');
        $file = $this->option('file');

        if (!$key || !$value) {
            $this->error('Both key and value are required');
            return 1;
        }

        try {
            $this->authService->setConfig($key, $value, $file);
            $this->info("Configuration updated successfully: {$key} = {$value}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to set config: {$e->getMessage()}");
            return 1;
        }
    }

    protected function resetConfig()
    {
        $key = $this->option('key');
        $file = $this->option('file');

        if (!$key && !$file) {
            $this->error('Either key or file is required');
            return 1;
        }

        if ($this->confirm('Are you sure you want to reset this configuration?')) {
            try {
                if ($key) {
                    $this->authService->resetConfig($key);
                    $this->info("Configuration reset successfully: {$key}");
                } else {
                    $this->authService->resetConfigFile($file);
                    $this->info("Configuration file reset successfully: {$file}");
                }
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to reset config: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 