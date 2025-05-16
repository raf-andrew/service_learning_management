<?php

namespace Codespaces\Services;

use Codespaces\Deployments\DeploymentTracker;
use Codespaces\Monitoring\MonitoringSystem;

class ServiceManager
{
    private array $config;
    private DeploymentTracker $tracker;
    private MonitoringSystem $monitor;
    private string $environment;

    public function __construct(string $configPath, string $environment = 'codespaces')
    {
        $this->config = json_decode(file_get_contents($configPath), true);
        $this->environment = $environment;
        $this->tracker = new DeploymentTracker($configPath);
        $this->monitor = new MonitoringSystem($configPath);
    }

    public function deployService(string $serviceName): bool
    {
        if (!isset($this->config['services'][$serviceName])) {
            throw new \InvalidArgumentException("Service {$serviceName} not found in configuration");
        }

        $serviceConfig = $this->config['services'][$serviceName];
        
        // Check dependencies first
        if (!empty($serviceConfig['dependencies'])) {
            foreach ($serviceConfig['dependencies'] as $dependency) {
                if (!$this->isServiceHealthy($dependency)) {
                    throw new \RuntimeException("Dependency {$dependency} is not healthy");
                }
            }
        }

        // Deploy based on service type
        $success = match($serviceConfig['type']) {
            'api' => $this->deployApiService($serviceName, $serviceConfig),
            'database' => $this->deployDatabaseService($serviceName, $serviceConfig),
            'cache' => $this->deployCacheService($serviceName, $serviceConfig),
            'queue' => $this->deployQueueService($serviceName, $serviceConfig),
            'mail' => $this->deployMailService($serviceName, $serviceConfig),
            default => throw new \InvalidArgumentException("Unknown service type: {$serviceConfig['type']}")
        };

        if ($success) {
            $this->tracker->trackDeployment($serviceName, [
                'status' => 'deployed',
                'environment' => $this->environment,
                'version' => $this->config['version'],
                'health' => 'initializing',
                'configuration' => $serviceConfig
            ]);

            // Start monitoring
            $this->monitor->startMonitoring($serviceName);
        }

        return $success;
    }

    private function deployApiService(string $serviceName, array $config): bool
    {
        // Implementation for API service deployment
        // This would include:
        // 1. Setting up the service container
        // 2. Configuring environment variables
        // 3. Setting up networking
        // 4. Starting the service
        return true;
    }

    private function deployDatabaseService(string $serviceName, array $config): bool
    {
        // Implementation for database service deployment
        // This would include:
        // 1. Setting up the database container
        // 2. Configuring database parameters
        // 3. Setting up persistence
        // 4. Initializing the database
        return true;
    }

    private function deployCacheService(string $serviceName, array $config): bool
    {
        // Implementation for cache service deployment
        // This would include:
        // 1. Setting up the cache container
        // 2. Configuring cache parameters
        // 3. Setting up persistence
        // 4. Starting the service
        return true;
    }

    private function deployQueueService(string $serviceName, array $config): bool
    {
        // Implementation for queue service deployment
        // This would include:
        // 1. Setting up the queue container
        // 2. Configuring queue parameters
        // 3. Setting up persistence
        // 4. Starting the service
        return true;
    }

    private function deployMailService(string $serviceName, array $config): bool
    {
        // Implementation for mail service deployment
        // This would include:
        // 1. Setting up the mail container
        // 2. Configuring mail parameters
        // 3. Setting up authentication
        // 4. Starting the service
        return true;
    }

    public function isServiceHealthy(string $serviceName): bool
    {
        $status = $this->tracker->getDeploymentStatus($serviceName);
        if (!$status) {
            return false;
        }

        return $status['health'] === 'healthy';
    }

    public function getServiceStatus(string $serviceName): ?array
    {
        return $this->tracker->getDeploymentStatus($serviceName);
    }

    public function getAllServices(): array
    {
        return array_keys($this->config['services']);
    }

    public function getServiceDependencies(string $serviceName): array
    {
        return $this->config['services'][$serviceName]['dependencies'] ?? [];
    }

    public function updateServiceConfiguration(string $serviceName, array $newConfig): bool
    {
        if (!isset($this->config['services'][$serviceName])) {
            return false;
        }

        // Update configuration
        $this->config['services'][$serviceName] = array_merge(
            $this->config['services'][$serviceName],
            $newConfig
        );

        // Track configuration change
        $this->tracker->trackDeployment($serviceName, [
            'status' => 'reconfigured',
            'environment' => $this->environment,
            'version' => $this->config['version'],
            'health' => 'reconfiguring',
            'configuration' => $this->config['services'][$serviceName]
        ]);

        return true;
    }

    public function switchEnvironment(string $newEnvironment): void
    {
        if (!in_array($newEnvironment, ['local', 'codespaces'])) {
            throw new \InvalidArgumentException("Invalid environment: {$newEnvironment}");
        }

        $this->environment = $newEnvironment;
        
        // Update all service configurations
        foreach ($this->config['services'] as $serviceName => $serviceConfig) {
            $this->updateServiceConfiguration($serviceName, [
                'environment' => [
                    'MCP_ENV' => $newEnvironment
                ]
            ]);
        }
    }
} 