<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoryEntry extends Model
{
    protected $fillable = [
        'category',
        'data',
        'tokens'
    ];

    protected $casts = [
        'data' => 'array',
        'tokens' => 'array'
    ];

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
} 