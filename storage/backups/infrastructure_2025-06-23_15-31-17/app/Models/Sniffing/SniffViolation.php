<?php

namespace App\Models\Sniffing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SniffViolation extends Model
{
    protected $fillable = [
        'sniff_result_id',
        'line_number',
        'column',
        'message',
        'source',
        'severity',
        'fixable',
        'fix_applied',
        'rule_name',
        'rule_category',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'column' => 'integer',
        'fixable' => 'boolean',
        'fix_applied' => 'boolean',
    ];

    public function sniffResult(): BelongsTo
    {
        return $this->belongsTo(SniffResult::class);
    }

    public function getFormattedLocationAttribute(): string
    {
        return "Line {$this->line_number}, Column {$this->column}";
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'error' => 'red',
            'warning' => 'yellow',
            default => 'green',
        };
    }

    public function getFixStatusAttribute(): string
    {
        if (!$this->fixable) {
            return 'Not Fixable';
        }
        return $this->fix_applied ? 'Fixed' : 'Not Fixed';
    }
} 