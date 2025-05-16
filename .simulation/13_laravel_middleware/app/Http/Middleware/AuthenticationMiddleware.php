<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from authentication.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        if (!$this->authenticate($request)) {
            return $this->handleUnauthenticated($request);
        }

        return $next($request);
    }

    /**
     * Attempt to authenticate the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function authenticate(Request $request): bool
    {
        $guards = $this->config('auth.guards', ['web']);

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                return true;
            }
        }

        return false;
    }

    /**
     * Handle an unauthenticated request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleUnauthenticated(Request $request): Response
    {
        $this->logAuthenticationFailure($request);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please log in to access this resource.'
            ], 401);
        }

        return redirect()->guest($this->config('auth.login_route', 'login'));
    }

    /**
     * Log authentication failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function logAuthenticationFailure(Request $request): void
    {
        Log::warning('Authentication Failed', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'attempted_guard' => Auth::getDefaultDriver()
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
        $except = array_merge($this->except, $this->config('auth.except', []));

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

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function user()
    {
        return Auth::user();
    }

    /**
     * Get the guard to be used for authentication.
     *
     * @return string
     */
    protected function guard()
    {
        return Auth::getDefaultDriver();
    }
} 