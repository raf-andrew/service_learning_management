<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthMetric extends Model
{
    protected $fillable = [
        'service_name',
        'metrics',
        'collected_at'
    ];

    protected $casts = [
        'metrics' => 'array',
        'collected_at' => 'datetime'
    ];

    public function getMetricsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setMetricsAttribute($value)
    {
        $this->attributes['metrics'] = json_encode($value);
    }

    public function getCpuUsage(): ?float
    {
        return $this->metrics['cpu']['usage'] ?? null;
    }

    public function getMemoryUsage(): ?float
    {
        return $this->metrics['memory']['usage'] ?? null;
    }

    public function getDiskUsage(): ?float
    {
        return $this->metrics['disk']['usage'] ?? null;
    }

    public function getNetworkConnections(): ?int
    {
        return $this->metrics['network']['connections'] ?? null;
    }

    public function getProcessCount(): ?int
    {
        return $this->metrics['process']['count'] ?? null;
    }

    public function getProcessMemoryUsage(): ?int
    {
        return $this->metrics['process']['memory'] ?? null;
    }

    public function isCpuUsageHigh(): bool
    {
        return $this->getCpuUsage() > 70;
    }

    public function isMemoryUsageHigh(): bool
    {
        return $this->getMemoryUsage() > 75;
    }

    public function isDiskUsageHigh(): bool
    {
        return $this->getDiskUsage() > 80;
    }
} 