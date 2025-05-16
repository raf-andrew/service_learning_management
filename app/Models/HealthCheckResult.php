<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheckResult extends Model
{
    protected $fillable = [
        'health_check_id',
        'status',
        'details',
        'checked_at'
    ];

    protected $casts = [
        'details' => 'array',
        'checked_at' => 'datetime'
    ];

    public function healthCheck(): BelongsTo
    {
        return $this->belongsTo(HealthCheck::class);
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function isUnhealthy(): bool
    {
        return $this->status === 'unhealthy';
    }

    public function getDetailsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDetailsAttribute($value)
    {
        $this->attributes['details'] = json_encode($value);
    }
} 