<?php

namespace Codespaces\Config;

class ConfigurationManager
{
    private string $configPath;
    private array $config;
    private string $environment;

    public function __construct(string $configPath, string $environment = 'codespaces')
    {
        $this->configPath = $configPath;
        $this->environment = $environment;
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("Configuration file not found: {$this->configPath}");
        }

        $this->config = json_decode(file_get_contents($this->configPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON configuration: " . json_last_error_msg());
        }

        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        $requiredSections = ['version', 'services', 'deployments', 'monitoring'];
        foreach ($requiredSections as $section) {
            if (!isset($this->config[$section])) {
                throw new \RuntimeException("Missing required configuration section: {$section}");
            }
        }

        foreach ($this->config['services'] as $serviceName => $serviceConfig) {
            $this->validateServiceConfig($serviceName, $serviceConfig);
        }
    }

    private function validateServiceConfig(string $serviceName, array $config): void
    {
        $requiredFields = ['name', 'type', 'ports'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                throw new \RuntimeException("Missing required field '{$field}' in service '{$serviceName}'");
            }
        }

        if (!in_array($config['type'], ['api', 'database', 'cache', 'queue', 'mail'])) {
            throw new \RuntimeException("Invalid service type '{$config['type']}' in service '{$serviceName}'");
        }

        if (!is_array($config['ports'])) {
            throw new \RuntimeException("Ports must be an array in service '{$serviceName}'");
        }

        if (isset($config['dependencies']) && !is_array($config['dependencies'])) {
            throw new \RuntimeException("Dependencies must be an array in service '{$serviceName}'");
        }
    }

    public function getServiceConfig(string $serviceName): ?array
    {
        return $this->config['services'][$serviceName] ?? null;
    }

    public function getAllServices(): array
    {
        return array_keys($this->config['services']);
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function switchEnvironment(string $newEnvironment): void
    {
        if (!in_array($newEnvironment, ['local', 'codespaces'])) {
            throw new \InvalidArgumentException("Invalid environment: {$newEnvironment}");
        }

        $this->environment = $newEnvironment;
        $this->updateEnvironmentConfig();
    }

    private function updateEnvironmentConfig(): void
    {
        foreach ($this->config['services'] as $serviceName => &$serviceConfig) {
            if (isset($serviceConfig['environment'])) {
                $serviceConfig['environment']['MCP_ENV'] = $this->environment;
            }
        }

        $this->saveConfig();
    }

    public function updateServiceConfig(string $serviceName, array $newConfig): void
    {
        if (!isset($this->config['services'][$serviceName])) {
            throw new \InvalidArgumentException("Service {$serviceName} not found");
        }

        $this->config['services'][$serviceName] = array_merge(
            $this->config['services'][$serviceName],
            $newConfig
        );

        $this->validateServiceConfig($serviceName, $this->config['services'][$serviceName]);
        $this->saveConfig();
    }

    private function saveConfig(): void
    {
        file_put_contents(
            $this->configPath,
            json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function getDeploymentConfig(): array
    {
        return $this->config['deployments'] ?? [];
    }

    public function getMonitoringConfig(): array
    {
        return $this->config['monitoring'] ?? [];
    }

    public function validateEnvironmentVariables(): array
    {
        $missingVars = [];
        $requiredVars = [
            'MCP_API_KEY',
            'CODESPACES_DB_HOST',
            'CODESPACES_DB_USERNAME',
            'CODESPACES_DB_PASSWORD',
            'CODESPACES_REDIS_HOST',
            'CODESPACES_REDIS_PASSWORD',
            'CODESPACES_RABBITMQ_HOST',
            'CODESPACES_RABBITMQ_USERNAME',
            'CODESPACES_RABBITMQ_PASSWORD',
            'CODESPACES_MAIL_HOST',
            'CODESPACES_MAIL_USERNAME',
            'CODESPACES_MAIL_PASSWORD'
        ];

        foreach ($requiredVars as $var) {
            if (!getenv($var)) {
                $missingVars[] = $var;
            }
        }

        return $missingVars;
    }

    public function getServiceDependencies(string $serviceName): array
    {
        return $this->config['services'][$serviceName]['dependencies'] ?? [];
    }

    public function getServicePorts(string $serviceName): array
    {
        return $this->config['services'][$serviceName]['ports'] ?? [];
    }

    public function getServiceHealthEndpoint(string $serviceName): ?string
    {
        return $this->config['services'][$serviceName]['health_endpoint'] ?? null;
    }

    public function getServiceHealthCheck(string $serviceName): ?string
    {
        return $this->config['services'][$serviceName]['health_check'] ?? null;
    }
} 