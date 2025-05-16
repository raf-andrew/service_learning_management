<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'status',
        'metadata',
        'tags',
        'is_active',
        'last_health_check',
        'health_check_interval',
        'health_check_timeout',
        'health_check_retries',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'last_health_check' => 'datetime',
        'health_check_interval' => 'integer',
        'health_check_timeout' => 'integer',
        'health_check_retries' => 'integer',
    ];

    /**
     * Get the service instances.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(ServiceInstance::class);
    }

    /**
     * Get the health checks for the service.
     */
    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    /**
     * Get the service dependencies.
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(ServiceDependency::class, 'dependent_service_id');
    }

    /**
     * Get the services that depend on this service.
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(ServiceDependency::class, 'service_id');
    }

    /**
     * Check if the service is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the latest health check result.
     */
    public function getLatestHealthCheck(): ?HealthCheck
    {
        return $this->healthChecks()->latest()->first();
    }

    /**
     * Check if the service is healthy.
     */
    public function isHealthy(): bool
    {
        $latestCheck = $this->getLatestHealthCheck();
        return $latestCheck && $latestCheck->status === 'healthy';
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if service has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }
} 