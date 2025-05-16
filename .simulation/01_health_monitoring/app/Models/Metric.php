<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'value',
        'unit',
        'timestamp',
        'labels'
    ];

    protected $casts = [
        'value' => 'float',
        'timestamp' => 'datetime',
        'labels' => 'array'
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('timestamp', 'desc');
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function isAboveThreshold(): bool
    {
        return $this->value > $this->threshold;
    }

    public function isBelowThreshold(): bool
    {
        return $this->value < $this->threshold;
    }

    public function getFormattedValue(): string
    {
        return sprintf('%.2f %s', $this->value, $this->unit);
    }

    public function getThresholdPercentage(): float
    {
        if ($this->threshold === 0) {
            return 0;
        }
        return ($this->value / $this->threshold) * 100;
    }

    public function getStatus(): string
    {
        if ($this->isAboveThreshold()) {
            return 'critical';
        }
        if ($this->getThresholdPercentage() > 80) {
            return 'warning';
        }
        return 'healthy';
    }
} 