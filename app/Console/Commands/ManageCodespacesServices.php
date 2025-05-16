<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CodespacesServiceManager;
use App\Services\CodespacesHealthMonitor;

class ManageCodespacesServices extends Command
{
    protected $signature = 'codespaces:services 
                            {action : The action to perform (create|activate|deactivate|health|heal)}
                            {service? : The service name (database|redis|mail)}
                            {--config= : Path to service configuration file}';

    protected $description = 'Manage Codespaces services';

    protected $serviceManager;
    protected $healthMonitor;

    public function __construct(
        CodespacesServiceManager $serviceManager,
        CodespacesHealthMonitor $healthMonitor
    ) {
        parent::__construct();
        $this->serviceManager = $serviceManager;
        $this->healthMonitor = $healthMonitor;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $service = $this->argument('service');

        switch ($action) {
            case 'create':
                $this->createService($service);
                break;
            case 'activate':
                $this->activateService($service);
                break;
            case 'deactivate':
                $this->deactivateService($service);
                break;
            case 'health':
                $this->checkHealth($service);
                break;
            case 'heal':
                $this->healService($service);
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    protected function createService(?string $service)
    {
        if (!$service) {
            $this->error('Service name is required for create action');
            return;
        }

        $configPath = $this->option('config');
        if (!$configPath) {
            $this->error('Configuration file path is required for create action');
            return;
        }

        if (!file_exists($configPath)) {
            $this->error("Configuration file not found: {$configPath}");
            return;
        }

        $config = json_decode(file_get_contents($configPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON configuration file');
            return;
        }

        try {
            $this->serviceManager->createService($service, $config);
            $this->info("Service {$service} created successfully");
        } catch (\Exception $e) {
            $this->error("Failed to create service: {$e->getMessage()}");
        }
    }

    protected function activateService(?string $service)
    {
        if (!$service) {
            $this->error('Service name is required for activate action');
            return;
        }

        try {
            $this->serviceManager->activateService($service);
            $this->info("Service {$service} activated successfully");
        } catch (\Exception $e) {
            $this->error("Failed to activate service: {$e->getMessage()}");
        }
    }

    protected function deactivateService(?string $service)
    {
        if (!$service) {
            $this->error('Service name is required for deactivate action');
            return;
        }

        try {
            $this->serviceManager->deactivateService($service);
            $this->info("Service {$service} deactivated successfully");
        } catch (\Exception $e) {
            $this->error("Failed to deactivate service: {$e->getMessage()}");
        }
    }

    protected function checkHealth(?string $service)
    {
        if ($service) {
            $result = $this->healthMonitor->checkServiceHealth($service);
            $this->displayHealthResult($result);
        } else {
            $results = $this->healthMonitor->checkAllServices();
            foreach ($results as $serviceName => $result) {
                $this->displayHealthResult($result);
            }
        }
    }

    protected function healService(?string $service)
    {
        if (!$service) {
            $this->error('Service name is required for heal action');
            return;
        }

        try {
            $success = $this->healthMonitor->healService($service);
            if ($success) {
                $this->info("Service {$service} healed successfully");
            } else {
                $this->error("Failed to heal service {$service}");
            }
        } catch (\Exception $e) {
            $this->error("Error while healing service: {$e->getMessage()}");
        }
    }

    protected function displayHealthResult(array $result)
    {
        $status = $result['healthy'] ? 'HEALTHY' : 'UNHEALTHY';
        $color = $result['healthy'] ? 'green' : 'red';
        
        $this->line(sprintf(
            "[%s] %s: <fg=%s>%s</>",
            $result['timestamp'],
            $result['service'],
            $color,
            $status
        ));

        if (!$result['healthy'] && isset($result['error'])) {
            $this->line("  Error: {$result['error']}");
        }
    }
} 