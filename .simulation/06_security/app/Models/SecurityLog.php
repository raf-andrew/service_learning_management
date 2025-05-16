<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SecurityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_type',
        'severity',
        'description',
        'ip_address',
        'user_agent',
        'user_id',
        'metadata',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The event types that can be logged.
     *
     * @var array<string>
     */
    public const EVENT_TYPES = [
        'login_attempt',
        'login_success',
        'login_failure',
        'logout',
        'password_reset',
        'permission_change',
        'role_change',
        'security_alert',
        'api_access',
        'data_access',
        'configuration_change',
    ];

    /**
     * The severity levels for security events.
     *
     * @var array<string>
     */
    public const SEVERITY_LEVELS = [
        'low',
        'medium',
        'high',
        'critical',
    ];

    /**
     * Get the user that triggered the security event.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope a query to only include events of a specific severity.
     */
    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include events for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include events from a specific IP address.
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to only include events within a specific time range.
     */
    public function scopeInTimeRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
} 