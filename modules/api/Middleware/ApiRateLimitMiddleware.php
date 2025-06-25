<?php

namespace Modules\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = null, string $window = null)
    {
        // Check if rate limiting is enabled
        if (!config('modules.api.rate_limiting.enabled', true)) {
            return $next($request);
        }

        // Get rate limit configuration
        $limitConfig = $this->getRateLimitConfig($request, $limit, $window);
        
        // Get the identifier for rate limiting
        $identifier = $this->getIdentifier($request);
        
        // Check if request exceeds rate limit
        if ($this->isRateLimited($identifier, $limitConfig)) {
            $this->logRateLimitExceeded($request, $identifier, $limitConfig);
            
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 429,
                'retry_after' => $this->getRetryAfter($identifier, $limitConfig),
                'timestamp' => now()->toISOString(),
            ], 429);
        }

        // Increment the request count
        $this->incrementRequestCount($identifier, $limitConfig);
        
        // Add rate limit headers to response
        $response = $next($request);
        $this->addRateLimitHeaders($response, $identifier, $limitConfig);
        
        return $response;
    }

    /**
     * Get rate limit configuration based on user type
     */
    protected function getRateLimitConfig(Request $request, ?string $limit, ?string $window): array
    {
        $limits = config('modules.api.rate_limiting.limits', []);
        
        // Use provided limit and window if specified
        if ($limit && $window) {
            return [
                'limit' => (int) $limit,
                'window' => (int) $window,
            ];
        }

        // Determine user type and get appropriate limits
        $userType = $this->getUserType($request);
        
        return $limits[$userType] ?? [
            'limit' => config('modules.api.rate_limiting.default_limit', 60),
            'window' => config('modules.api.rate_limiting.default_window', 60),
        ];
    }

    /**
     * Get user type for rate limiting
     */
    protected function getUserType(Request $request): string
    {
        $user = Auth::user();
        
        if (!$user) {
            return 'guest';
        }

        // Check if user has API key
        if ($request->header('X-API-Key')) {
            return 'api_key';
        }

        // Check user roles
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return 'admin';
        }

        return 'user';
    }

    /**
     * Get identifier for rate limiting
     */
    protected function getIdentifier(Request $request): string
    {
        $user = Auth::user();
        
        if ($user) {
            return 'user_' . $user->id;
        }

        // Use IP address for guest users
        return 'ip_' . $request->ip();
    }

    /**
     * Check if request is rate limited
     */
    protected function isRateLimited(string $identifier, array $config): bool
    {
        $cacheKey = $this->getCacheKey($identifier, $config);
        $currentCount = Cache::get($cacheKey, 0);
        
        return $currentCount >= $config['limit'];
    }

    /**
     * Increment request count
     */
    protected function incrementRequestCount(string $identifier, array $config): void
    {
        $cacheKey = $this->getCacheKey($identifier, $config);
        $window = $config['window'];
        
        // Use sliding window approach
        $currentCount = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $currentCount + 1, $window);
    }

    /**
     * Get cache key for rate limiting
     */
    protected function getCacheKey(string $identifier, array $config): string
    {
        $prefix = config('modules.api.rate_limiting.cache_prefix', 'api_rate_limit');
        $window = $config['window'];
        
        // Create a time-based key for sliding window
        $timeSlot = floor(time() / $window);
        
        return "{$prefix}:{$identifier}:{$timeSlot}";
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, string $identifier, array $config): void
    {
        $cacheKey = $this->getCacheKey($identifier, $config);
        $currentCount = Cache::get($cacheKey, 0);
        $remaining = max(0, $config['limit'] - $currentCount);
        
        $headers = config('modules.api.rate_limiting.headers', []);
        
        $response->headers->set($headers['limit'] ?? 'X-RateLimit-Limit', $config['limit']);
        $response->headers->set($headers['remaining'] ?? 'X-RateLimit-Remaining', $remaining);
        $response->headers->set($headers['reset'] ?? 'X-RateLimit-Reset', $this->getResetTime($config));
    }

    /**
     * Get reset time for rate limit
     */
    protected function getResetTime(array $config): int
    {
        $window = $config['window'];
        $currentSlot = floor(time() / $window);
        
        return ($currentSlot + 1) * $window;
    }

    /**
     * Get retry after time
     */
    protected function getRetryAfter(string $identifier, array $config): int
    {
        return $this->getResetTime($config) - time();
    }

    /**
     * Log rate limit exceeded
     */
    protected function logRateLimitExceeded(Request $request, string $identifier, array $config): void
    {
        Log::warning('API Rate limit exceeded', [
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->fullUrl(),
            'limit' => $config['limit'],
            'window' => $config['window'],
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ]);
    }
} 