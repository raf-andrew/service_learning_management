<?php

namespace App\Modules\Shared\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait ConfigAccessTrait
{
    /**
     * Cache TTL for configuration values
     */
    private const CONFIG_CACHE_TTL = 3600;

    /**
     * Get configuration value with caching and fallback
     */
    protected function getConfig(string $key, $default = null, bool $useCache = true)
    {
        if (!$useCache) {
            return Config::get($key, $default);
        }

        $cacheKey = "config_{$key}";
        
        return Cache::remember($cacheKey, self::CONFIG_CACHE_TTL, function () use ($key, $default) {
            return Config::get($key, $default);
        });
    }

    /**
     * Get module configuration
     */
    protected function getModuleConfig(string $moduleName, string $key = null, $default = null)
    {
        $configKey = $key ? "modules.{$moduleName}.{$key}" : "modules.{$moduleName}";
        return $this->getConfig($configKey, $default);
    }

    /**
     * Get environment-specific configuration
     */
    protected function getEnvironmentConfig(string $key, $default = null)
    {
        $environment = app()->environment();
        $configKey = "environments.{$environment}.{$key}";
        return $this->getConfig($configKey, $default);
    }

    /**
     * Check if configuration key exists
     */
    protected function hasConfig(string $key): bool
    {
        return Config::has($key);
    }

    /**
     * Set configuration value
     */
    protected function setConfig(string $key, $value): void
    {
        Config::set($key, $value);
        
        // Clear cache for this key
        $cacheKey = "config_{$key}";
        Cache::forget($cacheKey);
        
        Log::debug("Configuration updated", ['key' => $key, 'value' => $value]);
    }

    /**
     * Get configuration with validation
     */
    protected function getConfigOrFail(string $key)
    {
        $value = $this->getConfig($key);
        
        if ($value === null) {
            throw new \InvalidArgumentException("Configuration key '{$key}' not found");
        }
        
        return $value;
    }

    /**
     * Get boolean configuration
     */
    protected function getBoolConfig(string $key, bool $default = false): bool
    {
        $value = $this->getConfig($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get integer configuration
     */
    protected function getIntConfig(string $key, int $default = 0): int
    {
        $value = $this->getConfig($key, $default);
        return (int) $value;
    }

    /**
     * Get array configuration
     */
    protected function getArrayConfig(string $key, array $default = []): array
    {
        $value = $this->getConfig($key, $default);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get string configuration
     */
    protected function getStringConfig(string $key, string $default = ''): string
    {
        $value = $this->getConfig($key, $default);
        return (string) $value;
    }

    /**
     * Clear configuration cache
     */
    protected function clearConfigCache(string $key = null): void
    {
        if ($key) {
            $cacheKey = "config_{$key}";
            Cache::forget($cacheKey);
        } else {
            // Clear all config cache
            Cache::flush();
        }
        
        Log::debug("Configuration cache cleared", ['key' => $key]);
    }

    /**
     * Get all configuration for a module
     */
    protected function getAllModuleConfig(string $moduleName): array
    {
        return $this->getModuleConfig($moduleName, null, []);
    }

    /**
     * Check if module is enabled
     */
    protected function isModuleEnabled(string $moduleName): bool
    {
        return $this->getBoolConfig("modules.{$moduleName}.enabled", true);
    }

    /**
     * Get feature flag configuration
     */
    protected function isFeatureEnabled(string $feature): bool
    {
        return $this->getBoolConfig("features.{$feature}", false);
    }

    /**
     * Get performance configuration
     */
    protected function getPerformanceConfig(string $key, $default = null)
    {
        return $this->getConfig("performance.{$key}", $default);
    }

    /**
     * Get security configuration
     */
    protected function getSecurityConfig(string $key, $default = null)
    {
        return $this->getConfig("security.{$key}", $default);
    }

    /**
     * Get audit configuration
     */
    protected function getAuditConfig(string $key, $default = null)
    {
        return $this->getConfig("audit.{$key}", $default);
    }

    /**
     * Get logging configuration
     */
    protected function getLoggingConfig(string $key, $default = null)
    {
        return $this->getConfig("logging.{$key}", $default);
    }

    /**
     * Get cache configuration
     */
    protected function getCacheConfig(string $key, $default = null)
    {
        return $this->getConfig("cache.{$key}", $default);
    }

    /**
     * Get database configuration
     */
    protected function getDatabaseConfig(string $key, $default = null)
    {
        return $this->getConfig("database.{$key}", $default);
    }

    /**
     * Get queue configuration
     */
    protected function getQueueConfig(string $key, $default = null)
    {
        return $this->getConfig("queue.{$key}", $default);
    }

    /**
     * Get session configuration
     */
    protected function getSessionConfig(string $key, $default = null)
    {
        return $this->getConfig("session.{$key}", $default);
    }

    /**
     * Get mail configuration
     */
    protected function getMailConfig(string $key, $default = null)
    {
        return $this->getConfig("mail.{$key}", $default);
    }

    /**
     * Get filesystem configuration
     */
    protected function getFilesystemConfig(string $key, $default = null)
    {
        return $this->getConfig("filesystems.{$key}", $default);
    }

    /**
     * Get API configuration
     */
    protected function getApiConfig(string $key, $default = null)
    {
        return $this->getConfig("api.{$key}", $default);
    }

    /**
     * Get web3 configuration
     */
    protected function getWeb3Config(string $key, $default = null)
    {
        return $this->getConfig("web3.{$key}", $default);
    }

    /**
     * Get MCP configuration
     */
    protected function getMcpConfig(string $key, $default = null)
    {
        return $this->getConfig("mcp.{$key}", $default);
    }

    /**
     * Get E2EE configuration
     */
    protected function getE2eeConfig(string $key, $default = null)
    {
        return $this->getConfig("e2ee.{$key}", $default);
    }

    /**
     * Get SOC2 configuration
     */
    protected function getSoc2Config(string $key, $default = null)
    {
        return $this->getConfig("soc2.{$key}", $default);
    }

    /**
     * Get Auth configuration
     */
    protected function getAuthConfig(string $key, $default = null)
    {
        return $this->getConfig("auth.{$key}", $default);
    }

    /**
     * Get Shared configuration
     */
    protected function getSharedConfig(string $key, $default = null)
    {
        return $this->getConfig("shared.{$key}", $default);
    }
} 