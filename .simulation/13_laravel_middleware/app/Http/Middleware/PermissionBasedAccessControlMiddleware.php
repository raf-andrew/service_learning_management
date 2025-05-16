<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionBasedAccessControlMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  $permissions
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        // Get the authenticated user
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user has all required permissions
        foreach ($permissions as $permission) {
            if (!$this->userHasPermission($user, $permission)) {
                return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
            }
        }

        // Add permission information to the request for downstream middleware
        $request->attributes->set('user_permissions', $user->permissions);
        
        return $next($request);
    }

    /**
     * Check if a user has a specific permission.
     *
     * @param  \App\Models\User  $user
     * @param  string  $permission
     * @return bool
     */
    private function userHasPermission($user, $permission)
    {
        // Check if user has the permission directly
        if (in_array($permission, $user->permissions)) {
            return true;
        }

        // Check if user has the permission through role permissions
        foreach ($user->roles as $role) {
            if ($this->roleHasPermission($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a role has a specific permission.
     *
     * @param  string  $role
     * @param  string  $permission
     * @return bool
     */
    private function roleHasPermission($role, $permission)
    {
        // Define role permissions
        $rolePermissions = [
            'super_admin' => [
                'manage_users',
                'manage_roles',
                'manage_permissions',
                'view_reports',
                'manage_settings',
                'manage_content',
                'view_analytics',
            ],
            'admin' => [
                'manage_users',
                'view_reports',
                'manage_content',
                'view_analytics',
            ],
            'manager' => [
                'view_reports',
                'manage_content',
            ],
            'user' => [
                'view_content',
            ],
        ];

        // Check if role exists in permissions
        if (!isset($rolePermissions[$role])) {
            return false;
        }

        // Check if role has the specified permission
        return in_array($permission, $rolePermissions[$role]);
    }
} 