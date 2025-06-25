<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $key = $this->generateKey($request, $type);
        $limits = $this->getLimits($type);
        
        if ($this->isRateLimited($key, $limits)) {
            return $this->rateLimitResponse($limits);
        }

        $response = $next($request);
        
        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $key, $limits);
        
        return $response;
    }

    /**
     * Generate cache key for rate limiting
     */
    private function generateKey(Request $request, string $type): string
    {
        $identifier = $this->getIdentifier($request);
        $route = $request->route() ? $request->route()->getName() : $request->path();
        
        return "rate_limit:{$type}:{$identifier}:{$route}";
    }

    /**
     * Get identifier for rate limiting (IP, user ID, etc.)
     */
    private function getIdentifier(Request $request): string
    {
        // Use authenticated user ID if available
        if ($user = $request->user()) {
            return "user:{$user->id}";
        }

        // Use IP address as fallback
        return "ip:" . $request->ip();
    }

    /**
     * Get rate limits for the given type
     */
    private function getLimits(string $type): array
    {
        $limits = [
            'default' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
                'window_minutes' => 1
            ],
            'strict' => [
                'max_attempts' => 10,
                'decay_minutes' => 1,
                'window_minutes' => 1
            ],
            'api' => [
                'max_attempts' => 100,
                'decay_minutes' => 1,
                'window_minutes' => 1
            ],
            'auth' => [
                'max_attempts' => 5,
                'decay_minutes' => 15,
                'window_minutes' => 15
            ],
            'upload' => [
                'max_attempts' => 10,
                'decay_minutes' => 5,
                'window_minutes' => 5
            ],
            'search' => [
                'max_attempts' => 30,
                'decay_minutes' => 1,
                'window_minutes' => 1
            ]
        ];

        return $limits[$type] ?? $limits['default'];
    }

    /**
     * Check if request is rate limited
     */
    private function isRateLimited(string $key, array $limits): bool
    {
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $limits['max_attempts']) {
            $this->logRateLimitExceeded($key, $attempts, $limits);
            return true;
        }

        // Increment attempt counter
        Cache::put($key, $attempts + 1, $limits['decay_minutes'] * 60);
        
        return false;
    }

    /**
     * Generate rate limit exceeded response
     */
    private function rateLimitResponse(array $limits): Response
    {
        $retryAfter = $limits['decay_minutes'] * 60;
        
        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, string $key, array $limits): void
    {
        $attempts = Cache::get($key, 0);
        $remaining = max(0, $limits['max_attempts'] - $attempts);
        $reset = time() + ($limits['decay_minutes'] * 60);

        $response->headers->set('X-RateLimit-Limit', $limits['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', $reset);
    }

    /**
     * Log rate limit exceeded events
     */
    private function logRateLimitExceeded(string $key, int $attempts, array $limits): void
    {
        Log::warning('Rate limit exceeded', [
            'key' => $key,
            'attempts' => $attempts,
            'limit' => $limits['max_attempts'],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl()
        ]);
    }

    /**
     * Clear rate limit for a specific key
     */
    public static function clearRateLimit(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Get current rate limit status
     */
    public static function getRateLimitStatus(string $key, array $limits): array
    {
        $attempts = Cache::get($key, 0);
        $remaining = max(0, $limits['max_attempts'] - $attempts);
        $reset = time() + ($limits['decay_minutes'] * 60);

        return [
            'attempts' => $attempts,
            'remaining' => $remaining,
            'limit' => $limits['max_attempts'],
            'reset' => $reset,
            'is_limited' => $attempts >= $limits['max_attempts']
        ];
    }
} 