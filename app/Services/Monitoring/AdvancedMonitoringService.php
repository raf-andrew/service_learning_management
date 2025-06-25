<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\File;

/**
 * Advanced Monitoring Service
 * 
 * Provides comprehensive system monitoring and observability.
 */
class AdvancedMonitoringService
{
    /**
     * Monitoring configuration
     *
     * @var array<string, mixed>
     */
    protected array $config = [
        'metrics_retention' => 86400, // 24 hours
        'alert_thresholds' => [
            'cpu_usage' => 80,
            'memory_usage' => 85,
            'disk_usage' => 90,
            'error_rate' => 5,
            'response_time' => 1000,
        ],
        'health_checks' => [
            'database' => true,
            'cache' => true,
            'queue' => true,
            'storage' => true,
        ],
    ];

    /**
     * Get system health status
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'overall_status' => $this->getOverallStatus(),
            'components' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'queue' => $this->checkQueueHealth(),
                'storage' => $this->checkStorageHealth(),
                'application' => $this->checkApplicationHealth(),
            ],
            'metrics' => $this->getSystemMetrics(),
            'alerts' => $this->getActiveAlerts(),
        ];
    }

    /**
     * Get overall system status
     *
     * @return string
     */
    protected function getOverallStatus(): string
    {
        $components = [
            $this->checkDatabaseHealth(),
            $this->checkCacheHealth(),
            $this->checkQueueHealth(),
            $this->checkStorageHealth(),
            $this->checkApplicationHealth(),
        ];
        
        $statuses = array_column($components, 'status');
        
        if (in_array('critical', $statuses)) {
            return 'critical';
        } elseif (in_array('warning', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * Check database health
     *
     * @return array<string, mixed>
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $status = 'healthy';
            $message = 'Database connection successful';
            
            if ($responseTime > 100) {
                $status = 'warning';
                $message = 'Database response time is slow';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'response_time_ms' => round($responseTime, 2),
                'connection_count' => $this->getDatabaseConnectionCount(),
                'slow_queries' => $this->getSlowQueryCount(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health
     *
     * @return array<string, mixed>
     */
    protected function checkCacheHealth(): array
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                
                $status = 'healthy';
                $message = 'Redis cache is operational';
                
                // Check memory usage
                $usedMemory = $info['used_memory'] ?? 0;
                $maxMemory = $info['maxmemory'] ?? 0;
                
                if ($maxMemory > 0) {
                    $memoryUsage = ($usedMemory / $maxMemory) * 100;
                    if ($memoryUsage > $this->config['alert_thresholds']['memory_usage']) {
                        $status = 'warning';
                        $message = 'Cache memory usage is high';
                    }
                }
                
                return [
                    'status' => $status,
                    'message' => $message,
                    'driver' => $driver,
                    'memory_usage_percent' => $memoryUsage ?? 0,
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'hit_rate' => $this->calculateCacheHitRate($info),
                ];
            }
            
            // File cache
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'File cache is operational',
                    'driver' => $driver,
                ];
            } else {
                return [
                    'status' => 'critical',
                    'message' => 'File cache is not working properly',
                    'driver' => $driver,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Cache health check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     *
     * @return array<string, mixed>
     */
    protected function checkQueueHealth(): array
    {
        try {
            $driver = config('queue.default');
            
            if ($driver === 'database') {
                $failedJobs = DB::table('failed_jobs')->count();
                $pendingJobs = DB::table('jobs')->count();
                
                $status = 'healthy';
                $message = 'Queue system is operational';
                
                if ($failedJobs > 10) {
                    $status = 'warning';
                    $message = 'High number of failed jobs';
                }
                
                if ($pendingJobs > 100) {
                    $status = 'warning';
                    $message = 'High number of pending jobs';
                }
                
                return [
                    'status' => $status,
                    'message' => $message,
                    'driver' => $driver,
                    'failed_jobs' => $failedJobs,
                    'pending_jobs' => $pendingJobs,
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Queue system is operational',
                'driver' => $driver,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Queue health check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     *
     * @return array<string, mixed>
     */
    protected function checkStorageHealth(): array
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = ($usedSpace / $totalSpace) * 100;
            
            $status = 'healthy';
            $message = 'Storage is operational';
            
            if ($usagePercent > $this->config['alert_thresholds']['disk_usage']) {
                $status = 'warning';
                $message = 'Storage usage is high';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'usage_percent' => round($usagePercent, 2),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'used_space_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Storage health check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check application health
     *
     * @return array<string, mixed>
     */
    protected function checkApplicationHealth(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            $memoryLimit = ini_get('memory_limit');
            
            $status = 'healthy';
            $message = 'Application is running normally';
            
            // Check memory usage
            if ($memoryLimit !== '-1') {
                $limitBytes = $this->convertToBytes($memoryLimit);
                $usagePercent = ($memoryUsage / $limitBytes) * 100;
                
                if ($usagePercent > $this->config['alert_thresholds']['memory_usage']) {
                    $status = 'warning';
                    $message = 'Application memory usage is high';
                }
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
                'memory_limit' => $memoryLimit,
                'uptime' => $this->getApplicationUptime(),
                'version' => app()->version(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Application health check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system metrics
     *
     * @return array<string, mixed>
     */
    protected function getSystemMetrics(): array
    {
        return [
            'performance' => [
                'response_time_avg' => $this->getAverageResponseTime(),
                'requests_per_minute' => $this->getRequestsPerMinute(),
                'error_rate' => $this->getErrorRate(),
            ],
            'resources' => [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
            ],
            'application' => [
                'active_users' => $this->getActiveUsers(),
                'database_connections' => $this->getDatabaseConnectionCount(),
                'cache_hit_rate' => $this->getCacheHitRate(),
            ],
        ];
    }

    /**
     * Get active alerts
     *
     * @return array<string, mixed>
     */
    protected function getActiveAlerts(): array
    {
        $alerts = [];
        $health = $this->getSystemHealth();
        
        // Check component alerts
        foreach ($health['components'] as $component => $status) {
            if ($status['status'] === 'critical') {
                $alerts[] = [
                    'level' => 'critical',
                    'component' => $component,
                    'message' => $status['message'],
                    'timestamp' => now()->toISOString(),
                ];
            } elseif ($status['status'] === 'warning') {
                $alerts[] = [
                    'level' => 'warning',
                    'component' => $component,
                    'message' => $status['message'],
                    'timestamp' => now()->toISOString(),
                ];
            }
        }
        
        // Check metric thresholds
        $metrics = $health['metrics'];
        
        if (isset($metrics['resources']['cpu_usage']) && 
            $metrics['resources']['cpu_usage'] > $this->config['alert_thresholds']['cpu_usage']) {
            $alerts[] = [
                'level' => 'warning',
                'component' => 'system',
                'message' => 'CPU usage is high',
                'value' => $metrics['resources']['cpu_usage'],
                'threshold' => $this->config['alert_thresholds']['cpu_usage'],
                'timestamp' => now()->toISOString(),
            ];
        }
        
        if (isset($metrics['performance']['error_rate']) && 
            $metrics['performance']['error_rate'] > $this->config['alert_thresholds']['error_rate']) {
            $alerts[] = [
                'level' => 'critical',
                'component' => 'application',
                'message' => 'Error rate is high',
                'value' => $metrics['performance']['error_rate'],
                'threshold' => $this->config['alert_thresholds']['error_rate'],
                'timestamp' => now()->toISOString(),
            ];
        }
        
        return $alerts;
    }

    /**
     * Log system metrics
     *
     * @return void
     */
    public function logMetrics(): void
    {
        $metrics = $this->getSystemMetrics();
        $timestamp = now()->toISOString();
        
        foreach ($metrics as $category => $categoryMetrics) {
            foreach ($categoryMetrics as $metric => $value) {
                $key = "metrics:{$category}:{$metric}";
                Cache::put($key, [
                    'value' => $value,
                    'timestamp' => $timestamp,
                ], $this->config['metrics_retention']);
            }
        }
    }

    /**
     * Get performance dashboard data
     *
     * @return array<string, mixed>
     */
    public function getPerformanceDashboard(): array
    {
        return [
            'overview' => [
                'system_status' => $this->getOverallStatus(),
                'uptime' => $this->getApplicationUptime(),
                'total_requests' => $this->getTotalRequests(),
                'error_count' => $this->getErrorCount(),
            ],
            'trends' => [
                'response_time' => $this->getResponseTimeTrend(),
                'error_rate' => $this->getErrorRateTrend(),
                'throughput' => $this->getThroughputTrend(),
            ],
            'components' => $this->getSystemHealth()['components'],
            'alerts' => $this->getActiveAlerts(),
        ];
    }

    // Helper methods...

    /**
     * Get database connection count
     *
     * @return int
     */
    protected function getDatabaseConnectionCount(): int
    {
        try {
            if (config('database.default') === 'mysql') {
                $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                return $result[0]->Value ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get database connection count', ['error' => $e->getMessage()]);
        }
        
        return 0;
    }

    /**
     * Get slow query count
     *
     * @return int
     */
    protected function getSlowQueryCount(): int
    {
        try {
            if (config('database.default') === 'mysql') {
                $result = DB::select("SHOW STATUS LIKE 'Slow_queries'");
                return $result[0]->Value ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get slow query count', ['error' => $e->getMessage()]);
        }
        
        return 0;
    }

    /**
     * Calculate cache hit rate
     *
     * @param array<string, mixed> $info
     * @return float
     */
    protected function calculateCacheHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return 0.0;
        }
        
        return round(($hits / $total) * 100, 2);
    }

    /**
     * Convert memory limit to bytes
     *
     * @param string $limit
     * @return int
     */
    protected function convertToBytes(string $limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        return match($unit) {
            'k' => $value * 1024,
            'm' => $value * 1024 * 1024,
            'g' => $value * 1024 * 1024 * 1024,
            default => $value,
        };
    }

    /**
     * Get application uptime
     *
     * @return string
     */
    protected function getApplicationUptime(): string
    {
        $startTime = Cache::get('app:start_time');
        if (!$startTime) {
            $startTime = now();
            Cache::put('app:start_time', $startTime, 86400);
        }
        
        return $startTime->diffForHumans();
    }

    /**
     * Get average response time
     *
     * @return float
     */
    protected function getAverageResponseTime(): float
    {
        // This would calculate from actual request logs
        return 150.0; // Placeholder
    }

    /**
     * Get requests per minute
     *
     * @return int
     */
    protected function getRequestsPerMinute(): int
    {
        // This would calculate from actual request logs
        return 120; // Placeholder
    }

    /**
     * Get error rate
     *
     * @return float
     */
    protected function getErrorRate(): float
    {
        // This would calculate from actual error logs
        return 2.5; // Placeholder
    }

    /**
     * Get CPU usage
     *
     * @return float
     */
    protected function getCpuUsage(): float
    {
        // This would get actual CPU usage
        return 45.0; // Placeholder
    }

    /**
     * Get memory usage
     *
     * @return float
     */
    protected function getMemoryUsage(): float
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === '-1') {
            return 0.0;
        }
        
        $limitBytes = $this->convertToBytes($memoryLimit);
        return round(($memoryUsage / $limitBytes) * 100, 2);
    }

    /**
     * Get disk usage
     *
     * @return float
     */
    protected function getDiskUsage(): float
    {
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        
        return round(($usedSpace / $totalSpace) * 100, 2);
    }

    /**
     * Get active users
     *
     * @return int
     */
    protected function getActiveUsers(): int
    {
        // This would get actual active users
        return 25; // Placeholder
    }

    /**
     * Get cache hit rate
     *
     * @return float
     */
    protected function getCacheHitRate(): float
    {
        // This would get actual cache hit rate
        return 85.0; // Placeholder
    }

    /**
     * Get total requests
     *
     * @return int
     */
    protected function getTotalRequests(): int
    {
        // This would get actual total requests
        return 15000; // Placeholder
    }

    /**
     * Get error count
     *
     * @return int
     */
    protected function getErrorCount(): int
    {
        // This would get actual error count
        return 45; // Placeholder
    }

    /**
     * Get response time trend
     *
     * @return array<string, mixed>
     */
    protected function getResponseTimeTrend(): array
    {
        // This would get actual response time trend
        return [
            'current' => 150,
            'previous' => 145,
            'change' => 3.4,
        ];
    }

    /**
     * Get error rate trend
     *
     * @return array<string, mixed>
     */
    protected function getErrorRateTrend(): array
    {
        // This would get actual error rate trend
        return [
            'current' => 2.5,
            'previous' => 2.8,
            'change' => -10.7,
        ];
    }

    /**
     * Get throughput trend
     *
     * @return array<string, mixed>
     */
    protected function getThroughputTrend(): array
    {
        // This would get actual throughput trend
        return [
            'current' => 120,
            'previous' => 115,
            'change' => 4.3,
        ];
    }
} 