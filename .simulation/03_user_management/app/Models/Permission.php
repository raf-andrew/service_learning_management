<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'module',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('role_id');
    }

    public static function findByName(string $name)
    {
        return static::where('name', $name)->first();
    }

    public static function findByModule(string $module)
    {
        return static::where('module', $module)->get();
    }

    public static function createIfNotExists(array $attributes)
    {
        $permission = static::where('name', $attributes['name'])->first();

        if (!$permission) {
            $permission = static::create($attributes);
        }

        return $permission;
    }
} 