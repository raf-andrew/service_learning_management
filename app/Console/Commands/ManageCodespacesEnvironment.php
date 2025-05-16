<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CodespacesConfigManager;
use App\Services\CodespacesLifecycleManager;
use App\Services\CodespacesHealthMonitor;

class ManageCodespacesEnvironment extends Command
{
    protected $signature = 'codespaces:env
                            {action : The action to perform (init|toggle|rebuild|status)}
                            {service? : The service name (database|redis|mail)}
                            {--mode= : The environment mode (local|codespaces)}';

    protected $description = 'Manage Codespaces environment';

    protected $configManager;
    protected $lifecycleManager;
    protected $healthMonitor;

    public function __construct(
        CodespacesConfigManager $configManager,
        CodespacesLifecycleManager $lifecycleManager,
        CodespacesHealthMonitor $healthMonitor
    ) {
        parent::__construct();
        $this->configManager = $configManager;
        $this->lifecycleManager = $lifecycleManager;
        $this->healthMonitor = $healthMonitor;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $service = $this->argument('service');

        switch ($action) {
            case 'init':
                $this->initializeEnvironment();
                break;
            case 'toggle':
                $this->toggleEnvironment();
                break;
            case 'rebuild':
                $this->rebuildService($service);
                break;
            case 'status':
                $this->showStatus($service);
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    protected function initializeEnvironment()
    {
        $this->info('Initializing Codespaces environment...');

        // Create necessary directories
        $directories = [
            base_path('.codespaces/config/services'),
            base_path('.codespaces/config/local'),
            storage_path('logs/codespaces'),
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
                $this->info("Created directory: {$dir}");
            }
        }

        // Initialize services
        $services = ['database', 'redis', 'mail'];
        foreach ($services as $service) {
            $configPath = base_path(".codespaces/services/{$service}.json");
            if (file_exists($configPath)) {
                $config = json_decode(file_get_contents($configPath), true);
                $this->configManager->saveServiceConfig($service, $config, 'codespaces');
                $this->info("Initialized service: {$service}");
            }
        }

        $this->info('Environment initialized successfully!');
    }

    protected function toggleEnvironment()
    {
        $mode = $this->option('mode');
        if (!$mode) {
            $mode = $this->configManager->getMode() === 'local' ? 'codespaces' : 'local';
        }

        $this->info("Switching to {$mode} environment...");
        $this->configManager->setMode($mode);

        $services = $this->configManager->getActiveServices();
        foreach ($services as $service) {
            $this->configManager->applyServiceConfig($service);
            $this->info("Applied configuration for service: {$service}");
        }

        $this->info("Environment switched to {$mode} mode");
    }

    protected function rebuildService(?string $service)
    {
        if (!$service) {
            $this->error('Service name is required for rebuild action');
            return;
        }

        $this->info("Rebuilding service: {$service}");
        
        if ($this->lifecycleManager->rebuildService($service)) {
            $this->info("Service {$service} rebuilt successfully");
        } else {
            $this->error("Failed to rebuild service {$service}");
        }
    }

    protected function showStatus(?string $service)
    {
        $mode = $this->configManager->getMode();
        $this->info("Current environment mode: {$mode}");

        if ($service) {
            $health = $this->healthMonitor->checkServiceHealth($service);
            $this->displayHealthResult($health);
        } else {
            $services = $this->configManager->getActiveServices();
            foreach ($services as $serviceName) {
                $health = $this->healthMonitor->checkServiceHealth($serviceName);
                $this->displayHealthResult($health);
            }
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