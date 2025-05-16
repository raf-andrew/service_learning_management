<?php

namespace App\MCP\Agentic\Core\Server;

use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Agents\BaseAgent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class AgenticServer
{
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;
    protected TaskManager $taskManager;
    protected Collection $services;
    protected Collection $agents;
    protected bool $isRunning = false;
    protected string $environment;

    public function __construct(
        AuditLogger $auditLogger,
        AccessControl $accessControl,
        TaskManager $taskManager
    ) {
        $this->auditLogger = $auditLogger;
        $this->accessControl = $accessControl;
        $this->taskManager = $taskManager;
        $this->services = new Collection();
        $this->agents = new Collection();
        $this->environment = Config::get('mcp.agentic.environment', 'development');
    }

    public function registerService(string $name, object $service): void
    {
        if ($this->services->has($name)) {
            throw new \RuntimeException("Service {$name} is already registered");
        }

        $this->services->put($name, $service);
        
        $this->auditLogger->log('server', "Service {$name} registered", [
            'service' => $name,
            'environment' => $this->environment,
        ]);
    }

    public function registerAgent(BaseAgent $agent): void
    {
        $type = $agent->getType();
        
        if ($this->agents->has($type)) {
            throw new \RuntimeException("Agent {$type} is already registered");
        }

        $this->agents->put($type, $agent);
        
        $this->auditLogger->log('server', "Agent {$type} registered", [
            'agent_type' => $type,
            'capabilities' => $agent->getCapabilities(),
            'environment' => $this->environment,
        ]);
    }

    public function getService(string $name): ?object
    {
        return $this->services->get($name);
    }

    public function getAgent(string $type): ?BaseAgent
    {
        return $this->agents->get($type);
    }

    public function getAllServices(): Collection
    {
        return $this->services;
    }

    public function getAllAgents(): Collection
    {
        return $this->agents;
    }

    public function start(): void
    {
        if ($this->isRunning) {
            return;
        }

        $this->isRunning = true;
        
        $this->auditLogger->log('server', "Server started", [
            'environment' => $this->environment,
            'services' => $this->services->keys()->toArray(),
            'agents' => $this->agents->keys()->toArray(),
        ]);

        // Initialize all agents
        $this->agents->each(function (BaseAgent $agent) {
            $agent->initialize();
        });
    }

    public function stop(): void
    {
        if (!$this->isRunning) {
            return;
        }

        $this->isRunning = false;
        
        $this->auditLogger->log('server', "Server stopped", [
            'environment' => $this->environment,
        ]);

        // Stop all agents
        $this->agents->each(function (BaseAgent $agent) {
            $agent->stop();
        });
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function checkHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'environment' => $this->environment,
            'services' => [],
            'agents' => [],
        ];

        // Check services health
        $this->services->each(function ($service, $name) use (&$health) {
            if (method_exists($service, 'checkHealth')) {
                $health['services'][$name] = $service->checkHealth();
            } else {
                $health['services'][$name] = ['status' => 'unknown'];
            }
        });

        // Check agents health
        $this->agents->each(function (BaseAgent $agent, $type) use (&$health) {
            $health['agents'][$type] = [
                'status' => $agent->isRunning() ? 'running' : 'stopped',
                'capabilities' => $agent->getCapabilities(),
            ];
        });

        // Update overall status if any service or agent is unhealthy
        foreach ($health['services'] as $serviceHealth) {
            if ($serviceHealth['status'] === 'unhealthy') {
                $health['status'] = 'unhealthy';
                break;
            }
        }

        if ($health['status'] === 'healthy') {
            foreach ($health['agents'] as $agentHealth) {
                if ($agentHealth['status'] === 'stopped') {
                    $health['status'] = 'degraded';
                    break;
                }
            }
        }

        return $health;
    }
} 