<?php

namespace App\Modules\E2ee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EncryptionKey extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'e2ee_user_keys';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'key',
        'algorithm',
        'key_length',
        'status',
        'created_at',
        'expires_at',
        'rotated_at',
        'revoked_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'key' => 'encrypted',
        'expires_at' => 'datetime',
        'rotated_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'key',
    ];

    /**
     * Get the user that owns the encryption key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'user_id');
    }

    /**
     * Get the transactions for this key.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EncryptionTransaction::class, 'key_id');
    }

    /**
     * Scope a query to only include active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include expired keys.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include keys that need rotation.
     */
    public function scopeNeedsRotation($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '<=', now()->addDays(7));
    }

    /**
     * Check if the key is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the key needs rotation.
     */
    public function needsRotation(): bool
    {
        return $this->isActive() && $this->expires_at && $this->expires_at->diffInDays(now()) <= 7;
    }

    /**
     * Get the key in a safe format for display.
     */
    public function getDisplayKey(): string
    {
        return substr(hash('sha256', $this->key), 0, 16) . '...';
    }

    /**
     * Get the key age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get the days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, $this->expires_at->diffInDays(now()));
    }
} 