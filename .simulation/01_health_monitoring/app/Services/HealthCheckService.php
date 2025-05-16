<?php

namespace App\Services;

use App\Models\ServiceHealth;
use App\Models\Metric;
use App\Models\Alert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    private $metricService;
    private $alertService;

    public function __construct(MetricService $metricService, AlertService $alertService)
    {
        $this->metricService = $metricService;
        $this->alertService = $alertService;
    }

    public function checkAllServices(): array
    {
        $services = ServiceHealth::all();
        $overallStatus = 'healthy';
        $serviceStatuses = [];

        foreach ($services as $service) {
            $status = $this->checkService($service);
            $serviceStatuses[] = $status;

            if ($status['health_status'] === 'critical') {
                $overallStatus = 'critical';
            } elseif ($status['health_status'] === 'warning' && $overallStatus === 'healthy') {
                $overallStatus = 'warning';
            }
        }

        return [
            'overall_status' => $overallStatus,
            'services' => $serviceStatuses
        ];
    }

    public function checkService(ServiceHealth $service): array
    {
        try {
            $startTime = microtime(true);
            $metrics = $this->metricService->collectMetrics($service);
            $responseTime = microtime(true) - $startTime;

            // Update service status
            $service->update([
                'status' => 'healthy',
                'last_check' => now(),
                'response_time' => $responseTime
            ]);

            // Process metrics and generate alerts
            $this->processMetrics($service, $metrics);

            return [
                'service_name' => $service->service_name,
                'status' => 'healthy',
                'last_check' => $service->last_check,
                'response_time' => $responseTime,
                'health_status' => $service->getHealthStatus()
            ];
        } catch (\Exception $e) {
            Log::error("Health check failed for service {$service->service_name}: " . $e->getMessage());
            
            $service->increment('error_count');
            
            return [
                'service_name' => $service->service_name,
                'status' => 'unhealthy',
                'last_check' => now(),
                'response_time' => 0,
                'health_status' => 'critical',
                'error' => $e->getMessage()
            ];
        }
    }

    private function processMetrics(ServiceHealth $service, Collection $metrics): void
    {
        foreach ($metrics as $metric) {
            // Store metric
            $storedMetric = $service->metrics()->create([
                'name' => $metric['name'],
                'value' => $metric['value'],
                'unit' => $metric['unit'],
                'threshold' => $metric['threshold'],
                'timestamp' => now()
            ]);

            // Check for alerts
            if ($storedMetric->isAboveThreshold()) {
                $this->alertService->createAlert($service, [
                    'type' => $metric['name'],
                    'level' => 'critical',
                    'message' => "{$metric['name']} is above threshold: {$metric['value']} {$metric['unit']}"
                ]);
            } elseif ($storedMetric->getThresholdPercentage() > 80) {
                $this->alertService->createAlert($service, [
                    'type' => $metric['name'],
                    'level' => 'warning',
                    'message' => "{$metric['name']} is approaching threshold: {$metric['value']} {$metric['unit']}"
                ]);
            }
        }
    }

    public function testModels(): array
    {
        $results = [];

        // Test ServiceHealth model
        $serviceHealth = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);
        $results['ServiceHealth'] = [
            'created' => $serviceHealth->exists,
            'updated' => $serviceHealth->update(['status' => 'unhealthy'])
        ];

        // Test Metric model
        $metric = $serviceHealth->metrics()->create([
            'name' => 'test_metric',
            'value' => 75.5,
            'unit' => 'percent',
            'threshold' => 80.0,
            'timestamp' => now()
        ]);
        $results['Metric'] = [
            'created' => $metric->exists,
            'updated' => $metric->update(['value' => 85.0])
        ];

        // Test Alert model
        $alert = $serviceHealth->alerts()->create([
            'type' => 'test_alert',
            'level' => 'warning',
            'message' => 'Test alert message'
        ]);
        $results['Alert'] = [
            'created' => $alert->exists,
            'updated' => $alert->update(['level' => 'critical'])
        ];

        // Cleanup
        $serviceHealth->delete();

        return $results;
    }

    public function runLoadTest(): array
    {
        $startTime = microtime(true);
        $iterations = 100;
        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $this->checkAllServices();
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        return [
            'response_time' => ($totalTime / $iterations) * 1000, // Convert to milliseconds
            'requests_per_second' => $iterations / $totalTime,
            'error_rate' => $errorCount / $iterations
        ];
    }
} 