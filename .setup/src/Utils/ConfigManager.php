<?php

namespace Setup\Utils;

class ConfigManager {
    private array $config = [];
    private string $configFile;
    private Logger $logger;

    public function __construct(string $configFile, Logger $logger) {
        $this->configFile = $configFile;
        $this->logger = $logger;
    }

    public function load(): void {
        if (!file_exists($this->configFile)) {
            throw new \RuntimeException("Configuration file not found: {$this->configFile}");
        }

        $this->config = require $this->configFile;
        $this->logger->info("Configuration loaded from {$this->configFile}");
    }

    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): void {
        $this->config[$key] = $value;
    }

    public function has(string $key): bool {
        return isset($this->config[$key]);
    }

    public function all(): array {
        return $this->config;
    }

    public function merge(array $config): void {
        $this->config = array_merge_recursive($this->config, $config);
    }

    public function validate(): bool {
        $required = [
            'app' => ['name', 'env', 'debug', 'url', 'timezone'],
            'database' => ['driver', 'host', 'port', 'database', 'username', 'password'],
            'cache' => ['driver', 'path', 'prefix'],
            'session' => ['driver', 'path', 'lifetime'],
            'mail' => ['driver', 'host', 'port'],
            'logging' => ['default', 'channels'],
            'services' => ['api', 'queue', 'cache'],
            'testing' => ['enabled', 'suites', 'coverage']
        ];

        foreach ($required as $section => $keys) {
            if (!isset($this->config[$section])) {
                $this->logger->error("Missing configuration section: {$section}");
                return false;
            }

            foreach ($keys as $key) {
                if (!isset($this->config[$section][$key])) {
                    $this->logger->error("Missing configuration key: {$section}.{$key}");
                    return false;
                }
            }
        }

        return true;
    }
} 