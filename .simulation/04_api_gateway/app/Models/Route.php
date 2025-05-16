<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    protected $fillable = [
        'name',
        'path',
        'method',
        'target_url',
        'service_name',
        'is_active',
        'rate_limit',
        'timeout',
        'retry_count',
        'circuit_breaker_threshold',
        'circuit_breaker_timeout',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
        'timeout' => 'integer',
        'retry_count' => 'integer',
        'circuit_breaker_threshold' => 'integer',
        'circuit_breaker_timeout' => 'integer',
    ];

    /**
     * Get the access logs for this route.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Get the rate limits for this route.
     */
    public function rateLimits(): HasMany
    {
        return $this->hasMany(RateLimit::class);
    }

    /**
     * Check if the route is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the full target URL for this route.
     */
    public function getFullTargetUrl(): string
    {
        return rtrim($this->target_url, '/') . '/' . ltrim($this->path, '/');
    }
} 