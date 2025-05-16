<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheckResult extends Model
{
    protected $fillable = [
        'health_check_id',
        'status',
        'response_time',
        'error_message',
        'metadata',
        'checked_at'
    ];

    protected $casts = [
        'response_time' => 'float',
        'metadata' => 'array',
        'checked_at' => 'datetime'
    ];

    public function healthCheck(): BelongsTo
    {
        return $this->belongsTo(HealthCheck::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    public function isFailure(): bool
    {
        return $this->status === 'failure';
    }

    public function getFormattedResponseTime(): string
    {
        if ($this->response_time === null) {
            return 'N/A';
        }

        if ($this->response_time < 1000) {
            return number_format($this->response_time, 2) . 'ms';
        }

        return number_format($this->response_time / 1000, 2) . 's';
    }
} 