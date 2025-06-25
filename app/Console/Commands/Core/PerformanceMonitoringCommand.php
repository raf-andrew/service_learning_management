<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Performance Monitoring Command
 * 
 * Monitors system performance and generates reports.
 */
class PerformanceMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:performance {--detailed : Show detailed metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system performance metrics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting Performance Monitoring...');
        
        $metrics = $this->collectMetrics();
        $this->displayMetrics($metrics);
        
        $this->info('✅ Performance monitoring completed');
        
        return Command::SUCCESS;
    }

    /**
     * Collect performance metrics
     *
     * @return array<string, mixed>
     */
    private function collectMetrics(): array
    {
        $metrics = [
            'timestamp' => now()->toISOString(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'disk' => $this->getDiskMetrics(),
            'queue' => $this->getQueueMetrics(),
        ];

        // Store metrics for historical tracking
        $this->storeMetrics($metrics);

        return $metrics;
    }

    /**
     * Get database metrics
     *
     * @return array<string, mixed>
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test database connection
            DB::connection()->getPdo();
            
            $connectionTime = microtime(true) - $startTime;
            
            // Get database statistics
            $tables = DB::select("SHOW TABLES");
            $totalTables = count($tables);
            
            // Get table sizes
            $tableSizes = [];
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $size = DB::select("SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB'
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() 
                    AND table_name = ?", [$tableName]);
                
                if (!empty($size)) {
                    $tableSizes[$tableName] = $size[0]->Size_MB;
                }
            }
            
            return [
                'status' => 'connected',
                'connection_time_ms' => round($connectionTime * 1000, 2),
                'total_tables' => $totalTables,
                'total_size_mb' => array_sum($tableSizes),
                'table_sizes' => $tableSizes,
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache metrics
     *
     * @return array<string, mixed>
     */
    private function getCacheMetrics(): array
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                
                return [
                    'driver' => $driver,
                    'status' => 'connected',
                    'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                    'hit_rate' => $this->calculateHitRate($info),
                ];
            }
            
            return [
                'driver' => $driver,
                'status' => 'unknown',
                'message' => 'Metrics not available for this driver',
            ];
            
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get memory metrics
     *
     * @return array<string, mixed>
     */
    private function getMemoryMetrics(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'limit' => $memoryLimit,
            'usage_bytes' => $memoryUsage,
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_bytes' => $memoryPeak,
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'usage_percentage' => $this->calculateMemoryPercentage($memoryLimit, $memoryUsage),
        ];
    }

    /**
     * Get disk metrics
     *
     * @return array<string, mixed>
     */
    private function getDiskMetrics(): array
    {
        $path = storage_path();
        $totalSpace = disk_total_space($path);
        $freeSpace = disk_free_space($path);
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total_bytes' => $totalSpace,
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'free_bytes' => $freeSpace,
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'used_bytes' => $usedSpace,
            'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    /**
     * Get queue metrics
     *
     * @return array<string, mixed>
     */
    private function getQueueMetrics(): array
    {
        try {
            $driver = config('queue.default');
            
            if ($driver === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                return [
                    'driver' => $driver,
                    'status' => 'connected',
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                ];
            }
            
            return [
                'driver' => $driver,
                'status' => 'unknown',
                'message' => 'Metrics not available for this driver',
            ];
            
        } catch (\Exception $e) {
            return [
                'driver' => config('queue.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate
     *
     * @param array<string, mixed> $info
     * @return float
     */
    private function calculateHitRate(array $info): float
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
     * Calculate memory usage percentage
     *
     * @param string $limit
     * @param int $usage
     * @return float
     */
    private function calculateMemoryPercentage(string $limit, int $usage): float
    {
        $limitBytes = $this->convertToBytes($limit);
        
        if ($limitBytes === 0) {
            return 0.0;
        }
        
        return round(($usage / $limitBytes) * 100, 2);
    }

    /**
     * Convert memory limit to bytes
     *
     * @param string $limit
     * @return int
     */
    private function convertToBytes(string $limit): int
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
     * Display metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayMetrics(array $metrics): void
    {
        $this->newLine();
        $this->info('📊 Performance Metrics Report');
        $this->info('Generated: ' . $metrics['timestamp']);
        $this->newLine();

        // Database
        $this->displayDatabaseMetrics($metrics['database']);
        
        // Cache
        $this->displayCacheMetrics($metrics['cache']);
        
        // Memory
        $this->displayMemoryMetrics($metrics['memory']);
        
        // Disk
        $this->displayDiskMetrics($metrics['disk']);
        
        // Queue
        $this->displayQueueMetrics($metrics['queue']);

        if ($this->option('detailed')) {
            $this->displayDetailedMetrics($metrics);
        }
    }

    /**
     * Display database metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayDatabaseMetrics(array $metrics): void
    {
        $this->info('🗄️  Database');
        
        if ($metrics['status'] === 'connected') {
            $this->line("  Status: ✅ Connected");
            $this->line("  Connection Time: {$metrics['connection_time_ms']}ms");
            $this->line("  Total Tables: {$metrics['total_tables']}");
            $this->line("  Total Size: {$metrics['total_size_mb']}MB");
        } else {
            $this->line("  Status: ❌ Error - {$metrics['error']}");
        }
        
        $this->newLine();
    }

    /**
     * Display cache metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayCacheMetrics(array $metrics): void
    {
        $this->info('⚡ Cache');
        $this->line("  Driver: {$metrics['driver']}");
        
        if ($metrics['status'] === 'connected') {
            $this->line("  Status: ✅ Connected");
            $this->line("  Memory: {$metrics['used_memory_human']}");
            $this->line("  Hit Rate: {$metrics['hit_rate']}%");
        } else {
            $this->line("  Status: ❌ Error - {$metrics['error']}");
        }
        
        $this->newLine();
    }

    /**
     * Display memory metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayMemoryMetrics(array $metrics): void
    {
        $this->info('🧠 Memory');
        $this->line("  Limit: {$metrics['limit']}");
        $this->line("  Usage: {$metrics['usage_mb']}MB ({$metrics['usage_percentage']}%)");
        $this->line("  Peak: {$metrics['peak_mb']}MB");
        $this->newLine();
    }

    /**
     * Display disk metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayDiskMetrics(array $metrics): void
    {
        $this->info('💾 Disk');
        $this->line("  Total: {$metrics['total_gb']}GB");
        $this->line("  Used: {$metrics['used_gb']}GB ({$metrics['usage_percentage']}%)");
        $this->line("  Free: {$metrics['free_gb']}GB");
        $this->newLine();
    }

    /**
     * Display queue metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayQueueMetrics(array $metrics): void
    {
        $this->info('📋 Queue');
        $this->line("  Driver: {$metrics['driver']}");
        
        if ($metrics['status'] === 'connected') {
            $this->line("  Status: ✅ Connected");
            $this->line("  Pending Jobs: {$metrics['pending_jobs']}");
            $this->line("  Failed Jobs: {$metrics['failed_jobs']}");
        } else {
            $this->line("  Status: ❌ Error - {$metrics['error']}");
        }
        
        $this->newLine();
    }

    /**
     * Display detailed metrics
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function displayDetailedMetrics(array $metrics): void
    {
        $this->info('📋 Detailed Metrics');
        
        if (isset($metrics['database']['table_sizes'])) {
            $this->info('  Table Sizes:');
            foreach ($metrics['database']['table_sizes'] as $table => $size) {
                $this->line("    {$table}: {$size}MB");
            }
        }
        
        $this->newLine();
    }

    /**
     * Store metrics for historical tracking
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function storeMetrics(array $metrics): void
    {
        try {
            $key = 'performance_metrics_' . date('Y-m-d_H');
            Cache::put($key, $metrics, 3600); // Store for 1 hour
            
            Log::info('Performance metrics collected', $metrics);
        } catch (\Exception $e) {
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
                'metrics' => $metrics,
            ]);
        }
    }
} 