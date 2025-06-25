<?php

namespace App\Models\Sniffing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SniffResult extends Model
{
    use HasFactory;

    protected $table = 'sniff_results';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'file_path',
        'report_format',
        'fix_applied',
        'error_count',
        'warning_count',
        'result_data',
        'sniff_date',
        'execution_time',
        'phpcs_version',
        'standards_used',
        'status',
    ];

    protected $casts = [
        'result_data' => 'array',
        'fix_applied' => 'boolean',
        'sniff_date' => 'datetime',
        'execution_time' => 'float',
        'standards_used' => 'array',
    ];

    public function violations(): HasMany
    {
        return $this->hasMany(SniffViolation::class);
    }

    public function getSeverityLevelAttribute(): string
    {
        if ($this->error_count > 0) {
            return 'error';
        }
        if ($this->warning_count > 0) {
            return 'warning';
        }
        return 'success';
    }

    public function getFormattedExecutionTimeAttribute(): string
    {
        return number_format($this->execution_time, 2) . ' seconds';
    }

    public function getStandardsListAttribute(): string
    {
        return implode(', ', $this->standards_used);
    }
}
