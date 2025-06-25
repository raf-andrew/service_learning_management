<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Advanced Configuration Management Service
 * 
 * Provides comprehensive configuration management with validation and monitoring.
 */
class ConfigurationManagementService
{
    /**
     * Configuration cache key
     */
    protected const CONFIG_CACHE_KEY = 'app:config:validated';

    /**
     * Required configuration keys
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $requiredConfigs = [
        'app' => [
            'name' => ['type' => 'string', 'required' => true],
            'env' => ['type' => 'string', 'required' => true, 'values' => ['local', 'production', 'testing']],
            'debug' => ['type' => 'boolean', 'required' => true],
            'key' => ['type' => 'string', 'required' => true, 'min_length' => 32],
            'url' => ['type' => 'url', 'required' => true],
        ],
        'database' => [
            'default' => ['type' => 'string', 'required' => true],
            'connections' => ['type' => 'array', 'required' => true],
        ],
        'cache' => [
            'default' => ['type' => 'string', 'required' => true],
            'stores' => ['type' => 'array', 'required' => true],
        ],
        'queue' => [
            'default' => ['type' => 'string', 'required' => true],
            'connections' => ['type' => 'array', 'required' => true],
        ],
        'session' => [
            'driver' => ['type' => 'string', 'required' => true],
            'lifetime' => ['type' => 'integer', 'required' => true, 'min' => 1, 'max' => 1440],
        ],
        'mail' => [
            'default' => ['type' => 'string', 'required' => true],
            'mailers' => ['type' => 'array', 'required' => true],
        ],
    ];

    /**
     * Configuration validation rules
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $validationRules = [
        'security' => [
            'app.debug' => ['production' => false],
            'session.secure' => ['production' => true],
            'session.http_only' => ['production' => true],
            'session.same_site' => ['production' => 'lax'],
        ],
        'performance' => [
            'cache.default' => ['production' => 'redis'],
            'queue.default' => ['production' => 'redis'],
            'session.driver' => ['production' => 'redis'],
        ],
        'monitoring' => [
            'logging.level' => ['production' => 'error'],
            'logging.channels' => ['production' => ['stack', 'daily']],
        ],
    ];

    /**
     * Validate all configuration
     *
     * @return array<string, mixed>
     */
    public function validateConfiguration(): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'timestamp' => now()->toISOString(),
        ];

        // Validate required configurations
        foreach ($this->requiredConfigs as $section => $configs) {
            foreach ($configs as $key => $rules) {
                $fullKey = "{$section}.{$key}";
                $value = Config::get($fullKey);

                $validation = $this->validateConfigValue($fullKey, $value, $rules);
                if (!$validation['valid']) {
                    $results['valid'] = false;
                    $results['errors'][] = $validation;
                } elseif (isset($validation['warning'])) {
                    $results['warnings'][] = $validation;
                }
            }
        }

        // Validate environment-specific rules
        $environmentRules = $this->validateEnvironmentRules();
        $results['errors'] = array_merge($results['errors'], $environmentRules['errors']);
        $results['warnings'] = array_merge($results['warnings'], $environmentRules['warnings']);
        $results['recommendations'] = array_merge($results['recommendations'], $environmentRules['recommendations']);

        // Cache validation results
        Cache::put(self::CONFIG_CACHE_KEY, $results, 3600);

        return $results;
    }

    /**
     * Validate individual configuration value
     *
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    protected function validateConfigValue(string $key, $value, array $rules): array
    {
        $result = [
            'key' => $key,
            'value' => $value,
            'valid' => true,
        ];

        // Check if required
        if (isset($rules['required']) && $rules['required'] && empty($value)) {
            $result['valid'] = false;
            $result['error'] = "Configuration key '{$key}' is required but not set";
            return $result;
        }

        // Check type
        if (isset($rules['type'])) {
            $typeValid = $this->validateType($value, $rules['type']);
            if (!$typeValid) {
                $result['valid'] = false;
                $result['error'] = "Configuration key '{$key}' must be of type '{$rules['type']}'";
                return $result;
            }
        }

        // Check allowed values
        if (isset($rules['values']) && !in_array($value, $rules['values'])) {
            $result['valid'] = false;
            $result['error'] = "Configuration key '{$key}' must be one of: " . implode(', ', $rules['values']);
            return $result;
        }

        // Check string length
        if (isset($rules['min_length']) && is_string($value) && strlen($value) < $rules['min_length']) {
            $result['valid'] = false;
            $result['error'] = "Configuration key '{$key}' must be at least {$rules['min_length']} characters long";
            return $result;
        }

        // Check numeric range
        if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
            $result['valid'] = false;
            $result['error'] = "Configuration key '{$key}' must be at least {$rules['min']}";
            return $result;
        }

        if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
            $result['valid'] = false;
            $result['error'] = "Configuration key '{$key}' must be at most {$rules['max']}";
            return $result;
        }

        return $result;
    }

    /**
     * Validate type
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    protected function validateType($value, string $type): bool
    {
        return match($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            default => true,
        };
    }

    /**
     * Validate environment-specific rules
     *
     * @return array<string, mixed>
     */
    protected function validateEnvironmentRules(): array
    {
        $results = [
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
        ];

        $environment = Config::get('app.env');

        foreach ($this->validationRules as $category => $rules) {
            foreach ($rules as $key => $expectedValues) {
                $currentValue = Config::get($key);
                $expectedValue = $expectedValues[$environment] ?? null;

                if ($expectedValue !== null && $currentValue !== $expectedValue) {
                    $results['warnings'][] = [
                        'key' => $key,
                        'current_value' => $currentValue,
                        'expected_value' => $expectedValue,
                        'environment' => $environment,
                        'category' => $category,
                        'message' => "Configuration '{$key}' should be '{$expectedValue}' in {$environment} environment",
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get configuration summary
     *
     * @return array<string, mixed>
     */
    public function getConfigurationSummary(): array
    {
        $validation = $this->validateConfiguration();
        
        return [
            'environment' => Config::get('app.env'),
            'debug_mode' => Config::get('app.debug'),
            'cache_driver' => Config::get('cache.default'),
            'queue_driver' => Config::get('queue.default'),
            'session_driver' => Config::get('session.driver'),
            'database_connection' => Config::get('database.default'),
            'mail_driver' => Config::get('mail.default'),
            'validation' => $validation,
            'last_validated' => now()->toISOString(),
        ];
    }

    /**
     * Monitor configuration changes
     *
     * @return array<string, mixed>
     */
    public function monitorConfiguration(): array
    {
        $currentHash = $this->getConfigurationHash();
        $cachedHash = Cache::get('app:config:hash');
        
        $changes = [];
        if ($cachedHash && $cachedHash !== $currentHash) {
            $changes = $this->detectConfigurationChanges();
        }
        
        Cache::put('app:config:hash', $currentHash, 3600);
        
        return [
            'has_changes' => !empty($changes),
            'changes' => $changes,
            'last_check' => now()->toISOString(),
        ];
    }

    /**
     * Get configuration hash
     *
     * @return string
     */
    protected function getConfigurationHash(): string
    {
        $configData = [
            'app' => Config::get('app'),
            'database' => Config::get('database'),
            'cache' => Config::get('cache'),
            'queue' => Config::get('queue'),
            'session' => Config::get('session'),
            'mail' => Config::get('mail'),
        ];
        
        return md5(serialize($configData));
    }

    /**
     * Detect configuration changes
     *
     * @return array<string, mixed>
     */
    protected function detectConfigurationChanges(): array
    {
        // This would compare current config with cached config
        // For now, we'll return a simple structure
        return [
            'timestamp' => now()->toISOString(),
            'message' => 'Configuration changes detected',
        ];
    }

    /**
     * Optimize configuration
     *
     * @return array<string, mixed>
     */
    public function optimizeConfiguration(): array
    {
        $optimizations = [];
        $environment = Config::get('app.env');

        // Performance optimizations
        if ($environment === 'production') {
            if (Config::get('cache.default') !== 'redis') {
                $optimizations[] = [
                    'type' => 'performance',
                    'key' => 'cache.default',
                    'current' => Config::get('cache.default'),
                    'recommended' => 'redis',
                    'reason' => 'Redis provides better performance in production',
                ];
            }

            if (Config::get('queue.default') !== 'redis') {
                $optimizations[] = [
                    'type' => 'performance',
                    'key' => 'queue.default',
                    'current' => Config::get('queue.default'),
                    'recommended' => 'redis',
                    'reason' => 'Redis provides better queue performance in production',
                ];
            }
        }

        // Security optimizations
        if ($environment === 'production') {
            if (Config::get('app.debug')) {
                $optimizations[] = [
                    'type' => 'security',
                    'key' => 'app.debug',
                    'current' => true,
                    'recommended' => false,
                    'reason' => 'Debug mode should be disabled in production',
                ];
            }

            if (!Config::get('session.secure')) {
                $optimizations[] = [
                    'type' => 'security',
                    'key' => 'session.secure',
                    'current' => false,
                    'recommended' => true,
                    'reason' => 'Secure cookies should be enabled in production',
                ];
            }
        }

        return $optimizations;
    }

    /**
     * Export configuration
     *
     * @param array<string> $sections
     * @return array<string, mixed>
     */
    public function exportConfiguration(array $sections = []): array
    {
        $export = [];
        
        if (empty($sections)) {
            $sections = array_keys($this->requiredConfigs);
        }
        
        foreach ($sections as $section) {
            $export[$section] = Config::get($section);
        }
        
        return $export;
    }

    /**
     * Import configuration
     *
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    public function importConfiguration(array $configuration): array
    {
        $results = [
            'success' => true,
            'imported' => [],
            'errors' => [],
        ];

        foreach ($configuration as $section => $values) {
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $fullKey = "{$section}.{$key}";
                    
                    try {
                        Config::set($fullKey, $value);
                        $results['imported'][] = $fullKey;
                    } catch (\Exception $e) {
                        $results['success'] = false;
                        $results['errors'][] = [
                            'key' => $fullKey,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get configuration statistics
     *
     * @return array<string, mixed>
     */
    public function getConfigurationStatistics(): array
    {
        $validation = $this->validateConfiguration();
        
        return [
            'total_configs' => count($this->requiredConfigs),
            'valid_configs' => count($this->requiredConfigs) - count($validation['errors']),
            'invalid_configs' => count($validation['errors']),
            'warnings' => count($validation['warnings']),
            'recommendations' => count($validation['recommendations']),
            'validation_score' => $this->calculateValidationScore($validation),
            'last_validated' => now()->toISOString(),
        ];
    }

    /**
     * Calculate validation score
     *
     * @param array<string, mixed> $validation
     * @return float
     */
    protected function calculateValidationScore(array $validation): float
    {
        $total = count($this->requiredConfigs);
        $errors = count($validation['errors']);
        
        if ($total === 0) {
            return 100.0;
        }
        
        return round((($total - $errors) / $total) * 100, 2);
    }
} 