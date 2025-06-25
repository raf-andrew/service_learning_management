<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'auth_roles';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system_role', // Whether this is a system-defined role
        'is_active',
        'permissions', // JSON array of permission names
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * Get the users that have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', 'App\Models\User'),
            'auth_user_roles',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get the role permissions for this role.
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Get the permissions for this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'auth_role_permissions',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Scope a query to only include active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include system roles.
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Scope a query to only include custom roles.
     */
    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    /**
     * Scope a query to only include roles by name.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Check if the role is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the role is a system role.
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    /**
     * Check if the role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Check direct permissions array
        if (is_array($this->permissions) && in_array($permission, $this->permissions)) {
            return true;
        }

        // Check related permissions
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if the role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grant a permission to this role.
     */
    public function grantPermission(string $permission): bool
    {
        // Add to permissions array
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }

        // Add to related permissions
        $permissionModel = Permission::where('name', $permission)->first();
        if ($permissionModel && !$this->permissions()->where('permission_id', $permissionModel->id)->exists()) {
            $this->permissions()->attach($permissionModel->id);
        }

        return true;
    }

    /**
     * Revoke a permission from this role.
     */
    public function revokePermission(string $permission): bool
    {
        // Remove from permissions array
        $permissions = $this->permissions ?? [];
        $permissions = array_diff($permissions, [$permission]);
        $this->permissions = array_values($permissions);
        $this->save();

        // Remove from related permissions
        $permissionModel = Permission::where('name', $permission)->first();
        if ($permissionModel) {
            $this->permissions()->detach($permissionModel->id);
        }

        return true;
    }

    /**
     * Sync permissions for this role.
     */
    public function syncPermissions(array $permissions): bool
    {
        // Update permissions array
        $this->permissions = $permissions;
        $this->save();

        // Sync related permissions
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->sync($permissionIds);

        return true;
    }

    /**
     * Get the role's permissions as a formatted string.
     */
    public function getPermissionsString(): string
    {
        if (empty($this->permissions)) {
            return 'No permissions';
        }

        return implode(', ', $this->permissions);
    }

    /**
     * Get the role's display name.
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?: ucfirst(str_replace('_', ' ', $this->name));
    }

    /**
     * Get the number of users with this role.
     */
    public function getUserCount(): int
    {
        return $this->users()->count();
    }

    /**
     * Check if the role can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // System roles cannot be deleted
        if ($this->is_system_role) {
            return false;
        }

        // Roles with users cannot be deleted
        if ($this->getUserCount() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the role's metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set the role's metadata value.
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Get the role's creation age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if the role is the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->name === 'admin';
    }

    /**
     * Check if the role is the user role.
     */
    public function isUser(): bool
    {
        return $this->name === 'user';
    }

    /**
     * Get the role's module-specific permissions.
     */
    public function getModulePermissions(string $module): array
    {
        $modulePermissions = [];
        $permissions = $this->permissions ?? [];

        foreach ($permissions as $permission) {
            if (str_starts_with($permission, $module . '.')) {
                $modulePermissions[] = $permission;
            }
        }

        return $modulePermissions;
    }

    /**
     * Check if the role has module access.
     */
    public function hasModuleAccess(string $module): bool
    {
        return $this->hasAnyPermission([
            $module . '.view',
            $module . '.manage',
            $module . '.admin',
        ]);
    }
} 