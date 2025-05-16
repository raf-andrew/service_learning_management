<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccessControlMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Get the authenticated user
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if ($this->userHasRole($user, $role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Add role information to the request for downstream middleware
        $request->attributes->set('user_roles', $user->roles);
        
        return $next($request);
    }

    /**
     * Check if a user has a specific role.
     *
     * @param  \App\Models\User  $user
     * @param  string  $role
     * @return bool
     */
    private function userHasRole($user, $role)
    {
        // Check if user has the role directly
        if (in_array($role, $user->roles)) {
            return true;
        }

        // Check if user has the role through role inheritance
        foreach ($user->roles as $userRole) {
            if ($this->roleInheritsFrom($userRole, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a role inherits from another role.
     *
     * @param  string  $role
     * @param  string  $inheritedRole
     * @return bool
     */
    private function roleInheritsFrom($role, $inheritedRole)
    {
        // Define role hierarchy
        $roleHierarchy = [
            'super_admin' => ['admin', 'manager', 'user'],
            'admin' => ['manager', 'user'],
            'manager' => ['user'],
            'user' => [],
        ];

        // Check if role exists in hierarchy
        if (!isset($roleHierarchy[$role])) {
            return false;
        }

        // Check if role inherits from the specified role
        return in_array($inheritedRole, $roleHierarchy[$role]);
    }
} 