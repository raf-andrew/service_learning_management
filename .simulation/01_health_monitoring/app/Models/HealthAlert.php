<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthAlert extends Model
{
    protected $fillable = [
        'name',
        'type',
        'severity',
        'source_type',
        'source_id',
        'message',
        'context',
        'triggered_at',
        'resolved_at'
    ];

    protected $casts = [
        'context' => 'array',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function isActive(): bool
    {
        return !$this->isResolved();
    }

    public function getDuration(): ?int
    {
        if (!$this->isResolved()) {
            return null;
        }

        return $this->triggered_at->diffInSeconds($this->resolved_at);
    }

    public function getFormattedDuration(): string
    {
        $duration = $this->getDuration();
        if ($duration === null) {
            return 'Ongoing';
        }

        if ($duration < 60) {
            return $duration . ' seconds';
        }

        if ($duration < 3600) {
            return floor($duration / 60) . ' minutes';
        }

        if ($duration < 86400) {
            return floor($duration / 3600) . ' hours';
        }

        return floor($duration / 86400) . ' days';
    }

    public function getContextValue(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    public function hasContext(string $key): bool
    {
        return isset($this->context[$key]);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeOfSource($query, string $type, string $id)
    {
        return $query->where('source_type', $type)
            ->where('source_id', $id);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeTriggeredAfter($query, $date)
    {
        return $query->where('triggered_at', '>=', $date);
    }

    public function scopeTriggeredBefore($query, $date)
    {
        return $query->where('triggered_at', '<=', $date);
    }
} 