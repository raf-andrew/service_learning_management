<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateLimit extends Model
{
    protected $fillable = [
        'route_id',
        'api_key_id',
        'requests_count',
        'window_start',
        'window_end',
        'is_blocked',
        'blocked_until',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'is_blocked' => 'boolean',
        'blocked_until' => 'datetime',
    ];

    /**
     * Get the route that owns this rate limit.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Get the API key that owns this rate limit.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Check if the rate limit is currently blocked.
     */
    public function isBlocked(): bool
    {
        if (!$this->is_blocked) {
            return false;
        }

        if ($this->blocked_until && $this->blocked_until->isPast()) {
            $this->update([
                'is_blocked' => false,
                'blocked_until' => null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Increment the request count for this rate limit.
     */
    public function incrementRequestCount(): void
    {
        $this->increment('requests_count');
    }

    /**
     * Reset the request count for this rate limit.
     */
    public function resetRequestCount(): void
    {
        $this->update([
            'requests_count' => 0,
            'window_start' => now(),
            'window_end' => now()->addMinutes(1),
        ]);
    }
} 