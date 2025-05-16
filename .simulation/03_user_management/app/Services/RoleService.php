<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    public function createRole(array $data)
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);

            if (isset($data['permissions'])) {
                $role->permissions()->attach($data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    public function updateRole(Role $role, array $data)
    {
        return DB::transaction(function () use ($role, $data) {
            if (isset($data['name'])) {
                $role->name = $data['name'];
            }

            if (isset($data['description'])) {
                $role->description = $data['description'];
            }

            $role->save();

            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    public function deleteRole(Role $role)
    {
        return DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        });
    }

    public function assignRole(int $userId, int $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        if (!$user->roles()->where('role_id', $roleId)->exists()) {
            $user->roles()->attach($roleId);
        }
    }

    public function removeRole(int $userId, int $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        $user->roles()->detach($roleId);
    }

    public function hasPermission(User $user, string $permission)
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    public function getRolePermissions(Role $role)
    {
        return $role->permissions;
    }

    public function getUserRoles(User $user)
    {
        return $user->roles()->with('permissions')->get();
    }

    public function syncPermissions(Role $role, array $permissionIds)
    {
        return DB::transaction(function () use ($role, $permissionIds) {
            $role->permissions()->sync($permissionIds);
            return $role->load('permissions');
        });
    }
} 