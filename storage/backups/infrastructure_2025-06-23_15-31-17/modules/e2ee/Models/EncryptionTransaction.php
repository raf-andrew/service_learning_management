<?php

namespace App\Modules\E2ee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncryptionTransaction extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'e2ee_transactions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'transaction_id',
        'user_id',
        'key_id',
        'operation',
        'status',
        'algorithm',
        'metadata',
        'timestamp',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'user_id');
    }

    /**
     * Get the encryption key used in this transaction.
     */
    public function encryptionKey(): BelongsTo
    {
        return $this->belongsTo(EncryptionKey::class, 'key_id');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include encrypt operations.
     */
    public function scopeEncrypt($query)
    {
        return $query->where('operation', 'encrypt');
    }

    /**
     * Scope a query to only include decrypt operations.
     */
    public function scopeDecrypt($query)
    {
        return $query->where('operation', 'decrypt');
    }

    /**
     * Scope a query to only include recent transactions.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    /**
     * Check if the transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the transaction failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the transaction is an encrypt operation.
     */
    public function isEncrypt(): bool
    {
        return $this->operation === 'encrypt';
    }

    /**
     * Check if the transaction is a decrypt operation.
     */
    public function isDecrypt(): bool
    {
        return $this->operation === 'decrypt';
    }

    /**
     * Get the transaction age in minutes.
     */
    public function getAgeInMinutes(): int
    {
        return $this->timestamp->diffInMinutes(now());
    }

    /**
     * Get the transaction age in hours.
     */
    public function getAgeInHours(): int
    {
        return $this->timestamp->diffInHours(now());
    }

    /**
     * Get a formatted status for display.
     */
    public function getStatusDisplay(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get a formatted operation for display.
     */
    public function getOperationDisplay(): string
    {
        return ucfirst($this->operation);
    }

    /**
     * Get the transaction duration in milliseconds.
     */
    public function getDurationMs(): ?int
    {
        if (!$this->isCompleted() || !isset($this->metadata['start_time'])) {
            return null;
        }

        $startTime = $this->metadata['start_time'];
        $endTime = $this->metadata['end_time'] ?? now()->timestamp * 1000;

        return $endTime - $startTime;
    }
} 