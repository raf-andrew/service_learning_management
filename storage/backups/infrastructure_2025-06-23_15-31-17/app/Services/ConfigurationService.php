<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;

class ConfigurationService
{
    /**
     * Cache key for configuration
     */
    private const CONFIG_CACHE_KEY = 'app_configuration';

    /**
     * Cache TTL in seconds
     */
    private const CONFIG_CACHE_TTL = 3600;

    /**
     * Get all configuration with inheritance
     */
    public function getAllConfiguration(): array
    {
        return Cache::remember(self::CONFIG_CACHE_KEY, self::CONFIG_CACHE_TTL, function () {
            return $this->buildConfiguration();
        });
    }

    /**
     * Get module configuration
     */
    public function getModuleConfiguration(string $moduleName): array
    {
        $config = $this->getAllConfiguration();
        
        return $config['modules'][$moduleName] ?? [];
    }

    /**
     * Get environment-specific configuration
     */
    public function getEnvironmentConfiguration(string $environment = null): array
    {
        $environment = $environment ?: app()->environment();
        $config = $this->getAllConfiguration();
        
        return $config['environments'][$environment] ?? [];
    }

    /**
     * Build complete configuration with inheritance
     */
    private function buildConfiguration(): array
    {
        $config = [
            'base' => $this->loadBaseConfiguration(),
            'modules' => $this->loadModuleConfigurations(),
            'environments' => $this->loadEnvironmentConfigurations(),
            'merged' => [],
        ];

        // Merge configurations with inheritance
        $config['merged'] = $this->mergeConfigurations($config);

        return $config;
    }

    /**
     * Load base configuration
     */
    private function loadBaseConfiguration(): array
    {
        $baseConfig = [];
        
        // Load Laravel base config
        $configPath = config_path();
        $files = File::files($configPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php' && !str_starts_with($file->getFilename(), '.')) {
                $key = $file->getFilenameWithoutExtension();
                $baseConfig[$key] = require $file->getPathname();
            }
        }

        return $baseConfig;
    }

    /**
     * Load module configurations
     */
    private function loadModuleConfigurations(): array
    {
        $modules = [];
        $modulesPath = base_path('modules');
        
        if (!File::isDirectory($modulesPath)) {
            return $modules;
        }

        $moduleDirs = File::directories($modulesPath);
        
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $configPath = $moduleDir . '/config';
            
            if (File::isDirectory($configPath)) {
                $modules[$moduleName] = $this->loadModuleConfig($configPath, $moduleName);
            }
        }

        return $modules;
    }

    /**
     * Load configuration for a specific module
     */
    private function loadModuleConfig(string $configPath, string $moduleName): array
    {
        $config = [];
        $files = File::files($configPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $key = $file->getFilenameWithoutExtension();
                $config[$key] = require $file->getPathname();
            }
        }

        // Add module metadata
        $config['_metadata'] = [
            'name' => $moduleName,
            'path' => dirname($configPath),
            'enabled' => $this->isModuleEnabled($moduleName),
            'version' => $this->getModuleVersion($moduleName),
        ];

        return $config;
    }

    /**
     * Load environment-specific configurations
     */
    private function loadEnvironmentConfigurations(): array
    {
        $environments = [];
        $configPath = config_path();
        
        // Load environment-specific config directories
        $envDirs = ['staging', 'production', 'test'];
        
        foreach ($envDirs as $env) {
            $envPath = $configPath . '/' . $env;
            
            if (File::isDirectory($envPath)) {
                $environments[$env] = $this->loadEnvironmentConfig($envPath);
            }
        }

        return $environments;
    }

    /**
     * Load configuration for a specific environment
     */
    private function loadEnvironmentConfig(string $envPath): array
    {
        $config = [];
        $files = File::files($envPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $key = $file->getFilenameWithoutExtension();
                $config[$key] = require $file->getPathname();
            }
        }

        return $config;
    }

    /**
     * Merge configurations with inheritance
     */
    private function mergeConfigurations(array $config): array
    {
        $merged = $config['base'];
        
        // Merge environment-specific config
        $environment = app()->environment();
        if (isset($config['environments'][$environment])) {
            $merged = array_merge_recursive($merged, $config['environments'][$environment]);
        }

        // Merge module configurations
        foreach ($config['modules'] as $moduleName => $moduleConfig) {
            if ($moduleConfig['_metadata']['enabled']) {
                $merged = array_merge_recursive($merged, $moduleConfig);
            }
        }

        return $merged;
    }

    /**
     * Check if module is enabled
     */
    private function isModuleEnabled(string $moduleName): bool
    {
        return Config::get("modules.modules.{$moduleName}.enabled", true);
    }

    /**
     * Get module version
     */
    private function getModuleVersion(string $moduleName): ?string
    {
        $composerPath = base_path("modules/{$moduleName}/composer.json");
        
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            return $composer['version'] ?? null;
        }

        return null;
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CONFIG_CACHE_KEY);
    }

    /**
     * Reload configuration
     */
    public function reload(): array
    {
        $this->clearCache();
        return $this->getAllConfiguration();
    }

    /**
     * Get configuration value with dot notation
     */
    public function get(string $key, $default = null)
    {
        $config = $this->getAllConfiguration();
        return data_get($config['merged'], $key, $default);
    }

    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        $config = $this->getAllConfiguration();
        data_set($config['merged'], $key, $value);
        
        // Update cache
        Cache::put(self::CONFIG_CACHE_KEY, $config, self::CONFIG_CACHE_TTL);
    }

    /**
     * Get all module names
     */
    public function getModuleNames(): Collection
    {
        $config = $this->getAllConfiguration();
        return collect(array_keys($config['modules']));
    }

    /**
     * Get enabled modules
     */
    public function getEnabledModules(): Collection
    {
        $config = $this->getAllConfiguration();
        
        return collect($config['modules'])
            ->filter(function ($moduleConfig) {
                return $moduleConfig['_metadata']['enabled'] ?? false;
            })
            ->keys();
    }

    /**
     * Validate configuration
     */
    public function validate(): array
    {
        $errors = [];
        $config = $this->getAllConfiguration();

        // Validate required configurations
        $required = ['app', 'database', 'cache'];
        
        foreach ($required as $key) {
            if (!isset($config['merged'][$key])) {
                $errors[] = "Missing required configuration: {$key}";
            }
        }

        // Validate module configurations
        foreach ($config['modules'] as $moduleName => $moduleConfig) {
            if ($moduleConfig['_metadata']['enabled']) {
                $moduleErrors = $this->validateModuleConfig($moduleName, $moduleConfig);
                $errors = array_merge($errors, $moduleErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate module configuration
     */
    private function validateModuleConfig(string $moduleName, array $config): array
    {
        $errors = [];
        
        // Add module-specific validation here
        // This is a placeholder for future validation logic
        
        return $errors;
    }
} 