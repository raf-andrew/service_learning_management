<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeveloperCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'github_token',
        'github_username',
        'is_active',
        'last_used_at',
        'permissions'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'permissions' => 'array'
    ];

    protected $hidden = [
        'github_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
} 