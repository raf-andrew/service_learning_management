<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from authorization.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $role
     * @param  string|null  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $role = null, ?string $permission = null)
    {
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        if (!$this->authorize($request, $role, $permission)) {
            return $this->handleUnauthorized($request);
        }

        return $next($request);
    }

    /**
     * Determine if the request is authorized.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $role
     * @param  string|null  $permission
     * @return bool
     */
    protected function authorize(Request $request, ?string $role = null, ?string $permission = null): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($role && !$this->hasRole($user, $role)) {
            return false;
        }

        if ($permission && !$this->hasPermission($user, $permission)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the user has the specified role.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $role
     * @return bool
     */
    protected function hasRole($user, string $role): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        return $this->checkUserRoles($user, $role);
    }

    /**
     * Check if the user has the specified permission.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $permission
     * @return bool
     */
    protected function hasPermission($user, string $permission): bool
    {
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        return $this->checkUserPermissions($user, $permission);
    }

    /**
     * Check user roles using the configured role provider.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $role
     * @return bool
     */
    protected function checkUserRoles($user, string $role): bool
    {
        $provider = $this->config('auth.role_provider', 'database');
        
        switch ($provider) {
            case 'database':
                return $this->checkDatabaseRoles($user, $role);
            case 'cache':
                return $this->checkCacheRoles($user, $role);
            default:
                return false;
        }
    }

    /**
     * Check user permissions using the configured permission provider.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $permission
     * @return bool
     */
    protected function checkUserPermissions($user, string $permission): bool
    {
        $provider = $this->config('auth.permission_provider', 'database');
        
        switch ($provider) {
            case 'database':
                return $this->checkDatabasePermissions($user, $permission);
            case 'cache':
                return $this->checkCachePermissions($user, $permission);
            default:
                return false;
        }
    }

    /**
     * Check roles in the database.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $role
     * @return bool
     */
    protected function checkDatabaseRoles($user, string $role): bool
    {
        return $user->roles()->where('name', $role)->exists();
    }

    /**
     * Check permissions in the database.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $permission
     * @return bool
     */
    protected function checkDatabasePermissions($user, string $permission): bool
    {
        return $user->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check roles in the cache.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $role
     * @return bool
     */
    protected function checkCacheRoles($user, string $role): bool
    {
        $cacheKey = "user.{$user->id}.roles";
        $roles = cache()->get($cacheKey, function () use ($user) {
            return $user->roles()->pluck('name')->toArray();
        });

        return in_array($role, $roles);
    }

    /**
     * Check permissions in the cache.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $permission
     * @return bool
     */
    protected function checkCachePermissions($user, string $permission): bool
    {
        $cacheKey = "user.{$user->id}.permissions";
        $permissions = cache()->get($cacheKey, function () use ($user) {
            return $user->permissions()->pluck('name')->toArray();
        });

        return in_array($permission, $permissions);
    }

    /**
     * Handle an unauthorized request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleUnauthorized(Request $request): Response
    {
        $this->logAuthorizationFailure($request);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        }

        return redirect()->to($this->config('auth.unauthorized_redirect', '/unauthorized'));
    }

    /**
     * Log authorization failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function logAuthorizationFailure(Request $request): void
    {
        Log::warning('Authorization Failed', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Determine if the request should pass through the middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        $except = array_merge($this->except, $this->config('auth.authorization_except', []));

        foreach ($except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
} 