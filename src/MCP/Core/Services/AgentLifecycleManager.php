<?php

namespace App\MCP\Core\Services;

use App\MCP\Core\Service;
use App\MCP\Core\Agent;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Agent Lifecycle Manager Service
 * 
 * Manages the lifecycle of agents in the MCP system
 */
class AgentLifecycleManager extends Service
{
    protected array $agents = [];
    protected array $agentStates = [];
    protected array $agentConfigs = [];
    protected array $agentMetrics = [];
    protected array $agentErrors = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct('agent_lifecycle_manager');
        $this->logger = $logger;
    }

    public function initialize(): void
    {
        $this->isHealthy = true;
    }

    /**
     * Register an agent with the lifecycle manager
     */
    public function registerAgent(string $id, object $agent): void
    {
        if (isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is already registered");
        }

        $this->agents[$id] = $agent;
        $this->agentStates[$id] = [
            'status' => 'registered',
            'registered_at' => now(),
            'last_active' => now(),
            'error_count' => 0,
            'restart_count' => 0
        ];

        $this->logger->info("Registered agent with lifecycle manager: " . get_class($agent));
    }

    /**
     * Unregister an agent from the lifecycle manager
     */
    public function unregisterAgent(string $id): void
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        try {
            if ($this->agentStates[$id]['status'] === 'running') {
                $this->stopAgent($id);
            }

            unset($this->agents[$id]);
            unset($this->agentStates[$id]);
            unset($this->agentConfigs[$id]);
            unset($this->agentMetrics[$id]);
            unset($this->agentErrors[$id]);

            $this->logger->info("Unregistered agent from lifecycle manager: " . get_class($this->agents[$id]));
        } catch (\Throwable $e) {
            $this->logger->error("Failed to unregister agent {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Start an agent
     */
    public function startAgent(string $id): void
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        $agent = $this->agents[$id];

        try {
            if (method_exists($agent, 'start')) {
                $agent->start();
            }

            $this->agentStates[$id]['status'] = 'running';
            $this->agentStates[$id]['last_active'] = now();
            $this->agentStates[$id]['started_at'] = now();

            $this->logger->info("Started agent: " . get_class($agent));
        } catch (\Throwable $e) {
            $this->handleAgentError($id, $e);
            throw $e;
        }
    }

    /**
     * Stop an agent
     */
    public function stopAgent(string $id): void
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        $agent = $this->agents[$id];

        try {
            if (method_exists($agent, 'stop')) {
                $agent->stop();
            }

            $this->agentStates[$id]['status'] = 'stopped';
            $this->agentStates[$id]['stopped_at'] = now();

            $this->logger->info("Stopped agent: " . get_class($agent));
        } catch (\Throwable $e) {
            $this->handleAgentError($id, $e);
            throw $e;
        }
    }

    public function restartAgent(string $id): void
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        try {
            $this->stopAgent($id);
            $this->startAgent($id);
            $this->agentStates[$id]['restart_count']++;

            $this->logger->info("Agent {$id} restarted", [
                'agent' => $id,
                'state' => $this->agentStates[$id]
            ]);
        } catch (\Throwable $e) {
            $this->handleAgentError($id, $e);
            throw $e;
        }
    }

    public function getAgentState(string $id): array
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        return $this->agentStates[$id];
    }

    public function updateAgentConfig(string $id, array $config): void
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        $agent = $this->agents[$id];
        if (method_exists($agent, 'setConfig')) {
            $agent->setConfig($config);
        }

        $this->agentConfigs[$id] = $config;
        $this->logger->info("Agent {$id} config updated", [
            'agent' => $id,
            'config' => $config
        ]);
    }

    public function getAgentConfig(string $id): array
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        return $this->agentConfigs[$id] ?? [];
    }

    public function recordAgentMetrics(string $id, array $metrics): void
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        $this->agentMetrics[$id] = array_merge(
            $this->agentMetrics[$id] ?? [],
            $metrics,
            ['recorded_at' => now()]
        );

        $this->logger->info("Agent {$id} metrics recorded", [
            'agent' => $id,
            'metrics' => $metrics
        ]);
    }

    public function getAgentMetrics(string $id): array
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        return $this->agentMetrics[$id] ?? [];
    }

    protected function handleAgentError(string $id, \Throwable $error): void
    {
        $this->agentStates[$id]['error_count']++;
        $this->agentStates[$id]['last_error'] = [
            'message' => $error->getMessage(),
            'timestamp' => now(),
            'trace' => $error->getTraceAsString()
        ];

        $this->agentErrors[$id][] = [
            'message' => $error->getMessage(),
            'timestamp' => now(),
            'trace' => $error->getTraceAsString()
        ];

        $this->logger->error("Agent {$id} error", [
            'agent' => $id,
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ]);

        // Update health status if too many errors
        if ($this->agentStates[$id]['error_count'] > 5) {
            $this->isHealthy = false;
        }
    }

    public function getAgentErrors(string $id): array
    {
        if (!isset($this->agents[$id])) {
            throw new \RuntimeException("Agent {$id} is not registered");
        }

        return $this->agentErrors[$id] ?? [];
    }

    public function getRegisteredAgents(): array
    {
        return array_map(function($id) {
            return [
                'id' => $id,
                'state' => $this->agentStates[$id],
                'config' => $this->agentConfigs[$id] ?? [],
                'metrics' => $this->agentMetrics[$id] ?? [],
                'error_count' => count($this->agentErrors[$id] ?? [])
            ];
        }, array_keys($this->agents));
    }

    /**
     * Get the status of all registered agents
     */
    public function getAgentStatus(): array
    {
        $status = [];
        foreach ($this->agents as $id => $info) {
            $status[$id] = [
                'status' => $info['status'],
                'started_at' => $info['started_at'],
                'stopped_at' => $info['stopped_at']
            ];
        }
        return $status;
    }

    /**
     * Get the status of a specific agent
     */
    public function getAgentStatusByClass(string $class): ?array
    {
        return $this->agents[$class] ?? null;
    }

    /**
     * Check if an agent is running
     */
    public function isAgentRunning(string $id): bool
    {
        return isset($this->agents[$id]) && $this->agentStates[$id]['status'] === 'running';
    }
} 