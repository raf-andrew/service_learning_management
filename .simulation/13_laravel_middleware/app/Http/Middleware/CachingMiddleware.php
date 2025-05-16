<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CachingMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from caching.
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

        if ($request->isMethod('GET')) {
            $cacheKey = $this->generateCacheKey($request);
            
            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $this->addCacheHeaders($response, true);
                return $response;
            }

            $response = $next($request);
            
            if ($this->shouldCacheResponse($response)) {
                $this->cacheResponse($cacheKey, $response);
            }

            $this->addCacheHeaders($response, false);
            return $response;
        }

        return $next($request);
    }

    /**
     * Generate a unique cache key for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function generateCacheKey(Request $request): string
    {
        $key = 'response_' . md5($request->fullUrl());
        
        if ($request->hasHeader('Authorization')) {
            $key .= '_' . md5($request->header('Authorization'));
        }

        return $key;
    }

    /**
     * Determine if the response should be cached.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldCacheResponse(Response $response): bool
    {
        if (!$response->isSuccessful()) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');
        return str_contains($contentType, 'application/json') || 
               str_contains($contentType, 'text/html');
    }

    /**
     * Cache the response.
     *
     * @param  string  $key
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function cacheResponse(string $key, Response $response): void
    {
        $ttl = $this->config('cache.ttl', 3600);
        
        Cache::put($key, $response, $ttl);
        
        Log::debug('Response Cached', [
            'key' => $key,
            'ttl' => $ttl,
            'status' => $response->getStatusCode()
        ]);
    }

    /**
     * Add cache headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  bool  $fromCache
     * @return void
     */
    protected function addCacheHeaders(Response $response, bool $fromCache): void
    {
        $response->headers->set('X-Cache', $fromCache ? 'HIT' : 'MISS');
        $response->headers->set('Cache-Control', 'public, max-age=' . $this->config('cache.ttl', 3600));
    }

    /**
     * Determine if the request should pass through the middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        $except = array_merge($this->except, $this->config('cache.except', []));

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