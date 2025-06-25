<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use App\Services\CodespaceInfrastructureManager;

class InfrastructureManagerCommand extends Command
{
    protected $signature = 'infrastructure:manage 
        {action : Action to perform (status|start|stop|restart|cleanup)}
        {--service= : Specific service to manage}
        {--force : Force the action without confirmation}';

    protected $description = 'Manage infrastructure services (Docker, Network, Volumes)';

    public function handle()
    {
        $action = $this->argument('action');
        $specificService = $this->option('service');
        $force = $this->option('force');

        if (!$this->isValidAction($action)) {
            $this->error("Invalid action: {$action}");
            return 1;
        }

        if (!$force && !$this->confirm("Are you sure you want to {$action} the infrastructure?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $infrastructureManager = app(CodespaceInfrastructureManager::class);
        $dockerManager = app(DockerManager::class);
        $networkManager = app(NetworkManager::class);
        $volumeManager = app(VolumeManager::class);

        try {
            switch ($action) {
                case 'status':
                    $this->handleStatus($infrastructureManager, $dockerManager, $networkManager, $volumeManager);
                    break;
                case 'start':
                    $this->handleStart($infrastructureManager, $specificService);
                    break;
                case 'stop':
                    $this->handleStop($infrastructureManager, $specificService);
                    break;
                case 'restart':
                    $this->handleRestart($infrastructureManager, $specificService);
                    break;
                case 'cleanup':
                    $this->handleCleanup($infrastructureManager, $dockerManager, $volumeManager);
                    break;
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    protected function isValidAction(string $action): bool
    {
        return in_array($action, ['status', 'start', 'stop', 'restart', 'cleanup']);
    }

    protected function handleStatus(
        CodespaceInfrastructureManager $infrastructureManager,
        DockerManager $dockerManager,
        NetworkManager $networkManager,
        VolumeManager $volumeManager
    ): void {
        $this->info('Checking infrastructure status...');

        $status = $this->getStatusData($infrastructureManager, $dockerManager, $networkManager, $volumeManager);

        $this->table(
            ['Component', 'Status', 'Details'],
            collect($status)->map(fn($item) => [
                $item['component'],
                $item['status'],
                $item['details'] ?? 'N/A'
            ])
        );
    }

    protected function getStatusData(
        CodespaceInfrastructureManager $infrastructureManager,
        DockerManager $dockerManager,
        NetworkManager $networkManager,
        VolumeManager $volumeManager
    ): array {
        return [
            'Docker' => $dockerManager->getStatus(),
            'Network' => $networkManager->getStatus(),
            'Volumes' => $volumeManager->getStatus(),
            'Infrastructure' => $infrastructureManager->getStatus()
        ];
    }

    protected function handleStart(CodespaceInfrastructureManager $infrastructureManager, ?string $service): void
    {
        $this->info('Starting infrastructure...');
        $this->executeStart($infrastructureManager, $service);
        if ($service) {
            $this->info("Service {$service} started successfully.");
        } else {
            $this->info('All infrastructure services started successfully.');
        }
    }

    protected function executeStart(CodespaceInfrastructureManager $infrastructureManager, ?string $service): void
    {
        if ($service) {
            $infrastructureManager->startService($service);
        } else {
            $infrastructureManager->startAll();
        }
    }

    protected function handleStop(CodespaceInfrastructureManager $infrastructureManager, ?string $service): void
    {
        $this->info('Stopping infrastructure...');
        $this->executeStop($infrastructureManager, $service);
        if ($service) {
            $this->info("Service {$service} stopped successfully.");
        } else {
            $this->info('All infrastructure services stopped successfully.');
        }
    }

    protected function executeStop(CodespaceInfrastructureManager $infrastructureManager, ?string $service): void
    {
        if ($service) {
            $infrastructureManager->stopService($service);
        } else {
            $infrastructureManager->stopAll();
        }
    }

    protected function handleRestart(CodespaceInfrastructureManager $infrastructureManager, ?string $service): void
    {
        $this->info('Restarting infrastructure...');
        $this->executeRestart($infrastructureManager, $service);
        if ($service) {
            $this->info("Service {$service} restarted successfully.");
        } else {
            $this->info('All infrastructure services restarted successfully.');
        }
    }

    protected function executeRestart(CodespaceInfrastructureManager $infrastructureManager, ?string $service): void
    {
        if ($service) {
            $infrastructureManager->restartService($service);
        } else {
            $infrastructureManager->restartAll();
        }
    }

    protected function handleCleanup(
        CodespaceInfrastructureManager $infrastructureManager,
        DockerManager $dockerManager,
        VolumeManager $volumeManager
    ): void {
        $this->info('Cleaning up infrastructure...');
        $this->executeCleanup($infrastructureManager, $dockerManager, $volumeManager);
        $this->info('Infrastructure cleanup completed successfully.');
    }

    protected function executeCleanup(
        CodespaceInfrastructureManager $infrastructureManager,
        DockerManager $dockerManager,
        VolumeManager $volumeManager
    ): void {
        $infrastructureManager->stopAll();
        $dockerManager->cleanup();
        $volumeManager->cleanup();
    }
} 