<?php

namespace App\Services;

use App\Models\Environment;
use Illuminate\Support\Facades\Log;

class EnvironmentService
{
    public function createEnvironment(string $name, array $config): Environment
    {
        if (Environment::where('name', $name)->exists()) {
            throw new \Exception("Environment {$name} already exists");
        }

        try {
            $environment = Environment::create([
                'name' => $name,
                'branch' => $config['branch'],
                'url' => $config['url'],
                'variables' => $config['variables'],
                'status' => 'ready'
            ]);

            Log::info('Environment created successfully', [
                'environment' => $name,
                'config' => $config
            ]);

            return $environment;
        } catch (\Exception $e) {
            Log::error('Environment creation failed', [
                'environment' => $name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function updateEnvironment(string $name, array $config): Environment
    {
        $environment = Environment::where('name', $name)->firstOrFail();

        try {
            $environment->update([
                'branch' => $config['branch'] ?? $environment->branch,
                'url' => $config['url'] ?? $environment->url,
                'variables' => $config['variables'] ?? $environment->variables
            ]);

            Log::info('Environment updated successfully', [
                'environment' => $name,
                'config' => $config
            ]);

            return $environment;
        } catch (\Exception $e) {
            Log::error('Environment update failed', [
                'environment' => $name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function deleteEnvironment(string $name): bool
    {
        $environment = Environment::where('name', $name)->firstOrFail();

        try {
            $environment->delete();

            Log::info('Environment deleted successfully', [
                'environment' => $name
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Environment deletion failed', [
                'environment' => $name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function validateEnvironment(string $name): bool
    {
        $environment = Environment::where('name', $name)->firstOrFail();

        try {
            // Validate environment configuration
            $this->validateEnvironmentConfig($environment);

            // Validate environment connectivity
            $this->validateEnvironmentConnectivity($environment);

            Log::info('Environment validation successful', [
                'environment' => $name
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Environment validation failed', [
                'environment' => $name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function validateEnvironmentConfig(Environment $environment): void
    {
        // Validate required environment variables
        $requiredVariables = ['APP_ENV', 'APP_DEBUG'];
        $missingVariables = array_diff($requiredVariables, array_keys($environment->variables));

        if (!empty($missingVariables)) {
            throw new \Exception('Missing required environment variables: ' . implode(', ', $missingVariables));
        }

        // Validate branch configuration
        if (empty($environment->branch)) {
            throw new \Exception('Branch configuration is required');
        }

        // Validate URL configuration
        if (empty($environment->url) || !filter_var($environment->url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Valid URL configuration is required');
        }
    }

    protected function validateEnvironmentConnectivity(Environment $environment): void
    {
        // Implement environment connectivity validation
        // This would include:
        // 1. SSH connectivity
        // 2. Database connectivity
        // 3. Web server accessibility
        // 4. Required service availability
    }
} 