<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HealthMonitoringService;
use App\Services\HealthCheckService;
use App\Services\AlertService;

class HealthMonitorCommand extends Command
{
    protected $signature = 'health:monitor {--service= : Specific service to monitor} {--detailed : Show detailed health information}';
    protected $description = 'Monitor the health of system services';

    public function handle()
    {
        try {
            $healthMonitoringService = app(HealthMonitoringService::class);
            $healthCheckService = app(HealthCheckService::class);
            $alertService = app(AlertService::class);

            $specificService = $this->option('service');
            $showDetailed = $this->option('detailed');

            $this->info('Starting health monitoring...');

            // Get health status
            $healthStatus = $specificService 
                ? $healthMonitoringService->checkServiceHealth($specificService)
                : $healthMonitoringService->checkAllServices();

            // Always display results, even if empty
            $this->displayHealthResults($healthStatus ?? [], $showDetailed);

            // Handle alerts if needed
            $this->handleAlerts($healthStatus ?? [], $alertService);

            return $this->determineExitCode($healthStatus ?? []);
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    protected function displayHealthResults(array $healthStatus, bool $showDetailed): void
    {
        $this->table(
            ['Service', 'Status', 'Last Check', 'Details'],
            $this->getTableRows($healthStatus, $showDetailed)
        );
    }

    protected function getTableRows(array $healthStatus, bool $showDetailed): array
    {
        return collect($healthStatus)->map(function ($status) use ($showDetailed) {
            return [
                $status['service'],
                $this->formatStatus($status['healthy']),
                $status['last_check'] ?? 'N/A',
                $showDetailed ? ($status['details'] ?? 'N/A') : 'Use --detailed for more info'
            ];
        })->toArray();
    }

    protected function formatStatus(bool $healthy): string
    {
        return $healthy ? '✅ Healthy' : '❌ Unhealthy';
    }

    protected function handleAlerts(array $healthStatus, AlertService $alertService): void
    {
        $unhealthyServices = collect($healthStatus)
            ->filter(fn($status) => !$status['healthy'])
            ->keys()
            ->toArray();

        if (!empty($unhealthyServices)) {
            $alertService->sendAlert(
                'Health Check Alert',
                'The following services are unhealthy: ' . implode(', ', $unhealthyServices),
                'warning'
            );
        }
    }

    protected function determineExitCode(array $healthStatus): int
    {
        return collect($healthStatus)->every(fn($status) => $status['healthy']) ? 0 : 1;
    }
} 