<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RolePermission extends Pivot
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'auth_role_permissions';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'role_id',
        'permission_id',
        'granted_by', // User who granted this permission
        'granted_at',
        'expires_at', // Optional expiration date
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
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
     * Get the role that has this permission.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the permission that is assigned to the role.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the user who granted this permission.
     */
    public function grantedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'granted_by');
    }

    /**
     * Scope a query to only include active role permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include expired role permissions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include non-expired role permissions.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include role permissions by role.
     */
    public function scopeByRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope a query to only include role permissions by permission.
     */
    public function scopeByPermission($query, int $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }

    /**
     * Scope a query to only include role permissions by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->whereHas('permission', function ($q) use ($module) {
            $q->where('module', $module);
        });
    }

    /**
     * Check if the role permission is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the role permission is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the role permission is valid (active and not expired).
     */
    public function isValid(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    /**
     * Get the days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffInDays(now());
    }

    /**
     * Get the role permission's age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->granted_at->diffInDays(now());
    }

    /**
     * Get the role permission's metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set the role permission's metadata value.
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Check if the role permission requires renewal.
     */
    public function requiresRenewal(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        $renewalThreshold = config('modules.modules.auth.renewal_threshold_days', 30);
        return $this->getDaysUntilExpiration() <= $renewalThreshold;
    }

    /**
     * Get the renewal status.
     */
    public function getRenewalStatus(): string
    {
        if (!$this->expires_at) {
            return 'no_expiration';
        }

        $daysUntilExpiration = $this->getDaysUntilExpiration();

        if ($daysUntilExpiration < 0) {
            return 'expired';
        } elseif ($daysUntilExpiration <= 7) {
            return 'critical';
        } elseif ($daysUntilExpiration <= 30) {
            return 'warning';
        } else {
            return 'ok';
        }
    }

    /**
     * Check if the role permission is critical (admin-level permission).
     */
    public function isCritical(): bool
    {
        return $this->permission && $this->permission->isCritical();
    }

    /**
     * Get the role permission's display information.
     */
    public function getDisplayInfo(): array
    {
        return [
            'role_name' => $this->role->getDisplayName() ?? 'Unknown Role',
            'permission_name' => $this->permission->getDisplayName() ?? 'Unknown Permission',
            'granted_by' => $this->grantedBy->name ?? 'System',
            'granted_at' => $this->granted_at->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'status' => $this->isValid() ? 'Active' : 'Inactive',
            'renewal_status' => $this->getRenewalStatus(),
            'risk_level' => $this->permission->getRiskLevel() ?? 'unknown',
        ];
    }

    /**
     * Check if the role permission can be revoked.
     */
    public function canBeRevoked(): bool
    {
        // Critical permissions require special handling
        if ($this->isCritical()) {
            return false;
        }

        // Expired permissions can be revoked
        if ($this->isExpired()) {
            return true;
        }

        return true;
    }

    /**
     * Get the role permission's audit information.
     */
    public function getAuditInfo(): array
    {
        return [
            'role_id' => $this->role_id,
            'permission_id' => $this->permission_id,
            'granted_by' => $this->granted_by,
            'granted_at' => $this->granted_at->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'is_critical' => $this->isCritical(),
            'permission_name' => $this->permission->name ?? 'unknown',
            'permission_risk_level' => $this->permission->getRiskLevel() ?? 'unknown',
        ];
    }

    /**
     * Check if the role permission affects multiple users.
     */
    public function affectsMultipleUsers(): bool
    {
        return $this->role && $this->role->getUserCount() > 1;
    }

    /**
     * Get the number of users affected by this role permission.
     */
    public function getAffectedUserCount(): int
    {
        return $this->role ? $this->role->getUserCount() : 0;
    }

    /**
     * Check if the role permission requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->permission && $this->permission->requiresApproval();
    }

    /**
     * Get the role permission's impact level.
     */
    public function getImpactLevel(): string
    {
        if ($this->isCritical()) {
            return 'critical';
        } elseif ($this->affectsMultipleUsers()) {
            return 'high';
        } elseif ($this->permission && $this->permission->getRiskLevel() === 'high') {
            return 'medium';
        } else {
            return 'low';
        }
    }
} 