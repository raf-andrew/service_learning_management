<?php

declare(strict_types=1);

namespace MCP\Core\Config;

use MCP\Core\Logger\Logger;

class Config
{
    protected array $config = [];

    public function __construct(
        private string $configPath,
        private Logger $logger
    ) {
        $this->loadConfig();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->arrayGet($this->config, $key, $default);
    }

    public function set(string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->config = array_merge($this->config, $key);
        } else {
            $this->arraySet($this->config, $key, $value);
        }
    }

    public function has(string $key): bool
    {
        return $this->arrayHas($this->config, $key);
    }

    public function all(): array
    {
        return $this->config;
    }

    public function load(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    public function save(): void
    {
        $configFile = $this->configPath . '/config.php';
        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'return ' . var_export($this->config, true) . ';' . PHP_EOL;

        if (file_put_contents($configFile, $content) === false) {
            $this->logger->error('Failed to save configuration file', [
                'file' => $configFile
            ]);
            throw new \RuntimeException("Failed to save configuration file: {$configFile}");
        }

        $this->logger->info('Configuration file saved', [
            'file' => $configFile
        ]);
    }

    private function loadConfig(): void
    {
        $configFile = $this->configPath . '/config.php';

        if (!file_exists($configFile)) {
            $this->logger->warning('Configuration file not found', [
                'file' => $configFile
            ]);
            return;
        }

        $config = require $configFile;

        if (!is_array($config)) {
            $this->logger->error('Invalid configuration file format', [
                'file' => $configFile
            ]);
            throw new \RuntimeException("Invalid configuration file format: {$configFile}");
        }

        $this->config = $config;
        $this->logger->info('Configuration loaded', [
            'file' => $configFile
        ]);
    }

    protected function arrayGet(array $array, string $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    protected function arraySet(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }
    
    protected function arrayHas(array $array, string $key): bool
    {
        if (empty($array) || is_null($key)) {
            return false;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            
            $array = $array[$segment];
        }
        
        return true;
    }
} 