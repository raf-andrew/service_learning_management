<?php

namespace App\Simulations\HealthMonitoring;

use App\Models\ServiceHealth;
use App\Services\HealthCheckService;
use App\Services\MetricService;
use App\Services\AlertService;
use Illuminate\Support\Facades\Log;

class HealthMonitoringSimulation
{
    protected $healthCheckService;
    protected $metricService;
    protected $alertService;
    protected $services = [];
    protected $metrics = [];
    protected $alerts = [];

    public function __construct(
        HealthCheckService $healthCheckService,
        MetricService $metricService,
        AlertService $alertService
    ) {
        $this->healthCheckService = $healthCheckService;
        $this->metricService = $metricService;
        $this->alertService = $alertService;
    }

    public function initialize()
    {
        // Initialize test services
        $this->services = [
            'web_server' => [
                'name' => 'Web Server',
                'endpoint' => 'http://localhost:8000',
                'check_interval' => 60,
                'timeout' => 5,
            ],
            'database' => [
                'name' => 'Database',
                'endpoint' => 'mysql://localhost:3306',
                'check_interval' => 30,
                'timeout' => 3,
            ],
            'cache' => [
                'name' => 'Cache Server',
                'endpoint' => 'redis://localhost:6379',
                'check_interval' => 45,
                'timeout' => 2,
            ],
        ];

        Log::info('Health Monitoring Simulation initialized', [
            'services' => array_keys($this->services)
        ]);
    }

    public function run()
    {
        try {
            // Run health checks for all services
            foreach ($this->services as $serviceName => $config) {
                $this->checkService($serviceName, $config);
            }

            // Collect metrics
            $this->collectMetrics();

            // Process alerts
            $this->processAlerts();

            Log::info('Health Monitoring Simulation completed successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Health Monitoring Simulation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function checkService($serviceName, $config)
    {
        try {
            $result = $this->healthCheckService->checkService($serviceName, $config);
            $this->services[$serviceName]['last_check'] = now();
            $this->services[$serviceName]['status'] = $result['status'];
            
            Log::info("Service health check completed", [
                'service' => $serviceName,
                'status' => $result['status']
            ]);
        } catch (\Exception $e) {
            Log::error("Service health check failed", [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            $this->services[$serviceName]['status'] = 'error';
        }
    }

    protected function collectMetrics()
    {
        foreach ($this->services as $serviceName => $config) {
            try {
                $metrics = $this->metricService->collectMetrics($serviceName);
                $this->metrics[$serviceName] = $metrics;
                
                Log::info("Metrics collected", [
                    'service' => $serviceName,
                    'metrics_count' => count($metrics)
                ]);
            } catch (\Exception $e) {
                Log::error("Metrics collection failed", [
                    'service' => $serviceName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function processAlerts()
    {
        foreach ($this->metrics as $serviceName => $metrics) {
            try {
                $alerts = $this->alertService->processMetrics($serviceName, $metrics);
                if (!empty($alerts)) {
                    $this->alerts[$serviceName] = $alerts;
                    
                    Log::info("Alerts processed", [
                        'service' => $serviceName,
                        'alerts_count' => count($alerts)
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Alert processing failed", [
                    'service' => $serviceName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function getResults()
    {
        return [
            'services' => $this->services,
            'metrics' => $this->metrics,
            'alerts' => $this->alerts
        ];
    }
} 