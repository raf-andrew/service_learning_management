<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'level',
        'status',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'level' => 'string',
        'status' => 'string',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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