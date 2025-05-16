<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheManager
{
    protected $defaultTtl;
    protected $cacheableMethods;
    protected $cacheableStatusCodes;

    public function __construct()
    {
        $this->defaultTtl = config('cache.ttl', 3600);
        $this->cacheableMethods = ['GET', 'HEAD'];
        $this->cacheableStatusCodes = [200, 203, 300, 301, 302, 304, 307, 308];
    }

    public function shouldCache(Request $request, Response $response): bool
    {
        // Check if request method is cacheable
        if (!in_array($request->method(), $this->cacheableMethods)) {
            return false;
        }

        // Check if response status code is cacheable
        if (!in_array($response->status(), $this->cacheableStatusCodes)) {
            return false;
        }

        // Check Cache-Control headers
        if ($response->headers->has('Cache-Control')) {
            $cacheControl = $response->headers->get('Cache-Control');
            if (str_contains($cacheControl, 'no-store') || str_contains($cacheControl, 'private')) {
                return false;
            }
        }

        return true;
    }

    public function getCacheKey(Request $request): string
    {
        $key = 'response:' . $request->method() . ':' . $request->path();
        
        // Include query parameters in cache key
        if ($request->query()) {
            $key .= ':' . md5(json_encode($request->query()));
        }

        // Include request headers that affect response
        $varyHeaders = $this->getVaryHeaders($request);
        if ($varyHeaders) {
            $key .= ':' . md5(json_encode($varyHeaders));
        }

        return $key;
    }

    public function cacheResponse(Request $request, Response $response): void
    {
        if (!$this->shouldCache($request, $response)) {
            return;
        }

        $key = $this->getCacheKey($request);
        $ttl = $this->getCacheTtl($response);

        $cacheData = [
            'content' => $response->getContent(),
            'status' => $response->status(),
            'headers' => $response->headers->all(),
        ];

        Cache::put($key, $cacheData, $ttl);
        Log::info('Response cached', ['key' => $key, 'ttl' => $ttl]);
    }

    public function getCachedResponse(Request $request): ?Response
    {
        $key = $this->getCacheKey($request);
        $cached = Cache::get($key);

        if (!$cached) {
            return null;
        }

        $response = new Response(
            $cached['content'],
            $cached['status']
        );

        foreach ($cached['headers'] as $name => $value) {
            $response->headers->set($name, $value);
        }

        // Add cache hit header
        $response->headers->set('X-Cache', 'HIT');
        
        return $response;
    }

    public function invalidateCache(string $pattern): void
    {
        $keys = Cache::get('cache_keys:' . $pattern, []);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Cache::forget('cache_keys:' . $pattern);
        Log::info('Cache invalidated', ['pattern' => $pattern]);
    }

    public function addCacheHeaders(Response $response, int $ttl): void
    {
        $response->headers->set('Cache-Control', 'public, max-age=' . $ttl);
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
        $response->headers->set('Vary', 'Accept-Encoding');
    }

    protected function getCacheTtl(Response $response): int
    {
        if ($response->headers->has('Cache-Control')) {
            $cacheControl = $response->headers->get('Cache-Control');
            if (preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
                return (int) $matches[1];
            }
        }

        return $this->defaultTtl;
    }

    protected function getVaryHeaders(Request $request): array
    {
        $varyHeaders = [];
        $headers = ['Accept', 'Accept-Language', 'Accept-Encoding'];

        foreach ($headers as $header) {
            if ($request->headers->has($header)) {
                $varyHeaders[$header] = $request->headers->get($header);
            }
        }

        return $varyHeaders;
    }
} 