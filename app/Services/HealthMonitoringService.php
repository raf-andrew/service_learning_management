<?php

namespace App\Services;

use App\Models\HealthCheck;
use App\Models\HealthMetric;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class HealthMonitoringService
{
    protected $healthCheckService;
    protected $metricService;
    protected $alertService;

    public function __construct(
        HealthCheckService $healthCheckService,
        MetricService $metricService,
        AlertService $alertService
    ) {
        $this->healthCheckService = $healthCheckService;
        $this->metricService = $metricService;
        $this->alertService = $alertService;
    }

    public function runHealthChecks(): array
    {
        $results = [
            'services' => [],
            'metrics' => [],
            'alerts' => []
        ];

        try {
            // Get all active health checks
            $healthChecks = HealthCheck::where('is_active', true)->get();

            // Run health checks
            foreach ($healthChecks as $healthCheck) {
                $checkResult = $this->healthCheckService->checkService(
                    $healthCheck->name,
                    $healthCheck->config ?? []
                );

                $results['services'][$healthCheck->name] = $checkResult;

                // Collect metrics if service is healthy
                if ($checkResult['status'] === 'healthy') {
                    $metrics = $this->metricService->collectMetrics($healthCheck->name);
                    $results['metrics'][$healthCheck->name] = $metrics;

                    // Process alerts based on metrics
                    $alerts = $this->alertService->processMetrics($healthCheck->name, $metrics);
                    if (!empty($alerts)) {
                        $results['alerts'][$healthCheck->name] = $alerts;
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Health monitoring failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => $e->getMessage(),
                'services' => [],
                'metrics' => [],
                'alerts' => []
            ];
        }
    }

    public function getServiceStatus(string $serviceName): array
    {
        try {
            $healthCheck = HealthCheck::where('name', $serviceName)
                ->where('is_active', true)
                ->firstOrFail();

            $result = $this->healthCheckService->checkService(
                $healthCheck->name,
                $healthCheck->config ?? []
            );

            if ($result['status'] === 'healthy') {
                $result['metrics'] = $this->metricService->collectMetrics($healthCheck->name);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Service status check failed', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getSystemHealth(): array
    {
        $results = $this->runHealthChecks();
        
        return [
            'overall_status' => $this->calculateOverallStatus($results),
            'services' => $results['services'],
            'metrics' => $results['metrics'],
            'alerts' => $results['alerts']
        ];
    }

    protected function calculateOverallStatus(array $results): string
    {
        if (empty($results['services'])) {
            return 'unknown';
        }

        $unhealthyServices = collect($results['services'])
            ->filter(fn($service) => $service['status'] === 'unhealthy')
            ->count();

        if ($unhealthyServices === 0) {
            return 'healthy';
        }

        if ($unhealthyServices === count($results['services'])) {
            return 'unhealthy';
        }

        return 'degraded';
    }
} 