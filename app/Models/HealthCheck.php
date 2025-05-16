<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function results(): HasMany
    {
        return $this->hasMany(HealthCheckResult::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(HealthMetric::class, 'service_name', 'name');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(HealthAlert::class, 'service_name', 'name');
    }

    public function getLatestResult()
    {
        return $this->results()->latest()->first();
    }

    public function getLatestMetrics()
    {
        return $this->metrics()->latest()->first();
    }

    public function getActiveAlerts()
    {
        return $this->alerts()->whereNull('resolved_at')->get();
    }
} 