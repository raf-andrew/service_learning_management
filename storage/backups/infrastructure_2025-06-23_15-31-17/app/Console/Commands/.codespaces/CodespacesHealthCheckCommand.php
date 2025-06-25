<?php

namespace App\Console\Commands\.codespaces;

use Illuminate\Console\Command;
use App\Services\CodespacesHealthService;
use Illuminate\Support\Facades\Config;

class CodespacesHealthCheckCommand extends Command
{
    protected $signature = 'codespaces:health-check {--service= : Check specific service}';
    protected $description = 'Check health of Codespaces services';

    public function handle()
    {
        if (!Config::get('codespaces.enabled', false)) {
            $this->error('Codespaces is not enabled');
            return 1;
        }

        $healthService = app(CodespacesHealthService::class);
        $service = $this->option('service');

        if ($service) {
            $this->checkSingleService($healthService, $service);
        } else {
            $this->checkAllServices($healthService);
        }

        return 0;
    }

    protected function checkSingleService(CodespacesHealthService $healthService, string $service): void
    {
        $this->info("Checking health of {$service}...");
        
        $result = $healthService->checkServiceHealth($service);
        
        if ($result['healthy']) {
            $this->info("✅ {$service} is healthy");
        } else {
            $this->error("❌ {$service} is unhealthy: {$result['message']}");
        }
    }

    protected function checkAllServices(CodespacesHealthService $healthService): void
    {
        $this->info('Checking health of all services...');
        
        $results = $healthService->checkAllServices();
        $allHealthy = true;

        foreach ($results as $service => $result) {
            if ($result['healthy']) {
                $this->info("✅ {$service} is healthy");
            } else {
                $this->error("❌ {$service} is unhealthy: {$result['message']}");
                $allHealthy = false;
            }
        }

        if ($allHealthy) {
            $this->info('All services are healthy');
        } else {
            $this->error('Some services are unhealthy');
        }
    }
} 