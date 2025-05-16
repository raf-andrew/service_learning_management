<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from rate limiting.
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

        $key = $this->resolveRequestSignature($request);

        if ($this->tooManyAttempts($key)) {
            return $this->handleTooManyAttempts($request, $key);
        }

        $this->incrementAttempts($key);

        $response = $next($request);

        return $this->addHeaders($response, $key);
    }

    /**
     * Resolve the request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $identifier = $this->config('rate_limit.identifier', 'ip');
        $prefix = $this->config('rate_limit.prefix', 'rate_limit');

        switch ($identifier) {
            case 'ip':
                $key = $request->ip();
                break;
            case 'user':
                $key = $request->user() ? $request->user()->id : $request->ip();
                break;
            case 'route':
                $key = $request->route()->getName() ?: $request->path();
                break;
            default:
                $key = $request->ip();
        }

        return "{$prefix}:{$key}";
    }

    /**
     * Determine if the request has too many attempts.
     *
     * @param  string  $key
     * @return bool
     */
    protected function tooManyAttempts(string $key): bool
    {
        $maxAttempts = $this->config('rate_limit.max_attempts', 60);
        $decayMinutes = $this->config('rate_limit.decay_minutes', 1);

        return Cache::get($key, 0) >= $maxAttempts;
    }

    /**
     * Increment the counter for a given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function incrementAttempts(string $key): void
    {
        $decayMinutes = $this->config('rate_limit.decay_minutes', 1);

        Cache::add($key, 0, $decayMinutes * 60);
        Cache::increment($key);
    }

    /**
     * Handle a request that has too many attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleTooManyAttempts(Request $request, string $key): Response
    {
        $retryAfter = $this->getRetryAfter($key);
        $this->logRateLimitExceeded($request, $key);

        return response()->json([
            'error' => 'Too Many Attempts',
            'message' => 'Please try again later.',
            'retry_after' => $retryAfter
        ], 429);
    }

    /**
     * Get the number of seconds until the next retry.
     *
     * @param  string  $key
     * @return int
     */
    protected function getRetryAfter(string $key): int
    {
        $decayMinutes = $this->config('rate_limit.decay_minutes', 1);
        return Cache::get($key . ':timer', $decayMinutes * 60);
    }

    /**
     * Add the rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $key
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, string $key): Response
    {
        $maxAttempts = $this->config('rate_limit.max_attempts', 60);
        $remainingAttempts = $maxAttempts - Cache::get($key, 0);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
            'X-RateLimit-Reset' => time() + $this->getRetryAfter($key)
        ]);

        return $response;
    }

    /**
     * Log a rate limit exceeded event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return void
     */
    protected function logRateLimitExceeded(Request $request, string $key): void
    {
        Log::warning('Rate Limit Exceeded', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'key' => $key
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
        $except = array_merge($this->except, $this->config('rate_limit.except', []));

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