<?php

namespace App\Modules\Shared\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Shared\Utils\ArrayHelper;

class PerformanceOptimizationService
{
    /**
     * Query optimization settings
     */
    protected array $querySettings = [
        'max_queries_per_request' => 50,
        'slow_query_threshold' => 100, // milliseconds
        'enable_query_logging' => true,
        'enable_eager_loading' => true,
    ];

    /**
     * Cache optimization settings
     */
    protected array $cacheSettings = [
        'default_ttl' => 3600, // 1 hour
        'long_ttl' => 86400, // 24 hours
        'short_ttl' => 300, // 5 minutes
        'enable_cache_warming' => true,
        'cache_prefix' => 'perf_opt_',
    ];

    /**
     * Optimize a query with eager loading
     */
    public function optimizeQuery(Builder $query, array $relations = [], array $options = []): Builder
    {
        $startTime = microtime(true);

        // Apply eager loading if enabled and relations provided
        if ($this->querySettings['enable_eager_loading'] && !empty($relations)) {
            $query->with($relations);
        }

        // Apply query optimizations
        $query = $this->applyQueryOptimizations($query, $options);

        // Log query performance
        if ($this->querySettings['enable_query_logging']) {
            $this->logQueryPerformance($query, $startTime);
        }

        return $query;
    }

    /**
     * Apply query optimizations
     */
    protected function applyQueryOptimizations(Builder $query, array $options): Builder
    {
        // Select only needed columns if specified
        if (isset($options['select'])) {
            $query->select($options['select']);
        }

        // Apply indexing hints if specified
        if (isset($options['index'])) {
            $query->from(DB::raw($query->getModel()->getTable() . ' USE INDEX (' . $options['index'] . ')'));
        }

        // Apply chunking for large datasets
        if (isset($options['chunk']) && $options['chunk']) {
            $query->chunk($options['chunk_size'] ?? 1000, function ($results) use ($options) {
                if (isset($options['chunk_callback'])) {
                    $options['chunk_callback']($results);
                }
            });
        }

        return $query;
    }

    /**
     * Optimize collection operations
     */
    public function optimizeCollection(Collection $collection, array $options = []): Collection
    {
        // Apply lazy loading for large collections
        if (isset($options['lazy']) && $options['lazy']) {
            return $collection->lazy();
        }

        // Apply chunking for memory optimization
        if (isset($options['chunk']) && $options['chunk']) {
            return $collection->chunk($options['chunk_size'] ?? 1000);
        }

        return $collection;
    }

    /**
     * Smart caching with automatic invalidation
     */
    public function smartCache(string $key, callable $callback, array $options = []): mixed
    {
        $ttl = $options['ttl'] ?? $this->cacheSettings['default_ttl'];
        $tags = $options['tags'] ?? [];
        $cacheKey = $this->cacheSettings['cache_prefix'] . $key;

        // Check if cache warming is enabled
        if ($this->cacheSettings['enable_cache_warming'] && isset($options['warm'])) {
            $this->warmCache($cacheKey, $callback, $ttl, $tags);
        }

        return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Cache warming for frequently accessed data
     */
    protected function warmCache(string $key, callable $callback, int $ttl, array $tags): void
    {
        try {
            $data = $callback();
            Cache::tags($tags)->put($key, $data, $ttl);
            
            Log::info('Cache warmed successfully', [
                'key' => $key,
                'ttl' => $ttl,
                'tags' => $tags,
            ]);
        } catch (\Exception $e) {
            Log::warning('Cache warming failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Batch cache operations
     */
    public function batchCache(array $items, callable $callback, array $options = []): array
    {
        $results = [];
        $batchSize = $options['batch_size'] ?? 100;
        $ttl = $options['ttl'] ?? $this->cacheSettings['default_ttl'];

        foreach (array_chunk($items, $batchSize) as $batch) {
            $batchResults = [];
            
            foreach ($batch as $item) {
                $key = $options['key_generator']($item);
                $cacheKey = $this->cacheSettings['cache_prefix'] . $key;
                
                $batchResults[$key] = Cache::remember($cacheKey, $ttl, function () use ($callback, $item) {
                    return $callback($item);
                });
            }
            
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }

    /**
     * Optimize model relationships
     */
    public function optimizeRelationships(Model $model, array $relations, array $options = []): Model
    {
        // Load missing relationships
        if (isset($options['load_missing']) && $options['load_missing']) {
            $model->loadMissing($relations);
        }

        // Load aggregate relationships
        if (isset($options['load_aggregate'])) {
            foreach ($options['load_aggregate'] as $relation => $aggregate) {
                $model->loadAggregate($relation, $aggregate['column'], $aggregate['function']);
            }
        }

        // Load count relationships
        if (isset($options['load_count'])) {
            $model->loadCount($options['load_count']);
        }

        return $model;
    }

    /**
     * Monitor query performance
     */
    public function monitorQueryPerformance(callable $callback, array $context = []): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $startPeakMemory = memory_get_peak_usage();

        // Get initial query count
        $initialQueryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;

        try {
            $result = $callback();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            $endPeakMemory = memory_get_peak_usage();

            // Get final query count
            $finalQueryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
            $queryCount = $finalQueryCount - $initialQueryCount;

            $metrics = [
                'execution_time' => ($endTime - $startTime) * 1000, // milliseconds
                'memory_used' => ($endMemory - $startMemory) / 1024 / 1024, // MB
                'peak_memory' => ($endPeakMemory - $startPeakMemory) / 1024 / 1024, // MB
                'query_count' => $queryCount,
                'context' => $context,
            ];

            // Log performance metrics
            $this->logPerformanceMetrics($metrics);

            return [
                'result' => $result,
                'metrics' => $metrics,
            ];

        } catch (\Exception $e) {
            Log::error('Performance monitoring failed', [
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
            
            throw $e;
        }
    }

    /**
     * Log query performance
     */
    protected function logQueryPerformance(Builder $query, float $startTime): void
    {
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        if ($executionTime > $this->querySettings['slow_query_threshold']) {
            Log::warning('Slow query detected', [
                'execution_time' => $executionTime,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'threshold' => $this->querySettings['slow_query_threshold'],
            ]);
        }
    }

    /**
     * Log performance metrics
     */
    protected function logPerformanceMetrics(array $metrics): void
    {
        $logLevel = 'info';
        
        if ($metrics['execution_time'] > $this->querySettings['slow_query_threshold']) {
            $logLevel = 'warning';
        }

        if ($metrics['query_count'] > $this->querySettings['max_queries_per_request']) {
            $logLevel = 'warning';
        }

        Log::$logLevel('Performance metrics', $metrics);
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        return [
            'query_settings' => $this->querySettings,
            'cache_settings' => $this->cacheSettings,
            'cache_driver' => config('cache.default'),
            'database_connection' => config('database.default'),
        ];
    }

    /**
     * Optimize configuration for performance
     */
    public function optimizeConfiguration(): void
    {
        // Enable query logging in development
        if (app()->environment('local', 'development')) {
            DB::enableQueryLog();
        }

        // Configure cache for optimal performance
        if (config('cache.default') === 'file') {
            // Ensure cache directory is writable and optimized
            $cachePath = storage_path('framework/cache/data');
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
        }

        // Configure session for performance
        if (config('session.driver') === 'file') {
            $sessionPath = storage_path('framework/sessions');
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
        }
    }

    /**
     * Clear performance-related caches
     */
    public function clearPerformanceCaches(): void
    {
        // Clear application cache
        Cache::flush();

        // Clear route cache
        if (app()->routesAreCached()) {
            app('router')->clearCache();
        }

        // Clear config cache
        if (app()->configurationIsCached()) {
            app('config')->clearCache();
        }

        // Clear view cache
        if (app()->viewCacheExists()) {
            app('view')->clearCache();
        }

        Log::info('Performance caches cleared');
    }

    public function flattenConfig(array $config): array
    {
        return ArrayHelper::flatten($config);
    }
} 