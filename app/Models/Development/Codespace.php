<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Codespace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'github_id',
        'user_id',
        'environment',
        'size',
        'status',
        'url',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the codespace.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 