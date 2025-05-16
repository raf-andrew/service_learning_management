<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthAlert extends Model
{
    protected $fillable = [
        'service_name',
        'type',
        'level',
        'message',
        'triggered_at',
        'resolved_at'
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function isActive(): bool
    {
        return $this->resolved_at === null;
    }

    public function isCritical(): bool
    {
        return $this->level === 'critical';
    }

    public function isWarning(): bool
    {
        return $this->level === 'warning';
    }

    public function resolve(): bool
    {
        if ($this->isResolved()) {
            return false;
        }

        $this->resolved_at = now();
        return $this->save();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('level', 'warning');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForService($query, string $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }
} 