<?php

namespace App\Traits\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cacheable Trait
 * 
 * Provides caching functionality for services.
 * Implements smart caching strategies with automatic invalidation.
 */
trait CacheableTrait
{
    /**
     * @var bool
     */
    protected bool $cachingEnabled = true;

    /**
     * @var int
     */
    protected int $defaultCacheTtl = 3600;

    /**
     * @var array
     */
    protected array $cacheTags = [];

    /**
     * Initialize caching
     */
    protected function initializeCaching(): void
    {
        $this->cachingEnabled = config('cache.enabled', true);
        $this->defaultCacheTtl = config('cache.default_ttl', 3600);
        $this->cacheTags = [$this->getServiceName()];
    }

    /**
     * Get cached value or execute callback
     */
    protected function remember(string $key, callable $callback, int $ttl = null, array $tags = []): mixed
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }

        $ttl = $ttl ?? $this->defaultCacheTtl;
        $cacheKey = $this->generateCacheKey($key);
        $allTags = array_merge($this->cacheTags, $tags);

        return Cache::tags($allTags)->remember($cacheKey, $ttl, function () use ($callback, $key) {
            $this->logCacheMiss($key);
            return $callback();
        });
    }

    /**
     * Get cached value
     */
    protected function getCached(string $key, array $tags = []): mixed
    {
        if (!$this->cachingEnabled) {
            return null;
        }

        $cacheKey = $this->generateCacheKey($key);
        $allTags = array_merge($this->cacheTags, $tags);

        $value = Cache::tags($allTags)->get($cacheKey);
        
        if ($value !== null) {
            $this->logCacheHit($key);
        }

        return $value;
    }

    /**
     * Store value in cache
     */
    protected function putCached(string $key, mixed $value, int $ttl = null, array $tags = []): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $ttl = $ttl ?? $this->defaultCacheTtl;
        $cacheKey = $this->generateCacheKey($key);
        $allTags = array_merge($this->cacheTags, $tags);

        Cache::tags($allTags)->put($cacheKey, $value, $ttl);
        $this->logCacheStore($key);
    }

    /**
     * Forget cached value
     */
    protected function forgetCached(string $key, array $tags = []): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $cacheKey = $this->generateCacheKey($key);
        $allTags = array_merge($this->cacheTags, $tags);

        Cache::tags($allTags)->forget($cacheKey);
        $this->logCacheForget($key);
    }

    /**
     * Clear all cache for service
     */
    protected function clearServiceCache(): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        Cache::tags($this->cacheTags)->flush();
        $this->logCacheClear();
    }

    /**
     * Clear cache by tags
     */
    protected function clearCacheByTags(array $tags): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $allTags = array_merge($this->cacheTags, $tags);
        Cache::tags($allTags)->flush();
        $this->logCacheClearByTags($tags);
    }

    /**
     * Generate cache key
     */
    protected function generateCacheKey(string $key): string
    {
        $serviceName = $this->getServiceName();
        $fullKey = "service:{$serviceName}:{$key}";
        return md5($fullKey);
    }

    /**
     * Cache model with relationships
     */
    protected function cacheModel(string $modelClass, $id, Model $model, array $relations = [], int $ttl = null): void
    {
        $key = "model:{$modelClass}:{$id}";
        $tags = ["model:{$modelClass}", "id:{$id}"];
        
        if (!empty($relations)) {
            $key .= ':' . implode(',', $relations);
            $tags[] = 'with_relations';
        }

        $this->putCached($key, $model, $ttl, $tags);
    }

    /**
     * Get cached model
     */
    protected function getCachedModel(string $modelClass, $id, array $relations = []): ?Model
    {
        $key = "model:{$modelClass}:{$id}";
        $tags = ["model:{$modelClass}", "id:{$id}"];
        
        if (!empty($relations)) {
            $key .= ':' . implode(',', $relations);
            $tags[] = 'with_relations';
        }

        return $this->getCached($key, $tags);
    }

    /**
     * Cache query results
     */
    protected function cacheQueryResults(string $queryKey, mixed $results, int $ttl = null, array $tags = []): void
    {
        $key = "query:{$queryKey}";
        $allTags = array_merge($tags, ['query_results']);
        $this->putCached($key, $results, $ttl, $allTags);
    }

    /**
     * Get cached query results
     */
    protected function getCachedQueryResults(string $queryKey, array $tags = []): mixed
    {
        $key = "query:{$queryKey}";
        $allTags = array_merge($tags, ['query_results']);
        return $this->getCached($key, $allTags);
    }

    /**
     * Cache collection with pagination
     */
    protected function cacheCollection(string $collectionKey, mixed $collection, int $ttl = null, array $tags = []): void
    {
        $key = "collection:{$collectionKey}";
        $allTags = array_merge($tags, ['collection']);
        $this->putCached($key, $collection, $ttl, $allTags);
    }

    /**
     * Get cached collection
     */
    protected function getCachedCollection(string $collectionKey, array $tags = []): mixed
    {
        $key = "collection:{$collectionKey}";
        $allTags = array_merge($tags, ['collection']);
        return $this->getCached($key, $allTags);
    }

    /**
     * Warm cache for frequently accessed data
     */
    protected function warmCache(callable $dataProvider, string $key, int $ttl = null, array $tags = []): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        try {
            $data = $dataProvider();
            $this->putCached($key, $data, $ttl, $tags);
            $this->logCacheWarm($key);
        } catch (\Exception $e) {
            $this->logCacheWarmError($key, $e->getMessage());
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        return [
            'enabled' => $this->cachingEnabled,
            'default_ttl' => $this->defaultCacheTtl,
            'tags' => $this->cacheTags,
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix')
        ];
    }

    /**
     * Log cache hit
     */
    protected function logCacheHit(string $key): void
    {
        Log::debug("Cache hit: {$key}", [
            'service' => $this->getServiceName(),
            'key' => $key
        ]);
    }

    /**
     * Log cache miss
     */
    protected function logCacheMiss(string $key): void
    {
        Log::debug("Cache miss: {$key}", [
            'service' => $this->getServiceName(),
            'key' => $key
        ]);
    }

    /**
     * Log cache store
     */
    protected function logCacheStore(string $key): void
    {
        Log::debug("Cache store: {$key}", [
            'service' => $this->getServiceName(),
            'key' => $key
        ]);
    }

    /**
     * Log cache forget
     */
    protected function logCacheForget(string $key): void
    {
        Log::debug("Cache forget: {$key}", [
            'service' => $this->getServiceName(),
            'key' => $key
        ]);
    }

    /**
     * Log cache clear
     */
    protected function logCacheClear(): void
    {
        Log::info("Cache clear for service: {$this->getServiceName()}", [
            'service' => $this->getServiceName(),
            'tags' => $this->cacheTags
        ]);
    }

    /**
     * Log cache clear by tags
     */
    protected function logCacheClearByTags(array $tags): void
    {
        Log::info("Cache clear by tags", [
            'service' => $this->getServiceName(),
            'tags' => $tags
        ]);
    }

    /**
     * Log cache warm
     */
    protected function logCacheWarm(string $key): void
    {
        Log::info("Cache warm: {$key}", [
            'service' => $this->getServiceName(),
            'key' => $key
        ]);
    }

    /**
     * Log cache warm error
     */
    protected function logCacheWarmError(string $key, string $error): void
    {
        Log::error("Cache warm error: {$key}", [
            'service' => $this->getServiceName(),
            'key' => $key,
            'error' => $error
        ]);
    }

    /**
     * Get service name for cache key generation
     */
    abstract protected function getServiceName(): string;
} 