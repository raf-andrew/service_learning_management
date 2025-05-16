<?php

namespace App\MCP\Agentic\Core;

use App\MCP\Core\Service as BaseService;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;

abstract class Service extends BaseService
{
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;
    protected TaskManager $taskManager;

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

    public function getStatus(): array
    {
        return array_merge(parent::getStatus(), [
            'audit_log_count' => count($this->auditLogger->getLogs($this->name)),
        ]);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        parent::logInfo($message, $context);
        $this->auditLogger->log('service', $message, array_merge($context, [
            'service_id' => $this->id,
            'name' => $this->name,
        ]));
    }

    protected function logError(string $message, array $context = []): void
    {
        parent::logError($message, $context);
        $this->auditLogger->log('error', $message, array_merge($context, [
            'service_id' => $this->id,
            'name' => $this->name,
        ]));
    }

    protected function logWarning(string $message, array $context = []): void
    {
        parent::logWarning($message, $context);
        $this->auditLogger->log('warning', $message, array_merge($context, [
            'service_id' => $this->id,
            'name' => $this->name,
        ]));
    }

    protected function hasPermission(string $action): bool
    {
        return $this->accessControl->check('service:' . $action, $this->id);
    }

    protected function requiresHumanReview(string $action, array $context = []): bool
    {
        return $this->accessControl->requiresHumanReview('service:' . $action, array_merge($context, [
            'service_id' => $this->id,
            'name' => $this->name,
        ]));
    }
} 