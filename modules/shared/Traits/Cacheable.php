<?php

namespace Modules\Shared\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Cacheable
{
    /**
     * Cache TTL in seconds
     */
    protected int $cacheTtl = 3600;

    /**
     * Whether caching is enabled
     */
    protected bool $cachingEnabled = true;

    /**
     * Cache key prefix
     */
    protected string $cachePrefix = 'cacheable';

    /**
     * Execute a method with caching
     */
    protected function withCache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }

        $cacheKey = $this->buildCacheKey($key);
        $ttl = $ttl ?? $this->cacheTtl;

        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache operation failed, falling back to direct execution', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    /**
     * Execute a method with cache tags
     */
    protected function withCacheTags(array $tags, string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }

        $cacheKey = $this->buildCacheKey($key);
        $ttl = $ttl ?? $this->cacheTtl;

        try {
            return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache tags operation failed, falling back to direct execution', [
                'cache_key' => $cacheKey,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    /**
     * Build cache key with prefix
     */
    protected function buildCacheKey(string $key): string
    {
        return "{$this->cachePrefix}.{$key}";
    }

    /**
     * Clear cache by key
     */
    public function clearCache(string $key): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $cacheKey = $this->buildCacheKey($key);
        Cache::forget($cacheKey);
    }

    /**
     * Clear cache by pattern
     */
    public function clearCachePattern(string $pattern): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $cacheKey = $this->buildCacheKey($pattern);
        Cache::forget($cacheKey);
    }

    /**
     * Clear cache by tags
     */
    public function clearCacheTags(array $tags): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        try {
            Cache::tags($tags)->flush();
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache tags', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear all cache for this instance
     */
    public function clearAllCache(): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $pattern = $this->buildCacheKey('*');
        Cache::forget($pattern);
    }

    /**
     * Check if cache exists
     */
    public function hasCache(string $key): bool
    {
        if (!$this->cachingEnabled) {
            return false;
        }

        $cacheKey = $this->buildCacheKey($key);
        return Cache::has($cacheKey);
    }

    /**
     * Get cache value
     */
    public function getCache(string $key, mixed $default = null): mixed
    {
        if (!$this->cachingEnabled) {
            return $default;
        }

        $cacheKey = $this->buildCacheKey($key);
        return Cache::get($cacheKey, $default);
    }

    /**
     * Set cache value
     */
    public function setCache(string $key, mixed $value, ?int $ttl = null): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $cacheKey = $this->buildCacheKey($key);
        $ttl = $ttl ?? $this->cacheTtl;
        Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Increment cache value
     */
    public function incrementCache(string $key, int $value = 1): int
    {
        if (!$this->cachingEnabled) {
            return 0;
        }

        $cacheKey = $this->buildCacheKey($key);
        return Cache::increment($cacheKey, $value);
    }

    /**
     * Decrement cache value
     */
    public function decrementCache(string $key, int $value = 1): int
    {
        if (!$this->cachingEnabled) {
            return 0;
        }

        $cacheKey = $this->buildCacheKey($key);
        return Cache::decrement($cacheKey, $value);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        return [
            'cache_prefix' => $this->cachePrefix,
            'caching_enabled' => $this->cachingEnabled,
            'cache_ttl' => $this->cacheTtl,
        ];
    }

    /**
     * Enable caching
     */
    public function enableCaching(): void
    {
        $this->cachingEnabled = true;
    }

    /**
     * Disable caching
     */
    public function disableCaching(): void
    {
        $this->cachingEnabled = false;
    }

    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }

    /**
     * Set cache prefix
     */
    public function setCachePrefix(string $prefix): void
    {
        $this->cachePrefix = $prefix;
    }
} 