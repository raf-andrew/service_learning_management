<?php

namespace App\Console\Commands\Auth;

class ManageAuthProvidersCommand extends BaseAuthCommand
{
    protected $signature = 'auth:providers
        {action : The action to perform (list|enable|disable|configure)}
        {--provider= : Provider name}
        {--key= : Provider configuration key}
        {--value= : Provider configuration value}';

    protected $description = 'Manage authentication providers';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listProviders();
            case 'enable':
                return $this->enableProvider();
            case 'disable':
                return $this->disableProvider();
            case 'configure':
                return $this->configureProvider();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listProviders()
    {
        $providers = $this->authService->getAllProviders();
        
        $this->table(
            ['Provider', 'Status', 'Configuration'],
            $providers->map(fn($provider) => [
                $provider->name,
                $provider->enabled ? 'Enabled' : 'Disabled',
                json_encode($provider->config)
            ])
        );

        return 0;
    }

    protected function enableProvider()
    {
        $provider = $this->option('provider');
        if (!$provider) {
            $this->error('Provider name is required');
            return 1;
        }

        try {
            $this->authService->enableProvider($provider);
            $this->info("Provider {$provider} enabled successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to enable provider: {$e->getMessage()}");
            return 1;
        }
    }

    protected function disableProvider()
    {
        $provider = $this->option('provider');
        if (!$provider) {
            $this->error('Provider name is required');
            return 1;
        }

        try {
            $this->authService->disableProvider($provider);
            $this->info("Provider {$provider} disabled successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to disable provider: {$e->getMessage()}");
            return 1;
        }
    }

    protected function configureProvider()
    {
        $provider = $this->option('provider');
        $key = $this->option('key');
        $value = $this->option('value');

        if (!$provider || !$key || !$value) {
            $this->error('Provider name, key, and value are required');
            return 1;
        }

        try {
            $this->authService->configureProvider($provider, $key, $value);
            $this->info("Provider {$provider} configured successfully: {$key} = {$value}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to configure provider: {$e->getMessage()}");
            return 1;
        }
    }
} 