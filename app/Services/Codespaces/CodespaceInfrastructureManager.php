<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class CodespaceInfrastructureManager
{
    protected $configManager;
    protected $dockerManager;
    protected $networkManager;

    public function __construct(
        CodespaceConfigurationManager $configManager,
        DockerManager $dockerManager,
        NetworkManager $networkManager
    ) {
        $this->configManager = $configManager;
        $this->dockerManager = $dockerManager;
        $this->networkManager = $networkManager;
    }

    public function deployInfrastructure()
    {
        try {
            // Validate configuration
            $this->configManager->validateConfiguration();

            // Create network if it doesn't exist
            $this->networkManager->createNetwork('codespace');

            // Generate and save Docker Compose file
            $this->saveDockerCompose();

            // Start services
            $this->dockerManager->startServices();

            // Wait for services to be ready
            $this->waitForServices();

            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function saveDockerCompose()
    {
        $compose = $this->configManager->generateDockerCompose();
        File::put(base_path('docker-compose.yml'), $compose);
    }

    protected function waitForServices()
    {
        $services = $this->configManager->getRequiredServices();
        foreach ($services as $service) {
            $this->dockerManager->waitForService($service);
        }
    }

    public function teardownInfrastructure()
    {
        try {
            // Stop all services
            $this->dockerManager->stopServices();

            // Remove network
            $this->networkManager->removeNetwork('codespace');

            // Remove volumes
            $this->dockerManager->removeVolumes();

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function rollback()
    {
        try {
            $this->teardownInfrastructure();
        } catch (\Exception $e) {
            // Log rollback failure
            \Log::error('Infrastructure rollback failed: ' . $e->getMessage());
        }
    }

    public function getServiceStatus()
    {
        return $this->dockerManager->getServiceStatus();
    }

    public function getNetworkStatus()
    {
        return $this->networkManager->getNetworkStatus('codespace');
    }

    public function getInfrastructureStatus()
    {
        return [
            'services' => $this->getServiceStatus(),
            'network' => $this->getNetworkStatus(),
            'configuration' => $this->configManager->getConfiguration(),
        ];
    }

    public function updateInfrastructure(array $config)
    {
        try {
            // Update configuration
            $this->configManager->updateConfiguration($config);

            // Redeploy infrastructure
            return $this->deployInfrastructure();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function validateInfrastructure()
    {
        $status = $this->getInfrastructureStatus();
        
        // Check if all required services are running
        $requiredServices = $this->configManager->getRequiredServices();
        foreach ($requiredServices as $service) {
            if (!isset($status['services'][$service]) || $status['services'][$service] !== 'running') {
                return false;
            }
        }

        // Check if network exists and is connected
        if (!isset($status['network']) || $status['network'] !== 'active') {
            return false;
        }

        return true;
    }
} 