<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInstance extends Model
{
    protected $fillable = [
        'service_id',
        'host',
        'port',
        'status',
        'metadata',
        'last_heartbeat',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_heartbeat' => 'datetime',
        'is_active' => 'boolean',
        'port' => 'integer',
    ];

    /**
     * Get the service that owns the instance.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if the instance is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Update the instance heartbeat.
     */
    public function updateHeartbeat(): void
    {
        $this->update(['last_heartbeat' => now()]);
    }
} 