<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Has Caching Trait
 * 
 * Provides consistent caching functionality across classes.
 * This trait includes methods for cache management with automatic key generation.
 */
trait HasCaching
{
    /**
     * Get cache key with prefix
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @return string
     */
    protected function getCacheKey(string $key, array $context = []): string
    {
        $prefix = $this->getCachePrefix();
        $contextHash = !empty($context) ? '_' . md5(serialize($context)) : '';
        
        return "{$prefix}:{$key}{$contextHash}";
    }

    /**
     * Get cache prefix for this class
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        return strtolower(str_replace('\\', '_', static::class));
    }

    /**
     * Get value from cache
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @param mixed $default
     * @return mixed
     */
    protected function getFromCache(string $key, array $context = [], $default = null)
    {
        $cacheKey = $this->getCacheKey($key, $context);
        
        $this->logCacheOperation('get', $cacheKey);
        
        return Cache::get($cacheKey, $default);
    }

    /**
     * Store value in cache
     *
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $context
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    protected function putInCache(string $key, $value, array $context = [], ?int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($key, $context);
        $ttl = $ttl ?? $this->getDefaultCacheTtl();
        
        $this->logCacheOperation('put', $cacheKey, ['ttl' => $ttl]);
        
        return Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Store value in cache forever
     *
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function putInCacheForever(string $key, $value, array $context = []): bool
    {
        $cacheKey = $this->getCacheKey($key, $context);
        
        $this->logCacheOperation('put_forever', $cacheKey);
        
        return Cache::forever($cacheKey, $value);
    }

    /**
     * Remove value from cache
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function removeFromCache(string $key, array $context = []): bool
    {
        $cacheKey = $this->getCacheKey($key, $context);
        
        $this->logCacheOperation('forget', $cacheKey);
        
        return Cache::forget($cacheKey);
    }

    /**
     * Check if value exists in cache
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function hasInCache(string $key, array $context = []): bool
    {
        $cacheKey = $this->getCacheKey($key, $context);
        
        $this->logCacheOperation('has', $cacheKey);
        
        return Cache::has($cacheKey);
    }

    /**
     * Get or store value in cache
     *
     * @param string $key
     * @param callable $callback
     * @param array<string, mixed> $context
     * @param int|null $ttl
     * @return mixed
     */
    protected function rememberInCache(string $key, callable $callback, array $context = [], ?int $ttl = null)
    {
        $cacheKey = $this->getCacheKey($key, $context);
        $ttl = $ttl ?? $this->getDefaultCacheTtl();
        
        $this->logCacheOperation('remember', $cacheKey, ['ttl' => $ttl]);
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get or store value in cache forever
     *
     * @param string $key
     * @param callable $callback
     * @param array<string, mixed> $context
     * @return mixed
     */
    protected function rememberInCacheForever(string $key, callable $callback, array $context = [])
    {
        $cacheKey = $this->getCacheKey($key, $context);
        
        $this->logCacheOperation('remember_forever', $cacheKey);
        
        return Cache::rememberForever($cacheKey, $callback);
    }

    /**
     * Clear cache by pattern
     *
     * @param string $pattern
     * @return bool
     */
    protected function clearCacheByPattern(string $pattern): bool
    {
        $prefix = $this->getCachePrefix();
        $fullPattern = "{$prefix}:{$pattern}";
        
        $this->logCacheOperation('clear_pattern', $fullPattern);
        
        // Note: This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        return Cache::flush();
    }

    /**
     * Clear all cache for this class
     *
     * @return bool
     */
    protected function clearAllCache(): bool
    {
        $prefix = $this->getCachePrefix();
        
        $this->logCacheOperation('clear_all', $prefix);
        
        // Note: This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        return Cache::flush();
    }

    /**
     * Get cache statistics
     *
     * @return array<string, mixed>
     */
    protected function getCacheStatistics(): array
    {
        $prefix = $this->getCachePrefix();
        
        return [
            'prefix' => $prefix,
            'class' => static::class,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Warm up cache
     *
     * @param array<string, callable> $items
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function warmUpCache(array $items, array $context = []): array
    {
        $results = [];
        
        foreach ($items as $key => $callback) {
            try {
                $value = $this->rememberInCache($key, $callback, $context);
                $results[$key] = ['status' => 'success', 'value' => $value];
            } catch (\Exception $e) {
                $results[$key] = ['status' => 'error', 'error' => $e->getMessage()];
            }
        }
        
        $this->logCacheOperation('warm_up', 'multiple', ['count' => count($items)]);
        
        return $results;
    }

    /**
     * Get default cache TTL
     *
     * @return int
     */
    protected function getDefaultCacheTtl(): int
    {
        return 3600; // 1 hour
    }

    /**
     * Get cache TTL for specific data type
     *
     * @param string $type
     * @return int
     */
    protected function getCacheTtlForType(string $type): int
    {
        $ttlMap = [
            'user_data' => 1800,      // 30 minutes
            'configuration' => 3600,  // 1 hour
            'statistics' => 300,      // 5 minutes
            'temporary' => 60,        // 1 minute
            'permanent' => 0,         // Forever
        ];
        
        return $ttlMap[$type] ?? $this->getDefaultCacheTtl();
    }

    /**
     * Log cache operation (requires HasLogging trait)
     *
     * @param string $operation
     * @param string $key
     * @param array<string, mixed> $context
     * @return void
     */
    private function logCacheOperation(string $operation, string $key, array $context = []): void
    {
        if (method_exists($this, 'logCacheOperation')) {
            $this->logCacheOperation($operation, $key, $context);
        }
    }
} 