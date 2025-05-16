<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceHealth extends Model
{
    protected $fillable = [
        'service_name',
        'status',
        'last_check',
        'response_time',
        'error_count',
        'warning_count'
    ];

    protected $casts = [
        'last_check' => 'datetime',
        'response_time' => 'float',
        'error_count' => 'integer',
        'warning_count' => 'integer'
    ];

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function isDegraded(): bool
    {
        return $this->status === 'degraded';
    }

    public function isUnhealthy(): bool
    {
        return $this->status === 'unhealthy';
    }

    public function incrementErrorCount(): void
    {
        $this->increment('error_count');
    }

    public function incrementWarningCount(): void
    {
        $this->increment('warning_count');
    }

    public function resetCounts(): void
    {
        $this->update([
            'error_count' => 0,
            'warning_count' => 0
        ]);
    }

    public function hasWarnings(): bool
    {
        return $this->warning_count > 0;
    }

    public function hasErrors(): bool
    {
        return $this->error_count > 0;
    }

    public function getHealthStatus(): string
    {
        if ($this->hasErrors()) {
            return 'critical';
        }
        if ($this->hasWarnings()) {
            return 'warning';
        }
        return 'healthy';
    }
} 