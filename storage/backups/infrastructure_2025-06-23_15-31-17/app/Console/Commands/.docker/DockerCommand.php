<?php

namespace App\Console\Commands\.docker;

use Illuminate\Console\Command;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;

class DockerCommand extends Command
{
    protected $signature = 'docker
        {action : The action to perform (start|stop|restart|status|logs|rebuild|prune)}
        {service? : The service to perform the action on}';

    protected $description = 'Manage Docker services';

    protected $dockerManager;
    protected $networkManager;
    protected $volumeManager;

    public function __construct(
        DockerManager $dockerManager,
        NetworkManager $networkManager,
        VolumeManager $volumeManager
    ) {
        parent::__construct();
        $this->dockerManager = $dockerManager;
        $this->networkManager = $networkManager;
        $this->volumeManager = $volumeManager;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $service = $this->argument('service');

        switch ($action) {
            case 'start':
                $this->startServices($service);
                break;
            case 'stop':
                $this->stopServices($service);
                break;
            case 'restart':
                $this->restartServices($service);
                break;
            case 'status':
                $this->showStatus($service);
                break;
            case 'logs':
                $this->showLogs($service);
                break;
            case 'rebuild':
                $this->rebuildServices($service);
                break;
            case 'prune':
                $this->pruneResources();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    protected function startServices($service = null)
    {
        try {
            if ($service) {
                $this->info("Starting service: {$service}");
                $this->dockerManager->startServices();
                $this->dockerManager->waitForService($service);
            } else {
                $this->info('Starting all services...');
                $this->dockerManager->startServices();
            }
            $this->info('Services started successfully.');
        } catch (\Exception $e) {
            $this->error("Failed to start services: {$e->getMessage()}");
        }
    }

    protected function stopServices($service = null)
    {
        try {
            if ($service) {
                $this->info("Stopping service: {$service}");
                $this->dockerManager->stopServices();
            } else {
                $this->info('Stopping all services...');
                $this->dockerManager->stopServices();
            }
            $this->info('Services stopped successfully.');
        } catch (\Exception $e) {
            $this->error("Failed to stop services: {$e->getMessage()}");
        }
    }

    protected function restartServices($service = null)
    {
        $this->stopServices($service);
        $this->startServices($service);
    }

    protected function showStatus($service = null)
    {
        try {
            $status = $this->dockerManager->getServiceStatus();
            
            if ($service) {
                if (!isset($status[$service])) {
                    $this->error("Service {$service} not found.");
                    return;
                }
                $this->info("Status of {$service}:");
                $this->table(['Service', 'Status'], [[$service, $status[$service]]]);
            } else {
                $this->info('Service Status:');
                $this->table(['Service', 'Status'], collect($status)->map(fn($status, $service) => [$service, $status])->toArray());
            }
        } catch (\Exception $e) {
            $this->error("Failed to get service status: {$e->getMessage()}");
        }
    }

    protected function showLogs($service = null)
    {
        if (!$service) {
            $this->error('Please specify a service to show logs for.');
            return;
        }

        try {
            $logs = $this->dockerManager->getServiceLogs($service);
            $this->info("Logs for {$service}:");
            $this->line($logs);
        } catch (\Exception $e) {
            $this->error("Failed to get logs: {$e->getMessage()}");
        }
    }

    protected function rebuildServices($service = null)
    {
        try {
            if ($service) {
                $this->info("Rebuilding service: {$service}");
                $this->dockerManager->rebuildService($service);
            } else {
                $this->info('Rebuilding all services...');
                $this->dockerManager->rebuildService('app');
                $this->dockerManager->rebuildService('nginx');
            }
            $this->info('Services rebuilt successfully.');
        } catch (\Exception $e) {
            $this->error("Failed to rebuild services: {$e->getMessage()}");
        }
    }

    protected function pruneResources()
    {
        try {
            $this->info('Pruning unused Docker resources...');
            
            $this->info('Pruning networks...');
            $this->networkManager->pruneNetworks();
            
            $this->info('Pruning volumes...');
            $this->volumeManager->pruneVolumes();
            
            $this->info('Docker resources pruned successfully.');
        } catch (\Exception $e) {
            $this->error("Failed to prune resources: {$e->getMessage()}");
        }
    }
} 