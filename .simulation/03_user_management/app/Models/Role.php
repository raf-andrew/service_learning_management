<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission(string $permission)
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    public function hasAnyPermission(array $permissions)
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    public function hasAllPermissions(array $permissions)
    {
        return $this->permissions()->whereIn('name', $permissions)->count() === count($permissions);
    }

    public function givePermissionTo(Permission $permission)
    {
        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission);
        }
    }

    public function revokePermissionTo(Permission $permission)
    {
        $this->permissions()->detach($permission);
    }

    public function syncPermissions(array $permissions)
    {
        $this->permissions()->sync($permissions);
    }
} 