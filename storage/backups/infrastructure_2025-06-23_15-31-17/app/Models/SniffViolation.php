<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SniffViolation extends Model
{
    protected $fillable = [
        'sniff_result_id',
        'file_path',
        'line',
        'column',
        'type',
        'message',
        'source',
        'severity',
        'fixable',
        'context',
    ];

    protected $casts = [
        'line' => 'integer',
        'column' => 'integer',
        'fixable' => 'boolean',
        'context' => 'array',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(SniffResult::class, 'sniff_result_id');
    }

    public function getSeverityLevelAttribute(): string
    {
        return match (strtolower($this->severity)) {
            'error' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    public function getFormattedMessageAttribute(): string
    {
        return sprintf(
            '[%s] %s (Line %d, Column %d)',
            strtoupper($this->severity),
            $this->message,
            $this->line,
            $this->column
        );
    }

    public function getSourceCategoryAttribute(): string
    {
        $parts = explode('.', $this->source);
        return $parts[1] ?? 'Other';
    }

    public function getIsFixableAttribute(): bool
    {
        return $this->fixable;
    }
} 