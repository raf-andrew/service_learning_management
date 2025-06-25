<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CodespacesServiceManager
{
    protected $configPath;
    protected $services = [];
    protected $activeServices = [];

    public function __construct()
    {
        $this->configPath = base_path('.codespaces/services');
        $this->loadServices();
    }

    protected function loadServices()
    {
        if (!File::exists($this->configPath)) {
            File::makeDirectory($this->configPath, 0755, true, true);
        }

        $serviceFiles = File::glob($this->configPath . '/*.json');
        foreach ($serviceFiles as $file) {
            $serviceName = basename($file, '.json');
            $this->services[$serviceName] = json_decode(File::get($file), true);
        }
    }

    public function getServiceConfig(string $serviceName): ?array
    {
        return $this->services[$serviceName] ?? null;
    }

    public function saveServiceConfig(string $serviceName, array $config): void
    {
        $this->services[$serviceName] = $config;
        File::put(
            $this->configPath . "/{$serviceName}.json",
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }

    public function activateService(string $serviceName): bool
    {
        if (!isset($this->services[$serviceName])) {
            return false;
        }

        $config = $this->services[$serviceName];
        foreach ($config['env'] as $key => $value) {
            Config::set($key, $value);
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->activeServices[$serviceName] = true;
        return true;
    }

    public function deactivateService(string $serviceName): void
    {
        if (isset($this->activeServices[$serviceName])) {
            $config = $this->services[$serviceName];
            foreach ($config['env'] as $key => $value) {
                Config::set($key, null);
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
            unset($this->activeServices[$serviceName]);
        }
    }

    public function isServiceActive(string $serviceName): bool
    {
        return isset($this->activeServices[$serviceName]);
    }

    public function getActiveServices(): array
    {
        return array_keys($this->activeServices);
    }

    public function getAllServices(): array
    {
        return array_keys($this->services);
    }

    public function createService(string $serviceName, array $config): void
    {
        $this->saveServiceConfig($serviceName, [
            'name' => $serviceName,
            'env' => $config,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function updateService(string $serviceName, array $config): void
    {
        if (isset($this->services[$serviceName])) {
            $existingConfig = $this->services[$serviceName];
            $existingConfig['env'] = array_merge($existingConfig['env'], $config);
            $existingConfig['updated_at'] = now()->toIso8601String();
            $this->saveServiceConfig($serviceName, $existingConfig);
        }
    }

    public function deleteService(string $serviceName): void
    {
        if (isset($this->services[$serviceName])) {
            $this->deactivateService($serviceName);
            File::delete($this->configPath . "/{$serviceName}.json");
            unset($this->services[$serviceName]);
        }
    }
} 