<?php

namespace App\MCP\Core\Services;

use App\MCP\Core\Service;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Health Monitor Service
 * 
 * Monitors the health of agents and services in the MCP system
 */
class HealthMonitor extends Service
{
    protected array $serviceStatuses = [];
    protected array $agentStatuses = [];
    protected array $metrics = [];
    protected array $thresholds = [
        'memory_usage' => 90, // 90% threshold
        'cpu_usage' => 80,    // 80% threshold
        'response_time' => 1000, // 1 second threshold
        'error_rate' => 5     // 5% threshold
    ];

    private array $agents = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct('health_monitor');
        $this->logger = $logger;
    }

    public function initialize(): void
    {
        $this->isHealthy = true;
        $this->metrics = [
            'memory_usage' => 0,
            'cpu_usage' => 0,
            'response_time' => 0,
            'error_rate' => 0,
            'total_services' => 0,
            'healthy_services' => 0,
            'total_agents' => 0,
            'healthy_agents' => 0
        ];
    }

    public function checkServiceHealth(string $serviceId, object $service): array
    {
        try {
            $status = [
                'id' => $serviceId,
                'name' => method_exists($service, 'getName') ? $service->getName() : $serviceId,
                'status' => 'unknown',
                'last_check' => time(),
                'metrics' => []
            ];

            if (method_exists($service, 'isHealthy')) {
                $status['status'] = $service->isHealthy() ? 'healthy' : 'unhealthy';
            }

            if (method_exists($service, 'getMetrics')) {
                $status['metrics'] = $service->getMetrics();
            }

            $this->serviceStatuses[$serviceId] = $status;
            $this->updateMetrics();

            Log::info("Health check completed for service {$serviceId}", [
                'service' => $serviceId,
                'status' => $status['status']
            ]);

            return $status;
        } catch (\Throwable $e) {
            Log::error("Health check failed for service {$serviceId}", [
                'service' => $serviceId,
                'error' => $e->getMessage()
            ]);

            $status = [
                'id' => $serviceId,
                'name' => method_exists($service, 'getName') ? $service->getName() : $serviceId,
                'status' => 'error',
                'last_check' => time(),
                'error' => $e->getMessage()
            ];

            $this->serviceStatuses[$serviceId] = $status;
            $this->updateMetrics();

            return $status;
        }
    }

    public function checkAgentHealth(string $agentId, object $agent): array
    {
        try {
            $status = [
                'id' => $agentId,
                'name' => method_exists($agent, 'getName') ? $agent->getName() : $agentId,
                'status' => 'unknown',
                'last_check' => time(),
                'metrics' => []
            ];

            if (method_exists($agent, 'isRunning')) {
                $status['status'] = $agent->isRunning() ? 'healthy' : 'stopped';
            }

            if (method_exists($agent, 'getMetrics')) {
                $status['metrics'] = $agent->getMetrics();
            }

            $this->agentStatuses[$agentId] = $status;
            $this->updateMetrics();

            Log::info("Health check completed for agent {$agentId}", [
                'agent' => $agentId,
                'status' => $status['status']
            ]);

            return $status;
        } catch (\Throwable $e) {
            Log::error("Health check failed for agent {$agentId}", [
                'agent' => $agentId,
                'error' => $e->getMessage()
            ]);

            $status = [
                'id' => $agentId,
                'name' => method_exists($agent, 'getName') ? $agent->getName() : $agentId,
                'status' => 'error',
                'last_check' => time(),
                'error' => $e->getMessage()
            ];

            $this->agentStatuses[$agentId] = $status;
            $this->updateMetrics();

            return $status;
        }
    }

    public function getSystemHealth(): array
    {
        $this->updateMetrics();

        return [
            'status' => $this->isHealthy,
            'timestamp' => time(),
            'services' => $this->serviceStatuses,
            'agents' => $this->agentStatuses,
            'metrics' => $this->metrics,
            'thresholds' => $this->thresholds
        ];
    }

    protected function updateMetrics(): void
    {
        $totalServices = count($this->serviceStatuses);
        $healthyServices = count(array_filter($this->serviceStatuses, function($status) {
            return $status['status'] === 'healthy';
        }));

        $totalAgents = count($this->agentStatuses);
        $healthyAgents = count(array_filter($this->agentStatuses, function($status) {
            return $status['status'] === 'healthy';
        }));

        $this->metrics['total_services'] = $totalServices;
        $this->metrics['healthy_services'] = $healthyServices;
        $this->metrics['total_agents'] = $totalAgents;
        $this->metrics['healthy_agents'] = $healthyAgents;

        // Update system health status
        $this->isHealthy = ($totalServices === 0 || $healthyServices / $totalServices >= 0.8) &&
                          ($totalAgents === 0 || $healthyAgents / $totalAgents >= 0.8);

        // Check thresholds
        if (
            $this->metrics['memory_usage'] > $this->thresholds['memory_usage'] ||
            $this->metrics['cpu_usage'] > $this->thresholds['cpu_usage'] ||
            $this->metrics['response_time'] > $this->thresholds['response_time'] ||
            $this->metrics['error_rate'] > $this->thresholds['error_rate']
        ) {
            $this->isHealthy = false;
        }
    }

    public function setMetric(string $name, float $value): void
    {
        if (isset($this->metrics[$name])) {
            $this->metrics[$name] = $value;
            $this->updateMetrics();
        }
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function setThreshold(string $name, float $value): void
    {
        if (isset($this->thresholds[$name])) {
            $this->thresholds[$name] = $value;
            $this->updateMetrics();
        }
    }

    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * Register an agent with the health monitor
     */
    public function registerAgent(object $agent): void
    {
        $this->agents[get_class($agent)] = [
            'agent' => $agent,
            'last_check' => date('Y-m-d H:i:s'),
            'status' => 'healthy'
        ];
        $this->logger->info('Registered agent with health monitor: ' . get_class($agent));
    }

    /**
     * Unregister an agent from the health monitor
     */
    public function unregisterAgent(object $agent): void
    {
        unset($this->agents[get_class($agent)]);
        $this->logger->info('Unregistered agent from health monitor: ' . get_class($agent));
    }

    /**
     * Get the health status of all registered agents
     */
    public function getHealthStatus(): array
    {
        $status = [];
        foreach ($this->agents as $class => $info) {
            $status[$class] = [
                'status' => $info['status'],
                'last_check' => $info['last_check']
            ];
        }
        return $status;
    }

    /**
     * Check the health of all registered agents
     */
    public function checkHealth(): array
    {
        $results = [];
        foreach ($this->agents as $class => $info) {
            try {
                if (method_exists($info['agent'], 'getHealthStatus')) {
                    $agentStatus = $info['agent']->getHealthStatus();
                    $this->agents[$class]['status'] = $agentStatus['status'];
                    $this->agents[$class]['last_check'] = date('Y-m-d H:i:s');
                    $results[$class] = $agentStatus;
                }
            } catch (\Throwable $e) {
                $this->logger->error("Health check failed for agent $class: " . $e->getMessage());
                $this->agents[$class]['status'] = 'unhealthy';
                $results[$class] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage()
                ];
            }
        }
        return $results;
    }
} 