<?php

namespace App\MCP\Core;

use App\MCP\Interfaces\AgentInterface;
use Illuminate\Support\Facades\Log;

abstract class BaseAgent implements AgentInterface
{
    protected string $id;
    protected string $category;
    protected array $capabilities = [];
    protected string $accessLevel;
    protected array $auditLog = [];
    protected array $config = [];
    protected array $metrics = [];

    public function __construct()
    {
        $this->id = uniqid($this->getCategory() . '_');
        $this->initialize();
    }

    abstract protected function initialize(): void;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities);
    }

    public function execute(string $action, array $parameters = []): mixed
    {
        if (!$this->hasPermission($action)) {
            $this->logAudit('permission_denied', [
                'action' => $action,
                'parameters' => $parameters
            ]);
            throw new \RuntimeException("Permission denied for action: {$action}");
        }

        if (!$this->hasCapability($action)) {
            $this->logAudit('capability_not_found', [
                'action' => $action,
                'parameters' => $parameters
            ]);
            throw new \RuntimeException("Capability not found: {$action}");
        }

        $this->logAudit('action_executed', [
            'action' => $action,
            'parameters' => $parameters
        ]);

        return $this->performAction($action, $parameters);
    }

    abstract protected function performAction(string $action, array $parameters): mixed;

    public function getStatus(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'capabilities' => $this->capabilities,
            'access_level' => $this->accessLevel,
            'audit_log_count' => count($this->auditLog)
        ];
    }

    public function getAccessLevel(): string
    {
        return $this->accessLevel;
    }

    public function hasPermission(string $action): bool
    {
        // Implement permission checking logic based on access level
        return true; // Default implementation
    }

    public function getAuditLog(): array
    {
        return $this->auditLog;
    }

    protected function logAudit(string $event, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'context' => $context
        ];

        $this->auditLog[] = $logEntry;
        Log::info("Agent {$this->id} - {$event}", $context);
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function recordMetric(string $name, $value): void
    {
        $this->metrics[$name] = [
            'value' => $value,
            'timestamp' => now(),
        ];
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function performAction(string $action, array $params = []): array
    {
        if (!$this->hasCapability($action)) {
            throw new \InvalidArgumentException("Agent does not have capability: {$action}");
        }

        try {
            $result = $this->executeAction($action, $params);
            $this->recordMetric($action, [
                'status' => 'success',
                'duration' => $this->calculateDuration(),
                'params' => $params,
            ]);
            return $result;
        } catch (\Throwable $e) {
            Log::error("Agent action failed: {$action}", [
                'category' => $this->category,
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            
            $this->recordMetric($action, [
                'status' => 'error',
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            
            throw $e;
        }
    }

    abstract protected function executeAction(string $action, array $params): array;

    protected function calculateDuration(): float
    {
        // Implement duration calculation logic
        return 0.0;
    }

    protected function validateParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                throw new \InvalidArgumentException("Missing required parameter: {$param}");
            }
        }
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->category}] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->category}] {$message}", $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning("[{$this->category}] {$message}", $context);
    }
} 