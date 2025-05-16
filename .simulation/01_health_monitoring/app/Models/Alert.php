<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'service_id',
        'type',
        'severity',
        'message',
        'status',
        'resolved_at',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime'
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
} 