<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRole extends Pivot
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'auth_user_roles';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'granted_by', // User who granted this role
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
     * Get the user that has this role.
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the role that is assigned to the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user who granted this role.
     */
    public function grantedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'granted_by');
    }

    /**
     * Scope a query to only include active user roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include expired user roles.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include non-expired user roles.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include user roles by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include user roles by role.
     */
    public function scopeByRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Check if the user role is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the user role is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the user role is valid (active and not expired).
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
     * Get the user role's age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->granted_at->diffInDays(now());
    }

    /**
     * Get the user role's metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set the user role's metadata value.
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Check if the user role requires renewal.
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
     * Check if the user role is critical (admin role).
     */
    public function isCritical(): bool
    {
        return $this->role && $this->role->isAdmin();
    }

    /**
     * Get the user role's display information.
     */
    public function getDisplayInfo(): array
    {
        return [
            'user_name' => $this->user->name ?? 'Unknown User',
            'role_name' => $this->role->getDisplayName() ?? 'Unknown Role',
            'granted_by' => $this->grantedBy->name ?? 'System',
            'granted_at' => $this->granted_at->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'status' => $this->isValid() ? 'Active' : 'Inactive',
            'renewal_status' => $this->getRenewalStatus(),
        ];
    }

    /**
     * Check if the user role can be revoked.
     */
    public function canBeRevoked(): bool
    {
        // Critical roles require special handling
        if ($this->isCritical()) {
            return false;
        }

        // Expired roles can be revoked
        if ($this->isExpired()) {
            return true;
        }

        return true;
    }

    /**
     * Get the user role's audit information.
     */
    public function getAuditInfo(): array
    {
        return [
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'granted_by' => $this->granted_by,
            'granted_at' => $this->granted_at->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'is_critical' => $this->isCritical(),
        ];
    }
} 