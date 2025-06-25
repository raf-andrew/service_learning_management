<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Advanced Caching Service
 * 
 * Provides intelligent caching strategies with automatic optimization.
 */
class AdvancedCachingService
{
    /**
     * Cache strategies
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $strategies = [
        'aggressive' => [
            'ttl' => 3600, // 1 hour
            'tags' => true,
            'compression' => true,
            'invalidation' => 'smart',
        ],
        'moderate' => [
            'ttl' => 1800, // 30 minutes
            'tags' => true,
            'compression' => false,
            'invalidation' => 'time',
        ],
        'conservative' => [
            'ttl' => 300, // 5 minutes
            'tags' => false,
            'compression' => false,
            'invalidation' => 'time',
        ],
    ];

    /**
     * Cache statistics
     *
     * @var array<string, mixed>
     */
    protected array $statistics = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'compressions' => 0,
    ];

    /**
     * Get or store value with intelligent caching
     *
     * @param string $key
     * @param callable $callback
     * @param string $strategy
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function remember(string $key, callable $callback, string $strategy = 'moderate', array $context = [])
    {
        $cacheKey = $this->buildCacheKey($key, $context);
        $strategyConfig = $this->strategies[$strategy] ?? $this->strategies['moderate'];
        
        // Check if we should use cache
        if (!$this->shouldCache($key, $context)) {
            $this->statistics['misses']++;
            $this->logCacheOperation('miss', $cacheKey, $context);
            return $callback();
        }
        
        // Try to get from cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $this->statistics['hits']++;
            $this->logCacheOperation('hit', $cacheKey, $context);
            return $this->decompress($cached, $strategyConfig);
        }
        
        // Generate value
        $value = $callback();
        
        // Store in cache
        $this->store($cacheKey, $value, $strategyConfig, $context);
        
        return $value;
    }

    /**
     * Store value with advanced features
     *
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $strategy
     * @param array<string, mixed> $context
     * @return void
     */
    public function store(string $key, $value, array $strategy, array $context = []): void
    {
        $this->statistics['sets']++;
        
        // Compress if needed
        if ($strategy['compression']) {
            $value = $this->compress($value);
            $this->statistics['compressions']++;
        }
        
        // Store with tags if enabled
        if ($strategy['tags']) {
            $tags = $this->extractTags($key, $context);
            Cache::tags($tags)->put($key, $value, $strategy['ttl']);
        } else {
            Cache::put($key, $value, $strategy['ttl']);
        }
        
        $this->logCacheOperation('store', $key, $context);
    }

    /**
     * Get value with fallback
     *
     * @param string $key
     * @param mixed $default
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function get(string $key, $default = null, array $context = [])
    {
        $cacheKey = $this->buildCacheKey($key, $context);
        $value = Cache::get($cacheKey, $default);
        
        if ($value !== $default) {
            $this->statistics['hits']++;
            $this->logCacheOperation('hit', $cacheKey, $context);
        } else {
            $this->statistics['misses']++;
            $this->logCacheOperation('miss', $cacheKey, $context);
        }
        
        return $value;
    }

    /**
     * Delete cache with pattern matching
     *
     * @param string $pattern
     * @param array<string, mixed> $context
     * @return bool
     */
    public function delete(string $pattern, array $context = []): bool
    {
        $this->statistics['deletes']++;
        
        if (str_contains($pattern, '*')) {
            return $this->deleteByPattern($pattern, $context);
        }
        
        $cacheKey = $this->buildCacheKey($pattern, $context);
        $result = Cache::forget($cacheKey);
        
        $this->logCacheOperation('delete', $cacheKey, $context);
        
        return $result;
    }

    /**
     * Clear cache by tags
     *
     * @param array<string> $tags
     * @return bool
     */
    public function clearByTags(array $tags): bool
    {
        $this->statistics['deletes']++;
        
        try {
            Cache::tags($tags)->flush();
            $this->logCacheOperation('clear_tags', implode(',', $tags));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear cache by tags', ['tags' => $tags, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Warm up cache
     *
     * @param array<string, callable> $items
     * @param string $strategy
     * @return array<string, mixed>
     */
    public function warmUp(array $items, string $strategy = 'moderate'): array
    {
        $results = [];
        
        foreach ($items as $key => $callback) {
            try {
                $results[$key] = $this->remember($key, $callback, $strategy);
            } catch (\Exception $e) {
                Log::error('Failed to warm up cache', ['key' => $key, 'error' => $e->getMessage()]);
                $results[$key] = null;
            }
        }
        
        return $results;
    }

    /**
     * Get cache statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $total = $this->statistics['hits'] + $this->statistics['misses'];
        $hitRate = $total > 0 ? ($this->statistics['hits'] / $total) * 100 : 0;
        
        return [
            'hits' => $this->statistics['hits'],
            'misses' => $this->statistics['misses'],
            'sets' => $this->statistics['sets'],
            'deletes' => $this->statistics['deletes'],
            'compressions' => $this->statistics['compressions'],
            'hit_rate' => round($hitRate, 2),
            'total_operations' => $total,
        ];
    }

    /**
     * Optimize cache
     *
     * @return array<string, mixed>
     */
    public function optimize(): array
    {
        $optimizations = [];
        
        // Analyze hit rate
        $statistics = $this->getStatistics();
        if ($statistics['hit_rate'] < 80) {
            $optimizations[] = [
                'type' => 'low_hit_rate',
                'description' => 'Cache hit rate is below 80%',
                'current' => $statistics['hit_rate'] . '%',
                'recommendation' => 'Review cache keys and TTL settings',
            ];
        }
        
        // Check for memory usage
        if (config('cache.default') === 'redis') {
            $memoryInfo = Redis::info('memory');
            $usedMemory = $memoryInfo['used_memory'] ?? 0;
            $maxMemory = $memoryInfo['maxmemory'] ?? 0;
            
            if ($maxMemory > 0) {
                $memoryUsage = ($usedMemory / $maxMemory) * 100;
                if ($memoryUsage > 80) {
                    $optimizations[] = [
                        'type' => 'high_memory_usage',
                        'description' => 'Cache memory usage is high',
                        'current' => round($memoryUsage, 2) . '%',
                        'recommendation' => 'Consider increasing memory or optimizing cache keys',
                    ];
                }
            }
        }
        
        return $optimizations;
    }

    /**
     * Build cache key with context
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @return string
     */
    protected function buildCacheKey(string $key, array $context = []): string
    {
        $prefix = config('cache.prefix', 'laravel');
        $contextHash = !empty($context) ? ':' . md5(serialize($context)) : '';
        
        return "{$prefix}:{$key}{$contextHash}";
    }

    /**
     * Determine if we should cache
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function shouldCache(string $key, array $context = []): bool
    {
        // Don't cache in debug mode for certain keys
        if (config('app.debug') && str_contains($key, 'debug')) {
            return false;
        }
        
        // Don't cache for certain contexts
        if (isset($context['no_cache']) && $context['no_cache']) {
            return false;
        }
        
        return true;
    }

    /**
     * Compress value
     *
     * @param mixed $value
     * @return string
     */
    protected function compress($value): string
    {
        $serialized = serialize($value);
        
        // Only compress if it's worth it
        if (strlen($serialized) < 100) {
            return $serialized;
        }
        
        $compressed = gzcompress($serialized, 6);
        return 'gz:' . base64_encode($compressed);
    }

    /**
     * Decompress value
     *
     * @param mixed $value
     * @param array<string, mixed> $strategy
     * @return mixed
     */
    protected function decompress($value, array $strategy)
    {
        if (!$strategy['compression']) {
            return $value;
        }
        
        if (is_string($value) && str_starts_with($value, 'gz:')) {
            $compressed = base64_decode(substr($value, 3));
            $serialized = gzuncompress($compressed);
            return unserialize($serialized);
        }
        
        return $value;
    }

    /**
     * Extract tags from key and context
     *
     * @param string $key
     * @param array<string, mixed> $context
     * @return array<string>
     */
    protected function extractTags(string $key, array $context = []): array
    {
        $tags = [];
        
        // Extract tags from key
        if (preg_match('/^(\w+):/', $key, $matches)) {
            $tags[] = $matches[1];
        }
        
        // Add context tags
        if (isset($context['tags']) && is_array($context['tags'])) {
            $tags = array_merge($tags, $context['tags']);
        }
        
        // Add user tag if available
        if (isset($context['user_id'])) {
            $tags[] = 'user:' . $context['user_id'];
        }
        
        return array_unique($tags);
    }

    /**
     * Delete cache by pattern
     *
     * @param string $pattern
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function deleteByPattern(string $pattern, array $context = []): bool
    {
        if (config('cache.default') === 'redis') {
            return $this->deleteByPatternRedis($pattern, $context);
        }
        
        // Fallback for other drivers
        return Cache::flush();
    }

    /**
     * Delete cache by pattern using Redis
     *
     * @param string $pattern
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function deleteByPatternRedis(string $pattern, array $context = []): bool
    {
        try {
            $prefix = config('cache.prefix', 'laravel');
            $fullPattern = "{$prefix}:{$pattern}";
            
            $keys = Redis::keys($fullPattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Log cache operation
     *
     * @param string $operation
     * @param string $key
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logCacheOperation(string $operation, string $key, array $context = []): void
    {
        if (config('app.debug')) {
            Log::debug("Cache {$operation}", [
                'key' => $key,
                'context' => $context,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
} 