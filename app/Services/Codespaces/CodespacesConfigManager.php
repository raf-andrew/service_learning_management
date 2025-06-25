<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CodespacesConfigManager
{
    protected $configPath;
    protected $activeMode = 'local';
    protected $serviceConfigs = [];
    protected $localConfigs = [];

    public function __construct()
    {
        $this->configPath = base_path('.codespaces/config');
        $this->loadConfigurations();
    }

    protected function loadConfigurations()
    {
        if (!File::exists($this->configPath)) {
            File::makeDirectory($this->configPath, 0755, true, true);
        }

        // Load Codespaces configurations
        $codespacesConfigs = File::glob($this->configPath . '/services/*.json');
        foreach ($codespacesConfigs as $file) {
            $serviceName = basename($file, '.json');
            $this->serviceConfigs[$serviceName] = json_decode(File::get($file), true);
        }

        // Load local configurations
        $localConfigs = File::glob($this->configPath . '/local/*.json');
        foreach ($localConfigs as $file) {
            $serviceName = basename($file, '.json');
            $this->localConfigs[$serviceName] = json_decode(File::get($file), true);
        }
    }

    public function setMode(string $mode): void
    {
        if (!in_array($mode, ['local', 'codespaces'])) {
            throw new \InvalidArgumentException('Invalid mode. Must be either "local" or "codespaces"');
        }
        $this->activeMode = $mode;
    }

    public function getMode(): string
    {
        return $this->activeMode;
    }

    public function getServiceConfig(string $serviceName): ?array
    {
        if ($this->activeMode === 'codespaces') {
            return $this->serviceConfigs[$serviceName] ?? null;
        }
        return $this->localConfigs[$serviceName] ?? null;
    }

    public function saveServiceConfig(string $serviceName, array $config, string $mode = 'codespaces'): void
    {
        $targetPath = $this->configPath . '/' . $mode . '/' . $serviceName . '.json';
        File::ensureDirectoryExists(dirname($targetPath));
        
        if ($mode === 'codespaces') {
            $this->serviceConfigs[$serviceName] = $config;
        } else {
            $this->localConfigs[$serviceName] = $config;
        }

        File::put($targetPath, json_encode($config, JSON_PRETTY_PRINT));
    }

    public function applyServiceConfig(string $serviceName): void
    {
        $config = $this->getServiceConfig($serviceName);
        if (!$config) {
            return;
        }

        foreach ($config['env'] as $key => $value) {
            Config::set($key, $value);
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public function removeServiceConfig(string $serviceName): void
    {
        if ($this->activeMode === 'codespaces') {
            unset($this->serviceConfigs[$serviceName]);
            File::delete($this->configPath . '/services/' . $serviceName . '.json');
        } else {
            unset($this->localConfigs[$serviceName]);
            File::delete($this->configPath . '/local/' . $serviceName . '.json');
        }
    }

    public function getActiveServices(): array
    {
        return $this->activeMode === 'codespaces' 
            ? array_keys($this->serviceConfigs)
            : array_keys($this->localConfigs);
    }

    public function exportConfigurations(): array
    {
        return [
            'mode' => $this->activeMode,
            'services' => $this->activeMode === 'codespaces' 
                ? $this->serviceConfigs 
                : $this->localConfigs
        ];
    }

    public function importConfigurations(array $config): void
    {
        if (isset($config['mode'])) {
            $this->setMode($config['mode']);
        }

        if (isset($config['services'])) {
            foreach ($config['services'] as $serviceName => $serviceConfig) {
                $this->saveServiceConfig($serviceName, $serviceConfig, $this->activeMode);
            }
        }
    }
} 