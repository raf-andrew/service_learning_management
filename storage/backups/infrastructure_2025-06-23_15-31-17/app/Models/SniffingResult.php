<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SniffingResult extends Model
{
    use HasFactory;

    protected $table = 'sniffing_results';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'result_data',
        'report_format',
        'file_path',
        'fix_applied',
        'error_count',
        'warning_count',
    ];

    protected $casts = [
        'result_data' => 'json',
        'fix_applied' => 'boolean',
        'error_count' => 'integer',
        'warning_count' => 'integer',
    ];
}
