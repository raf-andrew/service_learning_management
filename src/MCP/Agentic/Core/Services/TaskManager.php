<?php

namespace App\MCP\Agentic\Core\Services;

use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class TaskManager
{
    protected Collection $tasks;
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;

    public function __construct(
        AuditLogger $auditLogger,
        AccessControl $accessControl
    ) {
        $this->tasks = new Collection();
        $this->auditLogger = $auditLogger;
        $this->accessControl = $accessControl;
    }

    public function registerTask(string $name, callable $handler, array $capabilities = []): void
    {
        if ($this->tasks->has($name)) {
            throw new \RuntimeException("Task {$name} already registered");
        }

        $this->tasks->put($name, [
            'handler' => $handler,
            'capabilities' => $capabilities,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log('agentic', "Task {$name} registered", [
            'task' => $name,
            'capabilities' => $capabilities,
        ]);
    }

    public function executeTask(string $name, array $parameters = []): array
    {
        if (!$this->tasks->has($name)) {
            throw new \RuntimeException("Task {$name} not found");
        }

        $task = $this->tasks->get($name);

        // Check if task requires human review
        if ($this->accessControl->requiresHumanReview('task:execute', [
            'task' => $name,
            'parameters' => $parameters,
        ])) {
            return [
                'status' => 'pending_review',
                'message' => 'Task requires human review',
            ];
        }

        // Validate capabilities
        foreach ($task['capabilities'] as $capability) {
            if (!$this->accessControl->check('capability:' . $capability, $name)) {
                return [
                    'status' => 'failed',
                    'reason' => "Missing capability: {$capability}",
                ];
            }
        }

        try {
            $result = call_user_func($task['handler'], $parameters);

            $this->auditLogger->log('agentic', "Task {$name} executed", [
                'task' => $name,
                'parameters' => $parameters,
                'result' => $result,
            ]);

            return [
                'status' => 'success',
                'result' => $result,
            ];
        } catch (\Exception $e) {
            $this->auditLogger->log('error', "Task {$name} failed", [
                'task' => $name,
                'parameters' => $parameters,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'reason' => $e->getMessage(),
            ];
        }
    }

    public function getTask(string $name): ?array
    {
        return $this->tasks->get($name);
    }

    public function getAllTasks(): Collection
    {
        return $this->tasks;
    }

    public function removeTask(string $name): void
    {
        if (!$this->tasks->has($name)) {
            throw new \RuntimeException("Task {$name} not found");
        }

        $this->tasks->forget($name);

        $this->auditLogger->log('agentic', "Task {$name} removed", [
            'task' => $name,
        ]);
    }

    public function updateTask(string $name, callable $handler, array $capabilities = []): void
    {
        if (!$this->tasks->has($name)) {
            throw new \RuntimeException("Task {$name} not found");
        }

        $this->tasks->put($name, [
            'handler' => $handler,
            'capabilities' => $capabilities,
            'created_at' => $this->tasks->get($name)['created_at'],
            'updated_at' => now(),
        ]);

        $this->auditLogger->log('agentic', "Task {$name} updated", [
            'task' => $name,
            'capabilities' => $capabilities,
        ]);
    }

    public function validateTask(string $name, array $parameters = []): array
    {
        if (!$this->tasks->has($name)) {
            return [
                'valid' => false,
                'reason' => "Task {$name} not found",
            ];
        }

        $task = $this->tasks->get($name);

        // Check capabilities
        foreach ($task['capabilities'] as $capability) {
            if (!$this->accessControl->check('capability:' . $capability, $name)) {
                return [
                    'valid' => false,
                    'reason' => "Missing capability: {$capability}",
                ];
            }
        }

        // Check if task requires human review
        if ($this->accessControl->requiresHumanReview('task:execute', [
            'task' => $name,
            'parameters' => $parameters,
        ])) {
            return [
                'valid' => true,
                'requires_review' => true,
            ];
        }

        return [
            'valid' => true,
            'requires_review' => false,
        ];
    }
} 