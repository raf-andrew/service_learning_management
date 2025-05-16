<?php

namespace App\MCP\Core;

use App\MCP\Core\Config\Config;
use App\MCP\Core\Exceptions\EnvironmentException;

class EnvironmentManager
{
    protected Config $config;
    protected string $currentEnvironment;
    
    public function __construct()
    {
        $this->config = new Config();
        $this->currentEnvironment = $this->config->get('app.env', 'local');
    }
    
    public function setEnvironment(string $environment): void
    {
        if (!in_array($environment, ['local', 'staging', 'production'])) {
            throw new EnvironmentException("Invalid environment: {$environment}");
        }
        
        $this->currentEnvironment = $environment;
        $this->loadEnvironmentConfig();
    }
    
    public function getCurrentEnvironment(): string
    {
        return $this->currentEnvironment;
    }
    
    public function isStaging(): bool
    {
        return $this->currentEnvironment === 'staging';
    }
    
    public function isProduction(): bool
    {
        return $this->currentEnvironment === 'production';
    }
    
    public function isLocal(): bool
    {
        return $this->currentEnvironment === 'local';
    }
    
    protected function loadEnvironmentConfig(): void
    {
        $configPath = config_path("{$this->currentEnvironment}/mcp.php");
        
        if (!file_exists($configPath)) {
            throw new EnvironmentException("Configuration file not found for environment: {$this->currentEnvironment}");
        }
        
        $config = require $configPath;
        $this->config->set($config);
    }
    
    public function getConfig(): Config
    {
        return $this->config;
    }
} 