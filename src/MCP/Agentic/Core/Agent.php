<?php

namespace App\MCP\Agentic\Core;

use App\MCP\Core\BaseAgent;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;

abstract class Agent extends BaseAgent
{
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;
    protected TaskManager $taskManager;
    protected bool $isRunning = false;

    public function __construct(
        AuditLogger $auditLogger,
        AccessControl $accessControl,
        TaskManager $taskManager
    ) {
        $this->auditLogger = $auditLogger;
        $this->accessControl = $accessControl;
        $this->taskManager = $taskManager;
        parent::__construct();
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function start(): void
    {
        if ($this->isRunning) {
            throw new \RuntimeException("Agent is already running");
        }

        $this->isRunning = true;
        $this->auditLogger->log('agentic', "Agent {$this->id} started", [
            'agent_id' => $this->id,
            'category' => $this->category,
        ]);
    }

    public function stop(): void
    {
        if (!$this->isRunning) {
            throw new \RuntimeException("Agent is not running");
        }

        $this->isRunning = false;
        $this->auditLogger->log('agentic', "Agent {$this->id} stopped", [
            'agent_id' => $this->id,
            'category' => $this->category,
        ]);
    }

    public function getStatus(): array
    {
        return array_merge(parent::getStatus(), [
            'is_running' => $this->isRunning,
        ]);
    }

    protected function logAudit(string $event, array $context = []): void
    {
        parent::logAudit($event, $context);
        $this->auditLogger->log('agentic', $event, array_merge($context, [
            'agent_id' => $this->id,
            'category' => $this->category,
        ]));
    }

    protected function hasPermission(string $action): bool
    {
        return $this->accessControl->check('agent:' . $action, $this->id);
    }

    protected function requiresHumanReview(string $action, array $context = []): bool
    {
        return $this->accessControl->requiresHumanReview('agent:' . $action, array_merge($context, [
            'agent_id' => $this->id,
            'category' => $this->category,
        ]));
    }
} 