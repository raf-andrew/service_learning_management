<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\UserRole;
use App\Modules\Auth\Models\RolePermission;
use App\Modules\Shared\Services\Core\AuditService;

class AuthorizationService
{
    /**
     * The audit service instance.
     */
    protected AuditService $auditService;

    /**
     * Create a new authorization service instance.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Check if a user has a specific permission.
     */
    public function hasPermission($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // Check cache first
        $cacheKey = "user_permission_{$user->id}_{$permission}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        // Check if user has admin role
        if ($this->hasRole($user, 'admin')) {
            $result = true;
        } else {
            // Check user's roles for the permission
            $result = $this->checkUserPermission($user, $permission);
        }

        // Cache the result
        Cache::put($cacheKey, $result, now()->addMinutes(30));

        return $result;
    }

    /**
     * Check if a user has any of the given permissions.
     */
    public function hasAnyPermission($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user has all of the given permissions.
     */
    public function hasAllPermissions($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a user has a specific role.
     */
    public function hasRole($user, string $roleName): bool
    {
        if (!$user) {
            return false;
        }

        // Check cache first
        $cacheKey = "user_role_{$user->id}_{$roleName}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        // Check user's roles
        $result = $user->roles()
            ->where('name', $roleName)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();

        // Cache the result
        Cache::put($cacheKey, $result, now()->addMinutes(30));

        return $result;
    }

    /**
     * Check if a user has any of the given roles.
     */
    public function hasAnyRole($user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user has all of the given roles.
     */
    public function hasAllRoles($user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if (!$this->hasRole($user, $roleName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for a user.
     */
    public function getUserPermissions($user): array
    {
        if (!$user) {
            return [];
        }

        // Check cache first
        $cacheKey = "user_permissions_{$user->id}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        $permissions = [];

        // Get permissions from user's roles
        $userRoles = $user->roles()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with(['permissions' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        foreach ($userRoles as $role) {
            // Add role's direct permissions
            if (is_array($role->permissions)) {
                $permissions = array_merge($permissions, $role->permissions);
            }

            // Add related permissions
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }

        // Remove duplicates and cache
        $permissions = array_unique($permissions);
        Cache::put($cacheKey, $permissions, now()->addMinutes(30));

        return $permissions;
    }

    /**
     * Get all roles for a user.
     */
    public function getUserRoles($user): array
    {
        if (!$user) {
            return [];
        }

        // Check cache first
        $cacheKey = "user_roles_{$user->id}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        $roles = $user->roles()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->pluck('name')
            ->toArray();

        // Cache the result
        Cache::put($cacheKey, $roles, now()->addMinutes(30));

        return $roles;
    }

    /**
     * Grant a role to a user.
     */
    public function grantRole($user, string $roleName, ?int $grantedBy = null, ?string $expiresAt = null): array
    {
        try {
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            $role = Role::where('name', $roleName)->where('is_active', true)->first();

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Role not found or inactive',
                ];
            }

            // Check if user already has this role
            if ($this->hasRole($user, $roleName)) {
                return [
                    'success' => false,
                    'message' => 'User already has this role',
                ];
            }

            // Create user role
            UserRole::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'expires_at' => $expiresAt ? now()->parse($expiresAt) : null,
                'is_active' => true,
            ]);

            // Clear user caches
            $this->clearUserCaches($user->id);

            $this->auditService->log('role_granted', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $roleName,
                'granted_by' => $grantedBy,
                'expires_at' => $expiresAt,
            ]);

            return [
                'success' => true,
                'message' => "Role '{$roleName}' granted successfully",
            ];

        } catch (\Exception $e) {
            Log::error('Role grant error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'role_name' => $roleName,
            ]);

            return [
                'success' => false,
                'message' => 'Role grant error occurred',
            ];
        }
    }

    /**
     * Revoke a role from a user.
     */
    public function revokeRole($user, string $roleName, ?int $revokedBy = null): array
    {
        try {
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Role not found',
                ];
            }

            // Check if user has this role
            if (!$this->hasRole($user, $roleName)) {
                return [
                    'success' => false,
                    'message' => 'User does not have this role',
                ];
            }

            // Check if role can be revoked
            $userRole = UserRole::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->where('is_active', true)
                ->first();

            if (!$userRole || !$userRole->canBeRevoked()) {
                return [
                    'success' => false,
                    'message' => 'Role cannot be revoked',
                ];
            }

            // Deactivate user role
            $userRole->update([
                'is_active' => false,
            ]);

            // Clear user caches
            $this->clearUserCaches($user->id);

            $this->auditService->log('role_revoked', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $roleName,
                'revoked_by' => $revokedBy,
            ]);

            return [
                'success' => true,
                'message' => "Role '{$roleName}' revoked successfully",
            ];

        } catch (\Exception $e) {
            Log::error('Role revoke error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'role_name' => $roleName,
            ]);

            return [
                'success' => false,
                'message' => 'Role revoke error occurred',
            ];
        }
    }

    /**
     * Grant a permission to a role.
     */
    public function grantPermissionToRole(string $roleName, string $permissionName, ?int $grantedBy = null, ?string $expiresAt = null): array
    {
        try {
            $role = Role::where('name', $roleName)->where('is_active', true)->first();

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Role not found or inactive',
                ];
            }

            $permission = Permission::where('name', $permissionName)->where('is_active', true)->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'message' => 'Permission not found or inactive',
                ];
            }

            // Check if role already has this permission
            if ($role->hasPermission($permissionName)) {
                return [
                    'success' => false,
                    'message' => 'Role already has this permission',
                ];
            }

            // Grant permission to role
            $role->grantPermission($permissionName);

            // Create role permission record
            RolePermission::create([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'expires_at' => $expiresAt ? now()->parse($expiresAt) : null,
                'is_active' => true,
            ]);

            // Clear role caches
            $this->clearRoleCaches($role->id);

            $this->auditService->log('permission_granted_to_role', [
                'role_id' => $role->id,
                'role_name' => $roleName,
                'permission_id' => $permission->id,
                'permission_name' => $permissionName,
                'granted_by' => $grantedBy,
                'expires_at' => $expiresAt,
            ]);

            return [
                'success' => true,
                'message' => "Permission '{$permissionName}' granted to role '{$roleName}'",
            ];

        } catch (\Exception $e) {
            Log::error('Permission grant to role error', [
                'error' => $e->getMessage(),
                'role_name' => $roleName,
                'permission_name' => $permissionName,
            ]);

            return [
                'success' => false,
                'message' => 'Permission grant error occurred',
            ];
        }
    }

    /**
     * Revoke a permission from a role.
     */
    public function revokePermissionFromRole(string $roleName, string $permissionName, ?int $revokedBy = null): array
    {
        try {
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Role not found',
                ];
            }

            $permission = Permission::where('name', $permissionName)->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'message' => 'Permission not found',
                ];
            }

            // Check if role has this permission
            if (!$role->hasPermission($permissionName)) {
                return [
                    'success' => false,
                    'message' => 'Role does not have this permission',
                ];
            }

            // Check if permission can be revoked
            $rolePermission = RolePermission::where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->where('is_active', true)
                ->first();

            if (!$rolePermission || !$rolePermission->canBeRevoked()) {
                return [
                    'success' => false,
                    'message' => 'Permission cannot be revoked',
                ];
            }

            // Revoke permission from role
            $role->revokePermission($permissionName);

            // Deactivate role permission record
            $rolePermission->update([
                'is_active' => false,
            ]);

            // Clear role caches
            $this->clearRoleCaches($role->id);

            $this->auditService->log('permission_revoked_from_role', [
                'role_id' => $role->id,
                'role_name' => $roleName,
                'permission_id' => $permission->id,
                'permission_name' => $permissionName,
                'revoked_by' => $revokedBy,
            ]);

            return [
                'success' => true,
                'message' => "Permission '{$permissionName}' revoked from role '{$roleName}'",
            ];

        } catch (\Exception $e) {
            Log::error('Permission revoke from role error', [
                'error' => $e->getMessage(),
                'role_name' => $roleName,
                'permission_name' => $permissionName,
            ]);

            return [
                'success' => false,
                'message' => 'Permission revoke error occurred',
            ];
        }
    }

    /**
     * Check if a user has module access.
     */
    public function hasModuleAccess($user, string $module): bool
    {
        return $this->hasAnyPermission($user, [
            "{$module}.view",
            "{$module}.manage",
            "{$module}.admin",
        ]);
    }

    /**
     * Get users with a specific permission.
     */
    public function getUsersWithPermission(string $permission): array
    {
        $permissionModel = Permission::where('name', $permission)->first();

        if (!$permissionModel) {
            return [];
        }

        $userModel = config('auth.providers.users.model', 'App\Models\User');

        return $userModel::whereHas('roles.permissions', function ($query) use ($permissionModel) {
            $query->where('permission_id', $permissionModel->id);
        })->get()->toArray();
    }

    /**
     * Get users with a specific role.
     */
    public function getUsersWithRole(string $roleName): array
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return [];
        }

        $userModel = config('auth.providers.users.model', 'App\Models\User');

        return $userModel::whereHas('roles', function ($query) use ($role) {
            $query->where('role_id', $role->id)
                  ->where('is_active', true)
                  ->where(function ($q) {
                      $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                  });
        })->get()->toArray();
    }

    /**
     * Check user permission directly.
     */
    protected function checkUserPermission($user, string $permission): bool
    {
        return $user->roles()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                      ->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Clear user caches.
     */
    protected function clearUserCaches(int $userId): void
    {
        $patterns = [
            "user_permission_{$userId}_*",
            "user_role_{$userId}_*",
            "user_permissions_{$userId}",
            "user_roles_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::get($pattern) ?: [];
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Clear role caches.
     */
    protected function clearRoleCaches(int $roleId): void
    {
        // Clear caches for all users with this role
        $userModel = config('auth.providers.users.model', 'App\Models\User');
        $users = $userModel::whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();

        foreach ($users as $user) {
            $this->clearUserCaches($user->id);
        }
    }
} 