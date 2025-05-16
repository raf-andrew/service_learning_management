<?php

namespace Setup\Config;

class ConfigurationManager {
    private string $configPath;
    private array $config = [];

    public function __construct() {
        $this->configPath = __DIR__ . '/../../config';
        if (!is_dir($this->configPath)) {
            mkdir($this->configPath, 0755, true);
        }
    }

    public function saveConfig(array $config): void {
        $this->config = $config;
        $filename = $this->configPath . '/setup.json';
        file_put_contents($filename, json_encode($config, JSON_PRETTY_PRINT));
    }

    public function loadSavedConfig(): ?array {
        $filename = $this->configPath . '/setup.json';
        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }

        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $this->config = $config;
        return $config;
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function getConfigValue(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function setConfigValue(string $key, $value): void {
        $this->config[$key] = $value;
    }

    public function hasConfig(): bool {
        return !empty($this->config);
    }

    public function clearConfig(): void {
        $this->config = [];
        $filename = $this->configPath . '/setup.json';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
} 