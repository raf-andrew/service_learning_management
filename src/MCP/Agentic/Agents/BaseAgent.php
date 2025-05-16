<?php

namespace App\MCP\Agentic\Agents;

use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;
use Illuminate\Support\Facades\Config;

abstract class BaseAgent
{
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;
    protected TaskManager $taskManager;
    protected string $tenant;
    protected bool $isRunning = false;

    public function __construct(
        AuditLogger $auditLogger,
        AccessControl $accessControl,
        TaskManager $taskManager
    ) {
        $this->auditLogger = $auditLogger;
        $this->accessControl = $accessControl;
        $this->taskManager = $taskManager;
    }

    abstract public function getType(): string;
    abstract public function getCapabilities(): array;

    public function initialize(): void
    {
        $this->auditLogger->log('agentic', "Agent {$this->getType()} initialized", [
            'agent_type' => $this->getType(),
            'capabilities' => $this->getCapabilities(),
        ]);
    }

    public function canExecute(): bool
    {
        return $this->accessControl->check('agent:execute', $this->getType());
    }

    public function executeTask(string $taskName, array $parameters = []): array
    {
        try {
            $result = $this->taskManager->executeTask($taskName, $parameters);
            
            if ($result['status'] === 'failed') {
                $this->auditLogger->log('failure', "Agent {$this->getType()} task failed", [
                    'agent_type' => $this->getType(),
                    'task' => $taskName,
                    'reason' => $result['reason'] ?? 'Unknown failure',
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->auditLogger->log('error', "Agent {$this->getType()} failed to execute task", [
                'agent_type' => $this->getType(),
                'task' => $taskName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    public function requiresHumanReview(string $action): bool
    {
        return $this->accessControl->requiresHumanReview('agent:critical_action', [
            'agent_type' => $this->getType(),
            'action' => $action,
        ]);
    }

    public function setTenant(string $tenantId): void
    {
        $this->tenant = $tenantId;
        $this->accessControl->validateTenantAccess($tenantId);
    }

    public function getTenant(): string
    {
        return $this->tenant;
    }

    public function logAudit(string $action, array $context = []): void
    {
        $this->auditLogger->log('audit', "Agent {$this->getType()} {$action}", array_merge($context, [
            'agent_type' => $this->getType(),
            'timestamp' => now()->toIso8601String(),
            'user_id' => $this->accessControl->getCurrentUser(),
            'tenant_id' => $this->tenant,
        ]));
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->getCapabilities());
    }

    public function canExecuteInEnvironment(): bool
    {
        $environment = Config::get('mcp.agentic.environment');
        $allowedEnvironments = Config::get('mcp.agentic.allowed_environments', ['testing', 'staging']);
        
        return in_array($environment, $allowedEnvironments);
    }

    public function start(): void
    {
        if ($this->isRunning) {
            return;
        }
        
        $this->isRunning = true;
        
        $this->auditLogger->log('agentic', "Agent {$this->getType()} started", [
            'agent_type' => $this->getType(),
        ]);
    }

    public function stop(): void
    {
        if (!$this->isRunning) {
            return;
        }
        
        $this->isRunning = false;
        
        $this->auditLogger->log('agentic', "Agent {$this->getType()} stopped", [
            'agent_type' => $this->getType(),
        ]);
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }
} 