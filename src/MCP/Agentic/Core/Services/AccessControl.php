<?php

namespace App\MCP\Agentic\Core\Services;

use App\MCP\Agentic\Core\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class AccessControl
{
    protected Collection $policies;
    protected Collection $capabilities;
    protected Collection $humanReviewRules;
    protected AuditLogger $auditLogger;
    protected string $currentUser;
    protected string $currentTenant;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->policies = new Collection();
        $this->capabilities = new Collection();
        $this->humanReviewRules = new Collection();
        $this->auditLogger = $auditLogger;
    }

    public function check(string $action, string $resource): bool
    {
        $policy = $this->getPolicy($action, $resource);
        
        if (!$policy) {
            $this->auditLogger->log('security', "Access denied: No policy found", [
                'action' => $action,
                'resource' => $resource,
                'user' => $this->currentUser,
                'tenant' => $this->currentTenant,
            ]);
            return false;
        }

        $result = $policy($this->currentUser, $this->currentTenant);

        $this->auditLogger->log('security', $result ? "Access granted" : "Access denied", [
            'action' => $action,
            'resource' => $resource,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
        ]);

        return $result;
    }

    public function requiresHumanReview(string $action, array $context = []): bool
    {
        $rule = $this->getHumanReviewRule($action);
        
        if (!$rule) {
            return false;
        }

        $result = $rule($context);

        $this->auditLogger->log('security', $result ? "Human review required" : "No human review required", [
            'action' => $action,
            'context' => $context,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
        ]);

        return $result;
    }

    public function registerPolicy(string $action, string $resource, callable $policy): void
    {
        $key = "{$action}:{$resource}";
        
        $this->policies->put($key, $policy);

        $this->auditLogger->log('security', "Policy registered", [
            'action' => $action,
            'resource' => $resource,
        ]);
    }

    public function registerHumanReviewRule(string $action, callable $rule): void
    {
        $this->humanReviewRules->put($action, $rule);

        $this->auditLogger->log('security', "Human review rule registered", [
            'action' => $action,
        ]);
    }

    public function registerCapability(string $name, array $permissions = []): void
    {
        $this->capabilities->put($name, [
            'permissions' => $permissions,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log('security', "Capability registered", [
            'capability' => $name,
            'permissions' => $permissions,
        ]);
    }

    public function validateTenantAccess(string $tenantId): void
    {
        if (!Config::get('mcp.agentic.tenants.' . $tenantId)) {
            throw new \RuntimeException("Invalid tenant: {$tenantId}");
        }

        $this->currentTenant = $tenantId;
    }

    public function setCurrentUser(string $userId): void
    {
        $this->currentUser = $userId;
    }

    public function getCurrentUser(): string
    {
        return $this->currentUser;
    }

    public function getCurrentTenant(): string
    {
        return $this->currentTenant;
    }

    protected function getPolicy(string $action, string $resource): ?callable
    {
        $key = "{$action}:{$resource}";
        return $this->policies->get($key);
    }

    protected function getHumanReviewRule(string $action): ?callable
    {
        return $this->humanReviewRules->get($action);
    }

    public function getCapability(string $name): ?array
    {
        return $this->capabilities->get($name);
    }

    public function getAllCapabilities(): Collection
    {
        return $this->capabilities;
    }

    public function removePolicy(string $action, string $resource): void
    {
        $key = "{$action}:{$resource}";
        $this->policies->forget($key);

        $this->auditLogger->log('security', "Policy removed", [
            'action' => $action,
            'resource' => $resource,
        ]);
    }

    public function removeHumanReviewRule(string $action): void
    {
        $this->humanReviewRules->forget($action);

        $this->auditLogger->log('security', "Human review rule removed", [
            'action' => $action,
        ]);
    }

    public function removeCapability(string $name): void
    {
        $this->capabilities->forget($name);

        $this->auditLogger->log('security', "Capability removed", [
            'capability' => $name,
        ]);
    }

    public function updateCapability(string $name, array $permissions = []): void
    {
        if (!$this->capabilities->has($name)) {
            throw new \RuntimeException("Capability {$name} not found");
        }

        $this->capabilities->put($name, [
            'permissions' => $permissions,
            'created_at' => $this->capabilities->get($name)['created_at'],
            'updated_at' => now(),
        ]);

        $this->auditLogger->log('security', "Capability updated", [
            'capability' => $name,
            'permissions' => $permissions,
        ]);
    }
} 