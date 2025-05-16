<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCheck extends Model
{
    protected $fillable = [
        'name',
        'type',
        'target',
        'config',
        'timeout',
        'retry_attempts',
        'retry_delay',
        'is_active'
    ];

    protected $casts = [
        'config' => 'array',
        'timeout' => 'integer',
        'retry_attempts' => 'integer',
        'retry_delay' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Get the service that owns the health check.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if the health check was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get the formatted response time.
     */
    public function getFormattedResponseTime(): string
    {
        return number_format($this->response_time, 2) . 'ms';
    }

    /**
     * Get the health check status with details.
     */
    public function getStatusWithDetails(): array
    {
        return [
            'status' => $this->status,
            'response_time' => $this->getFormattedResponseTime(),
            'error_message' => $this->error_message,
            'check_time' => $this->check_time->toIso8601String(),
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(HealthCheckResult::class);
    }

    public function getLatestResult(): ?HealthCheckResult
    {
        return $this->results()
            ->latest('checked_at')
            ->first();
    }

    public function getStatus(): string
    {
        $latestResult = $this->getLatestResult();
        return $latestResult ? $latestResult->status : 'unknown';
    }

    public function isHealthy(): bool
    {
        return $this->getStatus() === 'success';
    }

    public function getAverageResponseTime(): float
    {
        return $this->results()
            ->whereNotNull('response_time')
            ->avg('response_time') ?? 0.0;
    }

    public function getSuccessRate(): float
    {
        $total = $this->results()->count();
        if ($total === 0) {
            return 0.0;
        }

        $successful = $this->results()
            ->where('status', 'success')
            ->count();

        return ($successful / $total) * 100;
    }
} 