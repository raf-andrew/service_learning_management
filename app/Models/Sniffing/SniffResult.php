<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SniffResult extends Model
{
    protected $fillable = [
        'total_violations',
        'error_count',
        'warning_count',
        'info_count',
        'summary',
        'report_path',
    ];

    protected $casts = [
        'summary' => 'array',
        'total_violations' => 'integer',
        'error_count' => 'integer',
        'warning_count' => 'integer',
        'info_count' => 'integer',
    ];

    public function violations(): HasMany
    {
        return $this->hasMany(SniffViolation::class);
    }

    public function getCoveragePercentageAttribute(): float
    {
        return $this->analyzed_files > 0
            ? ($this->analyzed_files / $this->total_files) * 100
            : 0;
    }

    public function getStandardsCoverageAttribute(): array
    {
        return $this->summary['standards_coverage'] ?? [];
    }

    public function getViolationsByFileAttribute(): array
    {
        return $this->summary['violations_by_file'] ?? [];
    }
} 