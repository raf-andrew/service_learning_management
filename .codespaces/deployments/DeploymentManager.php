<?php

namespace Codespaces\Deployments;

use Codespaces\Config\ConfigurationManager;
use Codespaces\Services\ServiceManager;
use Codespaces\Monitoring\MonitoringSystem;

class DeploymentManager {
    private ConfigurationManager $config;
    private ServiceManager $services;
    private MonitoringSystem $monitor;
    private DeploymentTracker $tracker;
    private string $environment;
    private array $deploymentState = [];
    private array $deploymentHistory = [];

    public function __construct(string $configPath, string $environment = 'codespaces') {
        $this->config = new ConfigurationManager($configPath, $environment);
        $this->services = new ServiceManager($configPath, $environment);
        $this->monitor = new MonitoringSystem($configPath);
        $this->tracker = new DeploymentTracker($configPath);
        $this->environment = $environment;
    }

    public function deployServices(): bool {
        try {
            $services = $this->config->getAllServices();
            $deploymentOrder = $this->resolveDependencies($services);
            
            foreach ($deploymentOrder as $serviceName) {
                if (!$this->deployService($serviceName)) {
                    $this->rollbackDeployment($serviceName);
                    return false;
                }
            }

            $this->validateDeployment();
            $this->startMonitoring();
            return true;
        } catch (\Exception $e) {
            $this->logError("Deployment failed: " . $e->getMessage());
            return false;
        }
    }

    private function deployService(string $serviceName): bool {
        try {
            $serviceConfig = $this->config->getServiceConfig($serviceName);
            
            // Check if service is already deployed
            if ($this->isServiceDeployed($serviceName)) {
                $this->logInfo("Service {$serviceName} is already deployed");
                return true;
            }

            // Deploy service
            $this->logInfo("Deploying service {$serviceName}");
            $deploymentResult = $this->services->deployService($serviceName);

            if (!$deploymentResult) {
                throw new \Exception("Failed to deploy service {$serviceName}");
            }

            // Track deployment
            $this->tracker->trackDeployment($serviceName, [
                'status' => 'deployed',
                'environment' => $this->environment,
                'version' => $serviceConfig['version'] ?? '1.0.0',
                'health' => 'pending',
                'configuration' => $serviceConfig
            ]);

            // Wait for service to be healthy
            $healthCheckResult = $this->waitForServiceHealth($serviceName);
            if (!$healthCheckResult) {
                throw new \Exception("Service {$serviceName} failed health check");
            }

            $this->deploymentState[$serviceName] = [
                'status' => 'deployed',
                'timestamp' => date('c'),
                'health' => 'healthy'
            ];

            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to deploy service {$serviceName}: " . $e->getMessage());
            return false;
        }
    }

    private function resolveDependencies(array $services): array {
        $deploymentOrder = [];
        $visited = [];
        $temp = [];

        foreach ($services as $serviceName => $service) {
            if (!isset($visited[$serviceName])) {
                $this->visitService($serviceName, $services, $visited, $temp, $deploymentOrder);
            }
        }

        return array_reverse($deploymentOrder);
    }

    private function visitService(string $serviceName, array $services, array &$visited, array &$temp, array &$deploymentOrder): void {
        if (isset($temp[$serviceName])) {
            throw new \Exception("Circular dependency detected for service {$serviceName}");
        }

        if (isset($visited[$serviceName])) {
            return;
        }

        $temp[$serviceName] = true;

        if (isset($services[$serviceName]['dependencies'])) {
            foreach ($services[$serviceName]['dependencies'] as $dependency) {
                $this->visitService($dependency, $services, $visited, $temp, $deploymentOrder);
            }
        }

        unset($temp[$serviceName]);
        $visited[$serviceName] = true;
        $deploymentOrder[] = $serviceName;
    }

    private function waitForServiceHealth(string $serviceName, int $maxAttempts = 5): bool {
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            if ($this->services->isServiceHealthy($serviceName)) {
                return true;
            }
            sleep(5);
            $attempts++;
        }
        return false;
    }

    private function rollbackDeployment(string $serviceName): void {
        try {
            $this->logInfo("Rolling back deployment of service {$serviceName}");
            
            // Stop service
            $this->services->stopService($serviceName);

            // Remove service configuration
            $this->services->removeService($serviceName);

            // Update deployment state
            unset($this->deploymentState[$serviceName]);
            
            // Update tracker
            $this->tracker->trackDeployment($serviceName, [
                'status' => 'rolled_back',
                'environment' => $this->environment,
                'version' => '0.0.0',
                'health' => 'unknown',
                'configuration' => []
            ]);

            $this->logInfo("Successfully rolled back service {$serviceName}");
        } catch (\Exception $e) {
            $this->logError("Failed to rollback service {$serviceName}: " . $e->getMessage());
        }
    }

    private function validateDeployment(): void {
        $services = $this->config->getAllServices();
        foreach ($services as $serviceName => $service) {
            if (!$this->validateService($serviceName)) {
                throw new \Exception("Service {$serviceName} failed validation");
            }
        }
    }

    private function validateService(string $serviceName): bool {
        $serviceConfig = $this->config->getServiceConfig($serviceName);
        
        // Check if service is deployed
        if (!isset($this->deploymentState[$serviceName])) {
            return false;
        }

        // Check service health
        if (!$this->services->isServiceHealthy($serviceName)) {
            return false;
        }

        // Check dependencies
        if (isset($serviceConfig['dependencies'])) {
            foreach ($serviceConfig['dependencies'] as $dependency) {
                if (!isset($this->deploymentState[$dependency]) || 
                    $this->deploymentState[$dependency]['status'] !== 'deployed') {
                    return false;
                }
            }
        }

        return true;
    }

    private function startMonitoring(): void {
        $this->monitor->startMonitoring();
    }

    private function isServiceDeployed(string $serviceName): bool {
        return isset($this->deploymentState[$serviceName]) && 
               $this->deploymentState[$serviceName]['status'] === 'deployed';
    }

    private function logInfo(string $message): void {
        $this->monitor->logInfo($message);
    }

    private function logError(string $message): void {
        $this->monitor->logError($message);
    }

    public function getDeploymentState(): array {
        return $this->deploymentState;
    }

    public function getDeploymentHistory(): array {
        return $this->deploymentHistory;
    }
} 