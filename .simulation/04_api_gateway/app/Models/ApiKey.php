<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'secret',
        'is_active',
        'expires_at',
        'last_used_at',
        'created_by',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiKey) {
            $apiKey->key = Str::random(32);
            $apiKey->secret = Str::random(64);
        });
    }

    /**
     * Get the rate limits for this API key.
     */
    public function rateLimits(): HasMany
    {
        return $this->hasMany(RateLimit::class);
    }

    /**
     * Get the access logs for this API key.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Check if the API key is currently active.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->update(['is_active' => false]);
            return false;
        }

        return true;
    }

    /**
     * Update the last used timestamp.
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Generate a new API key pair.
     */
    public static function generate(): array
    {
        $key = Str::random(32);
        $secret = Str::random(64);

        return [
            'key' => $key,
            'secret' => $secret,
        ];
    }
} 