<?php

namespace Modules\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Api\Exceptions\ApiAuthenticationException;

class ApiAuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = null)
    {
        try {
            // Check if authentication is enabled
            if (!config('modules.api.authentication.enabled', true)) {
                return $next($request);
            }

            // Check if route is public
            if ($this->isPublicRoute($request)) {
                return $next($request);
            }

            // Try different authentication methods
            $user = $this->authenticate($request);

            if (!$user) {
                // Check if route is optional
                if ($this->isOptionalRoute($request)) {
                    return $next($request);
                }

                throw new ApiAuthenticationException('Authentication required', 401);
            }

            // Set the authenticated user
            Auth::setUser($user);

            // Log successful authentication
            Log::info('API Authentication successful', [
                'user_id' => $user->id,
                'method' => $this->getAuthMethod($request),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->fullUrl(),
            ]);

            return $next($request);

        } catch (ApiAuthenticationException $e) {
            Log::warning('API Authentication failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->fullUrl(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'timestamp' => now()->toISOString(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('API Authentication error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal authentication error',
                'code' => 500,
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Authenticate the request using available methods
     */
    protected function authenticate(Request $request): ?\App\Models\User
    {
        // Try Bearer token authentication
        if (config('modules.api.authentication.methods.token.enabled', true)) {
            $user = $this->authenticateWithToken($request);
            if ($user) {
                return $user;
            }
        }

        // Try API key authentication
        if (config('modules.api.authentication.methods.api_key.enabled', true)) {
            $user = $this->authenticateWithApiKey($request);
            if ($user) {
                return $user;
            }
        }

        // Try Basic authentication
        if (config('modules.api.authentication.methods.basic.enabled', false)) {
            $user = $this->authenticateWithBasic($request);
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Authenticate using Bearer token
     */
    protected function authenticateWithToken(Request $request): ?\App\Models\User
    {
        $header = config('modules.api.authentication.methods.token.header', 'Authorization');
        $prefix = config('modules.api.authentication.methods.token.prefix', 'Bearer');

        $authHeader = $request->header($header);
        if (!$authHeader || !str_starts_with($authHeader, $prefix . ' ')) {
            return null;
        }

        $token = substr($authHeader, strlen($prefix . ' '));
        if (empty($token)) {
            return null;
        }

        // Validate token (implement your token validation logic here)
        $user = $this->validateToken($token);
        
        return $user;
    }

    /**
     * Authenticate using API key
     */
    protected function authenticateWithApiKey(Request $request): ?\App\Models\User
    {
        $header = config('modules.api.authentication.methods.api_key.header', 'X-API-Key');
        $apiKey = $request->header($header);

        if (empty($apiKey)) {
            return null;
        }

        // Validate API key (implement your API key validation logic here)
        $user = $this->validateApiKey($apiKey);
        
        return $user;
    }

    /**
     * Authenticate using Basic authentication
     */
    protected function authenticateWithBasic(Request $request): ?\App\Models\User
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return null;
        }

        $credentials = base64_decode(substr($authHeader, 6));
        if (!$credentials) {
            return null;
        }

        [$username, $password] = explode(':', $credentials, 2);
        
        // Validate basic credentials (implement your basic auth logic here)
        $user = $this->validateBasicCredentials($username, $password);
        
        return $user;
    }

    /**
     * Validate Bearer token
     */
    protected function validateToken(string $token): ?\App\Models\User
    {
        // Implement your token validation logic here
        // This could involve JWT validation, database lookup, etc.
        
        // Example implementation:
        $cacheKey = 'api_token_' . hash('sha256', $token);
        $userId = Cache::get($cacheKey);
        
        if ($userId) {
            return \App\Models\User::find($userId);
        }
        
        return null;
    }

    /**
     * Validate API key
     */
    protected function validateApiKey(string $apiKey): ?\App\Models\User
    {
        // Implement your API key validation logic here
        // This could involve database lookup, key validation, etc.
        
        // Example implementation:
        $cacheKey = 'api_key_' . hash('sha256', $apiKey);
        $userId = Cache::get($cacheKey);
        
        if ($userId) {
            return \App\Models\User::find($userId);
        }
        
        return null;
    }

    /**
     * Validate Basic credentials
     */
    protected function validateBasicCredentials(string $username, string $password): ?\App\Models\User
    {
        // Implement your basic authentication logic here
        // This could involve database lookup, password verification, etc.
        
        $user = \App\Models\User::where('email', $username)->first();
        
        if ($user && \Hash::check($password, $user->password)) {
            return $user;
        }
        
        return null;
    }

    /**
     * Check if the route is public
     */
    protected function isPublicRoute(Request $request): bool
    {
        $publicRoutes = config('modules.api.authentication.public_routes', []);
        $path = $request->path();
        
        foreach ($publicRoutes as $route) {
            if (str_starts_with($path, trim($route, '/'))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the route is optional (authentication not required)
     */
    protected function isOptionalRoute(Request $request): bool
    {
        $optionalRoutes = config('modules.api.authentication.optional_routes', []);
        $path = $request->path();
        
        foreach ($optionalRoutes as $route) {
            if (str_starts_with($path, trim($route, '/'))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the authentication method used
     */
    protected function getAuthMethod(Request $request): string
    {
        if ($request->header('Authorization') && str_starts_with($request->header('Authorization'), 'Bearer ')) {
            return 'bearer_token';
        }
        
        if ($request->header('X-API-Key')) {
            return 'api_key';
        }
        
        if ($request->header('Authorization') && str_starts_with($request->header('Authorization'), 'Basic ')) {
            return 'basic';
        }
        
        return 'unknown';
    }
} 