<?php

namespace App\Listeners;

use App\Events\HealthCheckCompleted;
use App\Services\MetricService;
use App\Services\AlertService;
use Illuminate\Support\Facades\Log;

class ProcessHealthCheckResults
{
    protected $metricService;
    protected $alertService;

    public function __construct(MetricService $metricService, AlertService $alertService)
    {
        $this->metricService = $metricService;
        $this->alertService = $alertService;
    }

    public function handle(HealthCheckCompleted $event): void
    {
        try {
            $healthCheck = $event->healthCheck;
            $result = $event->result;

            // Collect metrics if service is healthy
            if ($result->isHealthy()) {
                $metrics = $this->metricService->collectMetrics($healthCheck->name);
                
                // Process alerts based on metrics
                $alerts = $this->alertService->processMetrics($healthCheck->name, $metrics);
                
                Log::info('Health check results processed', [
                    'service' => $healthCheck->name,
                    'status' => $result->status,
                    'metrics_collected' => !empty($metrics),
                    'alerts_generated' => count($alerts)
                ]);
            } else {
                Log::warning('Unhealthy service detected', [
                    'service' => $healthCheck->name,
                    'status' => $result->status,
                    'details' => $result->details
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process health check results', [
                'service' => $event->healthCheck->name,
                'error' => $e->getMessage()
            ]);
        }
    }
} 