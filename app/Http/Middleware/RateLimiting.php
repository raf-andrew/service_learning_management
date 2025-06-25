<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimiting
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = $this->getDecayMinutes($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $key);
            
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $identifier = $request->ip();
        
        if ($request->user()) {
            $identifier .= '|' . $request->user()->id;
        }

        return sha1($identifier . '|' . $request->route()?->uri() ?? $request->path());
    }

    /**
     * Get maximum attempts based on request type
     */
    protected function getMaxAttempts(Request $request): int
    {
        if ($request->is('api/*')) {
            return config('modules.modules.api.rate_limiting.requests_per_minute', 60);
        }

        return config('app.rate_limit_web', 120);
    }

    /**
     * Get decay minutes for rate limiting
     */
    protected function getDecayMinutes(Request $request): int
    {
        return 1; // 1 minute window
    }

    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key) + 1;
    }

    /**
     * Add rate limiting headers to response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Log rate limit exceeded events
     */
    protected function logRateLimitExceeded(Request $request, string $key): void
    {
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
            'key' => $key,
            'user_agent' => $request->userAgent(),
        ]);
    }
} 