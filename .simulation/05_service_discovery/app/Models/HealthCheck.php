<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheck extends Model
{
    protected $fillable = [
        'service_id',
        'status',
        'response_time',
        'error_message',
        'check_time',
    ];

    protected $casts = [
        'response_time' => 'float',
        'check_time' => 'datetime',
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
        return $this->status === 'success';
    }

    /**
     * Format the response time.
     */
    public function formatResponseTime(): string
    {
        return number_format($this->response_time, 2) . 'ms';
    }

    /**
     * Get the error message.
     */
    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }
} 