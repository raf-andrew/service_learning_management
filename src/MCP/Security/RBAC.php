<?php

namespace App\MCP\Security;

use App\MCP\Core\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RBAC
{
    protected Collection $roles;
    protected Collection $permissions;
    protected Collection $rolePermissions;
    protected Collection $userRoles;
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->roles = new Collection();
        $this->permissions = new Collection();
        $this->rolePermissions = new Collection();
        $this->userRoles = new Collection();
        $this->auditLogger = $auditLogger;
    }

    public function createRole(string $name, string $description = ''): bool
    {
        if ($this->roles->has($name)) {
            Log::warning("Role {$name} already exists");
            return false;
        }

        $this->roles->put($name, [
            'name' => $name,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log('security', "Role {$name} created", [
            'role' => $name,
            'description' => $description,
        ]);

        return true;
    }

    public function createPermission(string $name, string $description = ''): bool
    {
        if ($this->permissions->has($name)) {
            Log::warning("Permission {$name} already exists");
            return false;
        }

        $this->permissions->put($name, [
            'name' => $name,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log('security', "Permission {$name} created", [
            'permission' => $name,
            'description' => $description,
        ]);

        return true;
    }

    public function assignPermissionToRole(string $roleName, string $permissionName): bool
    {
        if (!$this->roles->has($roleName)) {
            Log::warning("Role {$roleName} does not exist");
            return false;
        }

        if (!$this->permissions->has($permissionName)) {
            Log::warning("Permission {$permissionName} does not exist");
            return false;
        }

        if (!$this->rolePermissions->has($roleName)) {
            $this->rolePermissions->put($roleName, new Collection());
        }

        $rolePerms = $this->rolePermissions->get($roleName);
        if (!$rolePerms->contains($permissionName)) {
            $rolePerms->push($permissionName);
            
            $this->auditLogger->log('security', "Permission {$permissionName} assigned to role {$roleName}", [
                'role' => $roleName,
                'permission' => $permissionName,
            ]);
        }

        return true;
    }

    public function assignRoleToUser(string $userId, string $roleName): bool
    {
        if (!$this->roles->has($roleName)) {
            Log::warning("Role {$roleName} does not exist");
            return false;
        }

        if (!$this->userRoles->has($userId)) {
            $this->userRoles->put($userId, new Collection());
        }

        $userRoles = $this->userRoles->get($userId);
        if (!$userRoles->contains($roleName)) {
            $userRoles->push($roleName);
            
            $this->auditLogger->log('security', "Role {$roleName} assigned to user {$userId}", [
                'user_id' => $userId,
                'role' => $roleName,
            ]);

            // Clear user permissions cache
            $this->clearUserPermissionsCache($userId);
        }

        return true;
    }

    public function hasPermission(string $userId, string $permissionName): bool
    {
        // Check cache first
        $cacheKey = "user_permissions_{$userId}";
        $userPermissions = Cache::get($cacheKey);

        if ($userPermissions === null) {
            $userPermissions = $this->calculateUserPermissions($userId);
            Cache::put($cacheKey, $userPermissions, now()->addMinutes(60));
        }

        return in_array($permissionName, $userPermissions);
    }

    public function hasRole(string $userId, string $roleName): bool
    {
        return $this->userRoles->has($userId) && 
               $this->userRoles->get($userId)->contains($roleName);
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function getUserRoles(string $userId): Collection
    {
        return $this->userRoles->get($userId, new Collection());
    }

    public function getRolePermissions(string $roleName): Collection
    {
        return $this->rolePermissions->get($roleName, new Collection());
    }

    protected function calculateUserPermissions(string $userId): array
    {
        $permissions = [];
        $userRoles = $this->getUserRoles($userId);

        foreach ($userRoles as $roleName) {
            $rolePermissions = $this->getRolePermissions($roleName);
            $permissions = array_merge($permissions, $rolePermissions->toArray());
        }

        return array_unique($permissions);
    }

    protected function clearUserPermissionsCache(string $userId): void
    {
        Cache::forget("user_permissions_{$userId}");
    }

    public function removeRole(string $name): bool
    {
        if (!$this->roles->has($name)) {
            Log::warning("Role {$name} does not exist");
            return false;
        }

        $this->roles->forget($name);
        $this->rolePermissions->forget($name);

        // Remove role from all users
        foreach ($this->userRoles as $userId => $roles) {
            $roles->reject(function ($role) use ($name) {
                return $role === $name;
            });
        }

        $this->auditLogger->log('security', "Role {$name} removed", [
            'role' => $name,
        ]);

        return true;
    }

    public function removePermission(string $name): bool
    {
        if (!$this->permissions->has($name)) {
            Log::warning("Permission {$name} does not exist");
            return false;
        }

        $this->permissions->forget($name);

        // Remove permission from all roles
        foreach ($this->rolePermissions as $roleName => $permissions) {
            $permissions->reject(function ($permission) use ($name) {
                return $permission === $name;
            });
        }

        $this->auditLogger->log('security', "Permission {$name} removed", [
            'permission' => $name,
        ]);

        return true;
    }

    public function removeRoleFromUser(string $userId, string $roleName): bool
    {
        if (!$this->userRoles->has($userId)) {
            Log::warning("User {$userId} has no roles");
            return false;
        }

        $userRoles = $this->userRoles->get($userId);
        if (!$userRoles->contains($roleName)) {
            Log::warning("User {$userId} does not have role {$roleName}");
            return false;
        }

        $userRoles = $userRoles->reject(function ($role) use ($roleName) {
            return $role === $roleName;
        });

        $this->userRoles->put($userId, $userRoles);

        $this->auditLogger->log('security', "Role {$roleName} removed from user {$userId}", [
            'user_id' => $userId,
            'role' => $roleName,
        ]);

        // Clear user permissions cache
        $this->clearUserPermissionsCache($userId);

        return true;
    }

    public function removePermissionFromRole(string $roleName, string $permissionName): bool
    {
        if (!$this->rolePermissions->has($roleName)) {
            Log::warning("Role {$roleName} has no permissions");
            return false;
        }

        $rolePerms = $this->rolePermissions->get($roleName);
        if (!$rolePerms->contains($permissionName)) {
            Log::warning("Role {$roleName} does not have permission {$permissionName}");
            return false;
        }

        $rolePerms = $rolePerms->reject(function ($permission) use ($permissionName) {
            return $permission === $permissionName;
        });

        $this->rolePermissions->put($roleName, $rolePerms);

        $this->auditLogger->log('security', "Permission {$permissionName} removed from role {$roleName}", [
            'role' => $roleName,
            'permission' => $permissionName,
        ]);

        return true;
    }
} 