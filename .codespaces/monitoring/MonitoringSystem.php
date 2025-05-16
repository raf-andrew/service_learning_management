<?php

namespace Codespaces\Monitoring;

use Codespaces\Services\ServiceManager;
use Codespaces\Deployments\DeploymentTracker;

class MonitoringSystem
{
    private ServiceManager $serviceManager;
    private DeploymentTracker $tracker;
    private Logger $logger;
    private Auditor $auditor;
    private array $config;
    private array $healthChecks = [];
    private array $metrics = [];
    private array $alerts = [];
    private array $monitors = [];

    public function __construct(
        ServiceManager $serviceManager,
        DeploymentTracker $tracker,
        Logger $logger,
        Auditor $auditor,
        string $configPath
    ) {
        $this->serviceManager = $serviceManager;
        $this->tracker = $tracker;
        $this->logger = $logger;
        $this->auditor = $auditor;
        $this->config = json_decode(file_get_contents($configPath), true)['monitoring'];
    }

    public function startMonitoring(): void
    {
        $this->logger->info('Starting monitoring system');
        $this->auditor->logEvent('monitoring_started');

        // Start health check monitoring
        $this->startHealthChecks();

        // Start metrics collection
        $this->startMetricsCollection();

        // Start alert processing
        $this->startAlertProcessing();

        $this->logger->info('Monitoring system started');
    }

    private function startHealthChecks(): void
    {
        $this->logger->info('Starting health checks');
        
        foreach ($this->serviceManager->getAllServices() as $serviceName => $service) {
            $this->healthChecks[$serviceName] = [
                'last_check' => null,
                'status' => null,
                'failures' => 0
            ];
        }

        // Start health check loop
        while (true) {
            foreach ($this->serviceManager->getAllServices() as $serviceName => $service) {
                $this->checkServiceHealth($serviceName);
            }
            sleep(60); // Check every minute
        }
    }

    private function checkServiceHealth(string $serviceName): void
    {
        try {
            $isHealthy = $this->serviceManager->isServiceHealthy($serviceName);
            $this->healthChecks[$serviceName]['last_check'] = date('Y-m-d H:i:s');
            $this->healthChecks[$serviceName]['status'] = $isHealthy ? 'healthy' : 'unhealthy';

            if (!$isHealthy) {
                $this->healthChecks[$serviceName]['failures']++;
                $this->handleUnhealthyService($serviceName);
            } else {
                $this->healthChecks[$serviceName]['failures'] = 0;
            }

            $this->logger->info("Health check for {$serviceName}: " . ($isHealthy ? 'healthy' : 'unhealthy'));
            $this->auditor->logEvent('health_check', [
                'service' => $serviceName,
                'status' => $isHealthy ? 'healthy' : 'unhealthy'
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Health check failed for {$serviceName}: " . $e->getMessage());
            $this->auditor->logEvent('health_check_failed', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function handleUnhealthyService(string $serviceName): void
    {
        $failures = $this->healthChecks[$serviceName]['failures'];
        $maxRetries = $this->config['self_healing']['max_retries'];

        if ($failures >= $maxRetries) {
            $this->logger->error("Service {$serviceName} is unhealthy after {$failures} failures");
            $this->auditor->logEvent('service_unhealthy', [
                'service' => $serviceName,
                'failures' => $failures
            ]);

            if ($this->config['self_healing']['enabled']) {
                $this->attemptSelfHealing($serviceName);
            }
        }
    }

    private function attemptSelfHealing(string $serviceName): void
    {
        $this->logger->info("Attempting self-healing for {$serviceName}");
        $this->auditor->logEvent('self_healing_started', ['service' => $serviceName]);

        try {
            // Attempt service restart
            if ($this->config['self_healing']['actions']['service_restart']) {
                $this->serviceManager->restartService($serviceName);
            }

            // Check dependencies
            if ($this->config['self_healing']['actions']['dependency_check']) {
                $this->checkServiceDependencies($serviceName);
            }

            // Reset configuration if needed
            if ($this->config['self_healing']['actions']['configuration_reset']) {
                $this->resetServiceConfiguration($serviceName);
            }

            $this->logger->info("Self-healing completed for {$serviceName}");
            $this->auditor->logEvent('self_healing_completed', ['service' => $serviceName]);
        } catch (\Exception $e) {
            $this->logger->error("Self-healing failed for {$serviceName}: " . $e->getMessage());
            $this->auditor->logEvent('self_healing_failed', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function startMetricsCollection(): void
    {
        $this->logger->info('Starting metrics collection');
        
        while (true) {
            foreach ($this->serviceManager->getAllServices() as $serviceName => $service) {
                $this->collectServiceMetrics($serviceName);
            }
            sleep($this->config['deployments']['metrics']['collection_interval']);
        }
    }

    private function collectServiceMetrics(string $serviceName): void
    {
        try {
            $metrics = $this->serviceManager->getServiceMetrics($serviceName);
            if ($metrics) {
                $this->metrics[$serviceName] = $metrics;
                $this->processMetrics($serviceName, $metrics);
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to collect metrics for {$serviceName}: " . $e->getMessage());
        }
    }

    private function processMetrics(string $serviceName, array $metrics): void
    {
        // Process metrics and generate alerts if needed
        foreach ($metrics as $metricName => $value) {
            if ($this->shouldGenerateAlert($serviceName, $metricName, $value)) {
                $this->generateAlert($serviceName, $metricName, $value);
            }
        }
    }

    private function shouldGenerateAlert(string $serviceName, string $metricName, $value): bool
    {
        // Implement alert threshold logic
        return false; // Placeholder
    }

    private function generateAlert(string $serviceName, string $metricName, $value): void
    {
        $alert = [
            'service' => $serviceName,
            'metric' => $metricName,
            'value' => $value,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->alerts[] = $alert;
        $this->logger->warning("Alert generated for {$serviceName}: {$metricName} = {$value}");
        $this->auditor->logEvent('alert_generated', $alert);
    }

    private function startAlertProcessing(): void
    {
        $this->logger->info('Starting alert processing');
        
        while (true) {
            $this->processAlerts();
            sleep(30); // Process alerts every 30 seconds
        }
    }

    private function processAlerts(): void
    {
        foreach ($this->alerts as $index => $alert) {
            // Process alert and take action if needed
            $this->handleAlert($alert);
            unset($this->alerts[$index]);
        }
    }

    private function handleAlert(array $alert): void
    {
        // Implement alert handling logic
        $this->logger->warning("Processing alert: " . json_encode($alert));
        $this->auditor->logEvent('alert_processed', $alert);
    }

    private function checkServiceDependencies(string $serviceName): void
    {
        $service = $this->serviceManager->getService($serviceName);
        if (!$service) {
            return;
        }

        foreach ($service->getDependencies() as $dependency) {
            if (!$this->serviceManager->isServiceHealthy($dependency)) {
                $this->logger->warning("Dependency {$dependency} is unhealthy for {$serviceName}");
                $this->attemptSelfHealing($dependency);
            }
        }
    }

    private function resetServiceConfiguration(string $serviceName): void
    {
        $this->logger->info("Resetting configuration for {$serviceName}");
        $this->auditor->logEvent('configuration_reset', ['service' => $serviceName]);

        // Implement configuration reset logic
    }

    public function getHealthChecks(): array
    {
        return $this->healthChecks;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }

    public function startMonitoringService(string $serviceName): void
    {
        if (!isset($this->config['services'][$serviceName])) {
            throw new \InvalidArgumentException("Service {$serviceName} not found in configuration");
        }

        $serviceConfig = $this->config['services'][$serviceName];
        
        // Create monitor based on service type
        $this->monitors[$serviceName] = match($serviceConfig['type']) {
            'api' => new ApiMonitor($serviceConfig),
            'database' => new DatabaseMonitor($serviceConfig),
            'cache' => new CacheMonitor($serviceConfig),
            'queue' => new QueueMonitor($serviceConfig),
            'mail' => new MailMonitor($serviceConfig),
            default => throw new \InvalidArgumentException("Unknown service type: {$serviceConfig['type']}")
        };

        // Start monitoring
        $this->monitors[$serviceName]->start();
    }

    public function stopMonitoringService(string $serviceName): void
    {
        if (isset($this->monitors[$serviceName])) {
            $this->monitors[$serviceName]->stop();
            unset($this->monitors[$serviceName]);
        }
    }

    public function getServiceHealth(string $serviceName): ?array
    {
        if (!isset($this->monitors[$serviceName])) {
            return null;
        }

        return $this->monitors[$serviceName]->getHealth();
    }

    public function getServiceMetrics(string $serviceName): ?array
    {
        if (!isset($this->monitors[$serviceName])) {
            return null;
        }

        return $this->monitors[$serviceName]->getMetrics();
    }

    public function processAlert(string $serviceName, array $alert): void
    {
        $this->alerts[] = [
            'service' => $serviceName,
            'timestamp' => date('c'),
            'level' => $alert['level'],
            'message' => $alert['message'],
            'context' => $alert['context'] ?? []
        ];

        // Update service health in tracker
        $this->tracker->updateServiceHealth(
            $serviceName,
            $alert['level'] === 'critical' ? 'unhealthy' : 'degraded',
            $alert['context'] ?? []
        );

        // Attempt self-healing if configured
        if ($this->config['monitoring']['self_healing']['enabled']) {
            $this->attemptSelfHealing($serviceName, $alert);
        }
    }

    private function attemptSelfHealing(string $serviceName, array $alert): void
    {
        $selfHealingConfig = $this->config['monitoring']['self_healing'];
        $retryCount = 0;

        while ($retryCount < $selfHealingConfig['max_retries']) {
            try {
                if ($selfHealingConfig['actions']['service_restart']) {
                    $this->monitors[$serviceName]->restart();
                }

                if ($selfHealingConfig['actions']['configuration_reset']) {
                    $this->monitors[$serviceName]->resetConfiguration();
                }

                if ($selfHealingConfig['actions']['dependency_check']) {
                    $this->monitors[$serviceName]->checkDependencies();
                }

                // Check if healing was successful
                $health = $this->getServiceHealth($serviceName);
                if ($health['status'] === 'healthy') {
                    $this->tracker->updateServiceHealth($serviceName, 'healthy', $health['metrics']);
                    return;
                }

                $retryCount++;
                sleep($selfHealingConfig['retry_delay']);
            } catch (\Exception $e) {
                $this->alerts[] = [
                    'service' => $serviceName,
                    'timestamp' => date('c'),
                    'level' => 'error',
                    'message' => "Self-healing failed: " . $e->getMessage(),
                    'context' => ['retry_count' => $retryCount]
                ];
                $retryCount++;
            }
        }

        // If we get here, self-healing failed
        $this->alerts[] = [
            'service' => $serviceName,
            'timestamp' => date('c'),
            'level' => 'critical',
            'message' => "Self-healing failed after {$retryCount} attempts",
            'context' => ['alert' => $alert]
        ];
    }

    public function getAlerts(string $serviceName = null, string $level = null): array
    {
        $filtered = $this->alerts;

        if ($serviceName) {
            $filtered = array_filter($filtered, fn($alert) => $alert['service'] === $serviceName);
        }

        if ($level) {
            $filtered = array_filter($filtered, fn($alert) => $alert['level'] === $level);
        }

        return array_values($filtered);
    }

    public function clearAlerts(string $serviceName = null): void
    {
        if ($serviceName) {
            $this->alerts = array_filter($this->alerts, fn($alert) => $alert['service'] !== $serviceName);
        } else {
            $this->alerts = [];
        }
    }
}

// Monitor base class
abstract class ServiceMonitor
{
    protected array $config;
    protected bool $running = false;
    protected array $health = ['status' => 'unknown'];
    protected array $metrics = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    abstract public function start(): void;
    abstract public function stop(): void;
    abstract public function getHealth(): array;
    abstract public function getMetrics(): array;
    abstract public function restart(): void;
    abstract public function resetConfiguration(): void;
    abstract public function checkDependencies(): void;
}

// Service-specific monitor implementations
class ApiMonitor extends ServiceMonitor
{
    public function start(): void
    {
        $this->running = true;
        // Implement API-specific monitoring
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function getHealth(): array
    {
        // Implement API health check
        return $this->health;
    }

    public function getMetrics(): array
    {
        // Implement API metrics collection
        return $this->metrics;
    }

    public function restart(): void
    {
        // Implement API restart
    }

    public function resetConfiguration(): void
    {
        // Implement API configuration reset
    }

    public function checkDependencies(): void
    {
        // Implement API dependency check
    }
}

class DatabaseMonitor extends ServiceMonitor
{
    public function start(): void
    {
        $this->running = true;
        // Implement database-specific monitoring
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function getHealth(): array
    {
        // Implement database health check
        return $this->health;
    }

    public function getMetrics(): array
    {
        // Implement database metrics collection
        return $this->metrics;
    }

    public function restart(): void
    {
        // Implement database restart
    }

    public function resetConfiguration(): void
    {
        // Implement database configuration reset
    }

    public function checkDependencies(): void
    {
        // Implement database dependency check
    }
}

class CacheMonitor extends ServiceMonitor
{
    public function start(): void
    {
        $this->running = true;
        // Implement cache-specific monitoring
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function getHealth(): array
    {
        // Implement cache health check
        return $this->health;
    }

    public function getMetrics(): array
    {
        // Implement cache metrics collection
        return $this->metrics;
    }

    public function restart(): void
    {
        // Implement cache restart
    }

    public function resetConfiguration(): void
    {
        // Implement cache configuration reset
    }

    public function checkDependencies(): void
    {
        // Implement cache dependency check
    }
}

class QueueMonitor extends ServiceMonitor
{
    public function start(): void
    {
        $this->running = true;
        // Implement queue-specific monitoring
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function getHealth(): array
    {
        // Implement queue health check
        return $this->health;
    }

    public function getMetrics(): array
    {
        // Implement queue metrics collection
        return $this->metrics;
    }

    public function restart(): void
    {
        // Implement queue restart
    }

    public function resetConfiguration(): void
    {
        // Implement queue configuration reset
    }

    public function checkDependencies(): void
    {
        // Implement queue dependency check
    }
}

class MailMonitor extends ServiceMonitor
{
    public function start(): void
    {
        $this->running = true;
        // Implement mail-specific monitoring
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function getHealth(): array
    {
        // Implement mail health check
        return $this->health;
    }

    public function getMetrics(): array
    {
        // Implement mail metrics collection
        return $this->metrics;
    }

    public function restart(): void
    {
        // Implement mail restart
    }

    public function resetConfiguration(): void
    {
        // Implement mail configuration reset
    }

    public function checkDependencies(): void
    {
        // Implement mail dependency check
    }
} 