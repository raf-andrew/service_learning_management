<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'auth_permissions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module', // Which module this permission belongs to
        'category', // Category within the module (e.g., 'user_management', 'data_access')
        'is_system_permission', // Whether this is a system-defined permission
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_system_permission' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'auth_role_permissions',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Get the role permissions for this permission.
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Get the users that have this permission through their roles.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', 'App\Models\User'),
            'auth_user_roles',
            'permission_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Scope a query to only include active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include system permissions.
     */
    public function scopeSystemPermissions($query)
    {
        return $query->where('is_system_permission', true);
    }

    /**
     * Scope a query to only include custom permissions.
     */
    public function scopeCustomPermissions($query)
    {
        return $query->where('is_system_permission', false);
    }

    /**
     * Scope a query to only include permissions by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope a query to only include permissions by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include permissions by name.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Check if the permission is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the permission is a system permission.
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system_permission;
    }

    /**
     * Get the permission's display name.
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?: ucfirst(str_replace('_', ' ', $this->name));
    }

    /**
     * Get the number of roles that have this permission.
     */
    public function getRoleCount(): int
    {
        return $this->roles()->count();
    }

    /**
     * Check if the permission can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // System permissions cannot be deleted
        if ($this->is_system_permission) {
            return false;
        }

        // Permissions assigned to roles cannot be deleted
        if ($this->getRoleCount() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the permission's metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set the permission's metadata value.
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Get the permission's creation age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if the permission is a view permission.
     */
    public function isViewPermission(): bool
    {
        return str_ends_with($this->name, '.view');
    }

    /**
     * Check if the permission is a create permission.
     */
    public function isCreatePermission(): bool
    {
        return str_ends_with($this->name, '.create');
    }

    /**
     * Check if the permission is an update permission.
     */
    public function isUpdatePermission(): bool
    {
        return str_ends_with($this->name, '.update');
    }

    /**
     * Check if the permission is a delete permission.
     */
    public function isDeletePermission(): bool
    {
        return str_ends_with($this->name, '.delete');
    }

    /**
     * Check if the permission is a manage permission.
     */
    public function isManagePermission(): bool
    {
        return str_ends_with($this->name, '.manage');
    }

    /**
     * Check if the permission is an admin permission.
     */
    public function isAdminPermission(): bool
    {
        return str_ends_with($this->name, '.admin');
    }

    /**
     * Get the action part of the permission name.
     */
    public function getAction(): string
    {
        $parts = explode('.', $this->name);
        return end($parts);
    }

    /**
     * Get the resource part of the permission name.
     */
    public function getResource(): string
    {
        $parts = explode('.', $this->name);
        array_pop($parts);
        return implode('.', $parts);
    }

    /**
     * Check if the permission is for a specific module.
     */
    public function isForModule(string $module): bool
    {
        return $this->module === $module || str_starts_with($this->name, $module . '.');
    }

    /**
     * Get the permission's category display name.
     */
    public function getCategoryDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Check if the permission is critical (admin-level).
     */
    public function isCritical(): bool
    {
        return $this->isAdminPermission() || 
               $this->isManagePermission() || 
               in_array($this->name, [
                   'auth.admin',
                   'system.admin',
                   'user.admin',
                   'data.admin',
               ]);
    }

    /**
     * Get the permission's risk level.
     */
    public function getRiskLevel(): string
    {
        if ($this->isCritical()) {
            return 'critical';
        } elseif ($this->isDeletePermission() || $this->isManagePermission()) {
            return 'high';
        } elseif ($this->isUpdatePermission() || $this->isCreatePermission()) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Check if the permission requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->isCritical() || $this->getRiskLevel() === 'high';
    }

    /**
     * Get the permission's description or generate one.
     */
    public function getDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $action = $this->getAction();
        $resource = $this->getResource();

        $descriptions = [
            'view' => "View {$resource}",
            'create' => "Create {$resource}",
            'update' => "Update {$resource}",
            'delete' => "Delete {$resource}",
            'manage' => "Manage {$resource}",
            'admin' => "Administer {$resource}",
        ];

        return $descriptions[$action] ?? "Permission to {$action} {$resource}";
    }
} 