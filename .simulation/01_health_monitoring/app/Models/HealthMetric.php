<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthMetric extends Model
{
    protected $fillable = [
        'name',
        'type',
        'value',
        'unit',
        'labels',
        'recorded_at'
    ];

    protected $casts = [
        'value' => 'float',
        'labels' => 'array',
        'recorded_at' => 'datetime'
    ];

    public function getFormattedValue(): string
    {
        if ($this->unit === null) {
            return (string) $this->value;
        }

        return number_format($this->value, 2) . ' ' . $this->unit;
    }

    public function isAboveThreshold(float $threshold): bool
    {
        return $this->value > $threshold;
    }

    public function isBelowThreshold(float $threshold): bool
    {
        return $this->value < $threshold;
    }

    public function isWithinRange(float $min, float $max): bool
    {
        return $this->value >= $min && $this->value <= $max;
    }

    public function getLabel(string $key, $default = null)
    {
        return $this->labels[$key] ?? $default;
    }

    public function hasLabel(string $key): bool
    {
        return isset($this->labels[$key]);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function scopeWithLabel($query, string $key, $value)
    {
        return $query->where("labels->{$key}", $value);
    }

    public function scopeRecordedAfter($query, $date)
    {
        return $query->where('recorded_at', '>=', $date);
    }

    public function scopeRecordedBefore($query, $date)
    {
        return $query->where('recorded_at', '<=', $date);
    }
} 