<?php

namespace App\MCP\Core;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\MCP\Core\Services\AuditLogger;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;

class MCPServer
{
    protected bool $enabled;
    protected array $agents = [];
    protected array $services = [];
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;
    protected TaskManager $taskManager;
    protected HealthMonitor $healthMonitor;

    public function __construct(
        AuditLogger $auditLogger,
        AccessControl $accessControl,
        TaskManager $taskManager,
        HealthMonitor $healthMonitor
    ) {
        $this->enabled = Config::get('app.env') !== 'production';
        $this->auditLogger = $auditLogger;
        $this->accessControl = $accessControl;
        $this->taskManager = $taskManager;
        $this->healthMonitor = $healthMonitor;
        $this->initialize();
    }

    protected function initialize(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->registerCoreServices();
        $this->initializeDefaultRoles();
        $this->initializeDefaultPermissions();
    }

    protected function registerCoreServices(): void
    {
        $this->registerService('audit_logger', $this->auditLogger);
        $this->registerService('access_control', $this->accessControl);
        $this->registerService('task_manager', $this->taskManager);
        $this->registerService('health_monitor', $this->healthMonitor);
    }

    protected function initializeDefaultRoles(): void
    {
        $this->accessControl->registerCapability('admin', ['manage_agents', 'manage_services', 'view_logs', 'manage_roles']);
        $this->accessControl->registerCapability('user', ['use_agents']);
    }

    protected function initializeDefaultPermissions(): void
    {
        // Admin permissions
        $this->accessControl->registerPolicy('manage_agents', 'admin', function() { return true; });
        $this->accessControl->registerPolicy('manage_services', 'admin', function() { return true; });
        $this->accessControl->registerPolicy('view_logs', 'admin', function() { return true; });
        $this->accessControl->registerPolicy('manage_roles', 'admin', function() { return true; });

        // User permissions
        $this->accessControl->registerPolicy('use_agents', 'user', function() { return true; });
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function hasAgent(string $name): bool
    {
        return isset($this->agents[$name]);
    }

    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }

    public function registerAgent(string $name, object $agent): void
    {
        if (!$this->enabled) {
            throw new \RuntimeException("MCP Server is disabled in production environment");
        }

        if ($this->hasAgent($name)) {
            throw new \RuntimeException("Agent {$name} is already registered");
        }

        $this->agents[$name] = $agent;
        $this->auditLogger->log('agentic', "Agent {$name} registered", [
            'agent' => $name,
        ]);

        // Monitor agent health
        $this->healthMonitor->checkAgentHealth($name, $agent);
    }

    public function registerService(string $name, object $service): void
    {
        if (!$this->enabled) {
            throw new \RuntimeException("MCP Server is disabled in production environment");
        }

        if ($this->hasService($name)) {
            throw new \RuntimeException("Service {$name} is already registered");
        }

        $this->services[$name] = $service;
        $this->auditLogger->log('agentic', "Service {$name} registered", [
            'service' => $name,
        ]);

        // Monitor service health
        $this->healthMonitor->checkServiceHealth($name, $service);
    }

    public function hasRole(string $role): bool
    {
        return $this->accessControl->getCapability($role) !== null;
    }

    public function hasPermission(string $role, string $permission): bool
    {
        $capability = $this->accessControl->getCapability($role);
        return $capability && in_array($permission, $capability['permissions']);
    }

    public function checkPermission(string $role, string $permission): bool
    {
        return $this->accessControl->check($permission, $role);
    }

    public function checkRole(string $role, string $requiredRole): bool
    {
        return $role === $requiredRole;
    }

    public function getAgents(): array
    {
        return $this->agents;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getSystemHealth(): array
    {
        return $this->healthMonitor->getSystemHealth();
    }

    public function checkAgentHealth(string $name): array
    {
        if (!$this->hasAgent($name)) {
            throw new \RuntimeException("Agent {$name} is not registered");
        }

        return $this->healthMonitor->checkAgentHealth($name, $this->agents[$name]);
    }

    public function checkServiceHealth(string $name): array
    {
        if (!$this->hasService($name)) {
            throw new \RuntimeException("Service {$name} is not registered");
        }

        return $this->healthMonitor->checkServiceHealth($name, $this->services[$name]);
    }
} 