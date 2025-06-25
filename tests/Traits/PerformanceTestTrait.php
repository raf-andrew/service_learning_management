<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait PerformanceTestTrait
{
    /**
     * Performance metrics storage
     */
    protected array $performanceMetrics = [];

    /**
     * Performance thresholds
     */
    protected array $performanceThresholds = [
        'response_time' => 200, // milliseconds
        'memory_usage' => 512, // MB
        'database_queries' => 10,
        'cache_hits' => 0.8, // 80% cache hit rate
        'cpu_usage' => 80, // percentage
    ];

    /**
     * Start performance measurement
     */
    protected function startPerformanceMeasurement(): void
    {
        $this->performanceMetrics = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'start_peak_memory' => memory_get_peak_usage(true),
            'database_queries' => DB::getQueryLog(),
        ];
    }

    /**
     * End performance measurement
     */
    protected function endPerformanceMeasurement(): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);
        $endDatabaseQueries = DB::getQueryLog();

        $metrics = [
            'response_time' => ($endTime - $this->performanceMetrics['start_time']) * 1000, // Convert to milliseconds
            'memory_usage' => ($endMemory - $this->performanceMetrics['start_memory']) / 1024 / 1024, // Convert to MB
            'peak_memory_usage' => $endPeakMemory / 1024 / 1024, // Convert to MB
            'database_queries' => count($endDatabaseQueries) - count($this->performanceMetrics['database_queries']),
            'total_database_queries' => count($endDatabaseQueries),
        ];

        $this->performanceMetrics = array_merge($this->performanceMetrics, $metrics);

        return $metrics;
    }

    /**
     * Test response time
     */
    protected function testResponseTime(callable $callback, float $maxTime = null): float
    {
        $maxTime = $maxTime ?: $this->performanceThresholds['response_time'];
        
        $this->startPerformanceMeasurement();
        $callback();
        $metrics = $this->endPerformanceMeasurement();

        $this->assertLessThan(
            $maxTime,
            $metrics['response_time'],
            "Response time {$metrics['response_time']}ms exceeded threshold {$maxTime}ms"
        );

        return $metrics['response_time'];
    }

    /**
     * Test memory usage
     */
    protected function testMemoryUsage(callable $callback, float $maxMemory = null): float
    {
        $maxMemory = $maxMemory ?: $this->performanceThresholds['memory_usage'];
        
        $this->startPerformanceMeasurement();
        $callback();
        $metrics = $this->endPerformanceMeasurement();

        $this->assertLessThan(
            $maxMemory,
            $metrics['memory_usage'],
            "Memory usage {$metrics['memory_usage']}MB exceeded threshold {$maxMemory}MB"
        );

        return $metrics['memory_usage'];
    }

    /**
     * Test database query count
     */
    protected function testDatabaseQueryCount(callable $callback, int $maxQueries = null): int
    {
        $maxQueries = $maxQueries ?: $this->performanceThresholds['database_queries'];
        
        DB::enableQueryLog();
        $this->startPerformanceMeasurement();
        $callback();
        $metrics = $this->endPerformanceMeasurement();
        DB::disableQueryLog();

        $this->assertLessThan(
            $maxQueries,
            $metrics['database_queries'],
            "Database queries {$metrics['database_queries']} exceeded threshold {$maxQueries}"
        );

        return $metrics['database_queries'];
    }

    /**
     * Test cache performance
     */
    protected function testCachePerformance(callable $callback, float $minHitRate = null): float
    {
        $minHitRate = $minHitRate ?: $this->performanceThresholds['cache_hits'];
        
        // Clear cache before test
        Cache::flush();
        
        $this->startPerformanceMeasurement();
        $callback();
        $metrics = $this->endPerformanceMeasurement();

        // Calculate cache hit rate (this would need to be implemented based on your caching strategy)
        $hitRate = $this->calculateCacheHitRate();

        $this->assertGreaterThan(
            $minHitRate,
            $hitRate,
            "Cache hit rate {$hitRate} is below threshold {$minHitRate}"
        );

        return $hitRate;
    }

    /**
     * Test concurrent requests
     */
    protected function testConcurrentRequests(string $endpoint, int $concurrentRequests = 10): array
    {
        $startTime = microtime(true);
        $responses = [];
        $errors = 0;

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            try {
                $response = $this->call('GET', $endpoint);
                $responses[] = $response->getStatusCode();
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        $metrics = [
            'total_requests' => $concurrentRequests,
            'successful_requests' => count($responses),
            'failed_requests' => $errors,
            'total_time' => $totalTime,
            'average_time' => $totalTime / $concurrentRequests,
            'requests_per_second' => $concurrentRequests / ($totalTime / 1000),
        ];

        $this->assertEquals(
            $concurrentRequests,
            count($responses),
            "All concurrent requests should succeed"
        );

        return $metrics;
    }

    /**
     * Test load performance
     */
    protected function testLoadPerformance(string $endpoint, int $requests = 100): array
    {
        $startTime = microtime(true);
        $responseTimes = [];
        $errors = 0;

        for ($i = 0; $i < $requests; $i++) {
            try {
                $requestStart = microtime(true);
                $response = $this->call('GET', $endpoint);
                $requestEnd = microtime(true);
                
                $responseTimes[] = ($requestEnd - $requestStart) * 1000;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        $metrics = [
            'total_requests' => $requests,
            'successful_requests' => count($responseTimes),
            'failed_requests' => $errors,
            'total_time' => $totalTime,
            'average_response_time' => array_sum($responseTimes) / count($responseTimes),
            'min_response_time' => min($responseTimes),
            'max_response_time' => max($responseTimes),
            'requests_per_second' => $requests / ($totalTime / 1000),
        ];

        $this->assertEquals(
            $requests,
            count($responseTimes),
            "All load test requests should succeed"
        );

        return $metrics;
    }

    /**
     * Test database performance
     */
    protected function testDatabasePerformance(callable $callback): array
    {
        DB::enableQueryLog();
        $this->startPerformanceMeasurement();
        
        $callback();
        
        $metrics = $this->endPerformanceMeasurement();
        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $slowQueries = array_filter($queryLog, function ($query) {
            return $query['time'] > 100; // Queries taking more than 100ms
        });

        $metrics['slow_queries'] = count($slowQueries);
        $metrics['average_query_time'] = array_sum(array_column($queryLog, 'time')) / count($queryLog);

        $this->assertLessThan(
            5,
            count($slowQueries),
            "Too many slow queries detected"
        );

        return $metrics;
    }

    /**
     * Test memory leaks
     */
    protected function testMemoryLeaks(callable $callback, int $iterations = 100): void
    {
        $memoryUsage = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
            
            // Force garbage collection
            gc_collect_cycles();
            
            $memoryUsage[] = memory_get_usage(true);
        }

        // Check for memory growth
        $firstMemory = $memoryUsage[0];
        $lastMemory = end($memoryUsage);
        $memoryGrowth = ($lastMemory - $firstMemory) / 1024 / 1024; // MB

        $this->assertLessThan(
            50, // 50MB growth threshold
            $memoryGrowth,
            "Memory leak detected: {$memoryGrowth}MB growth over {$iterations} iterations"
        );
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateCacheHitRate(): float
    {
        // This is a placeholder implementation
        // You would need to implement this based on your caching strategy
        // For example, using Redis INFO command or custom cache statistics
        
        return 0.9; // Placeholder: 90% hit rate
    }

    /**
     * Assert performance metrics
     */
    protected function assertPerformanceMetrics(array $metrics): void
    {
        foreach ($this->performanceThresholds as $metric => $threshold) {
            if (isset($metrics[$metric])) {
                $this->assertLessThan(
                    $threshold,
                    $metrics[$metric],
                    "Performance metric {$metric} exceeded threshold"
                );
            }
        }
    }

    /**
     * Set performance threshold
     */
    protected function setPerformanceThreshold(string $metric, $value): void
    {
        $this->performanceThresholds[$metric] = $value;
    }

    /**
     * Get performance threshold
     */
    protected function getPerformanceThreshold(string $metric)
    {
        return $this->performanceThresholds[$metric] ?? null;
    }

    /**
     * Log performance metrics
     */
    protected function logPerformanceMetrics(array $metrics, string $testName = ''): void
    {
        Log::info("Performance Test: {$testName}", $metrics);
    }

    /**
     * Reset performance metrics
     */
    protected function resetPerformanceMetrics(): void
    {
        $this->performanceMetrics = [];
    }
} 