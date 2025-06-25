<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'user_id',
        'permissions',
        'is_active',
        'expires_at',
    ];

    protected $hidden = [
        'key',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the API key.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new API key.
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Check if the API key has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if the API key is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the API key is valid.
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }

    /**
     * Scope to get only active API keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
} 