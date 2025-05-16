<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        $roles = $this->roleService->getAllRoles();
        return response()->json(['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'description' => ['required', 'string'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = $this->roleService->createRole($request->all());

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ], 201);
    }

    public function show(Role $role)
    {
        return response()->json(['role' => $role->load('permissions')]);
    }

    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'description' => ['sometimes', 'string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = $this->roleService->updateRole($role, $request->all());

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role
        ]);
    }

    public function destroy(Role $role)
    {
        $this->roleService->deleteRole($role);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->roleService->assignRole($request->user_id, $request->role_id);

        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function removeRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->roleService->removeRole($request->user_id, $request->role_id);

        return response()->json(['message' => 'Role removed successfully']);
    }

    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->json(['permissions' => $permissions]);
    }
} 