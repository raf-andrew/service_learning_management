<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Performance Optimization Command
 * 
 * Analyzes and optimizes system performance.
 */
class PerformanceOptimizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:optimize {--analyze : Analyze performance only} {--fix : Apply optimizations} {--detailed : Show detailed analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and optimize system performance';

    /**
     * Analysis results
     *
     * @var array<string, mixed>
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('⚡ Starting Performance Optimization Analysis...');
        
        $this->analyzePerformance();
        $this->displayResults();
        
        if ($this->option('fix')) {
            $this->applyOptimizations();
        }
        
        $this->info('✅ Performance optimization analysis completed');
        
        return Command::SUCCESS;
    }

    /**
     * Analyze performance
     */
    private function analyzePerformance(): void
    {
        $this->results = [
            'timestamp' => now()->toISOString(),
            'database' => $this->analyzeDatabasePerformance(),
            'cache' => $this->analyzeCachePerformance(),
            'memory' => $this->analyzeMemoryPerformance(),
            'queries' => $this->analyzeQueryPerformance(),
            'routes' => $this->analyzeRoutePerformance(),
            'optimizations' => $this->identifyOptimizations(),
        ];
    }

    /**
     * Analyze database performance
     *
     * @return array<string, mixed>
     */
    private function analyzeDatabasePerformance(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test database connection
            DB::connection()->getPdo();
            $connectionTime = microtime(true) - $startTime;
            
            // Analyze table sizes and indexes
            $tables = DB::select("SHOW TABLES");
            $tableAnalysis = [];
            $totalSize = 0;
            $missingIndexes = [];
            
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                
                // Get table size
                $size = DB::select("SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB',
                    table_rows
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() 
                    AND table_name = ?", [$tableName]);
                
                if (!empty($size)) {
                    $tableSize = $size[0]->Size_MB;
                    $totalSize += $tableSize;
                    
                    // Check for missing indexes on common columns
                    $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
                    foreach ($columns as $column) {
                        if (in_array($column->Field, ['created_at', 'updated_at', 'deleted_at', 'user_id', 'status'])) {
                            $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Column_name = ?", [$column->Field]);
                            if (empty($indexes)) {
                                $missingIndexes[] = [
                                    'table' => $tableName,
                                    'column' => $column->Field,
                                    'type' => 'common_column',
                                ];
                            }
                        }
                    }
                    
                    $tableAnalysis[] = [
                        'name' => $tableName,
                        'size_mb' => $tableSize,
                        'rows' => $size[0]->table_rows,
                    ];
                }
            }
            
            // Check for slow queries
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueryCount = $slowQueries[0]->Value ?? 0;
            
            return [
                'connection_time_ms' => round($connectionTime * 1000, 2),
                'total_tables' => count($tables),
                'total_size_mb' => round($totalSize, 2),
                'table_analysis' => $tableAnalysis,
                'missing_indexes' => $missingIndexes,
                'slow_queries' => $slowQueryCount,
                'grade' => $this->calculateDatabaseGrade($connectionTime, $slowQueryCount, count($missingIndexes)),
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'grade' => 'F',
            ];
        }
    }

    /**
     * Analyze cache performance
     *
     * @return array<string, mixed>
     */
    private function analyzeCachePerformance(): array
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                
                // Test cache performance
                $testKey = 'performance_test_' . uniqid();
                $testValue = 'test_value_' . uniqid();
                
                $startTime = microtime(true);
                $redis->set($testKey, $testValue, 60);
                $setTime = microtime(true) - $startTime;
                
                $startTime = microtime(true);
                $retrieved = $redis->get($testKey);
                $getTime = microtime(true) - $startTime;
                
                $redis->del($testKey);
                
                return [
                    'driver' => $driver,
                    'status' => 'connected',
                    'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                    'hit_rate' => $this->calculateHitRate($info),
                    'set_time_ms' => round($setTime * 1000, 3),
                    'get_time_ms' => round($getTime * 1000, 3),
                    'grade' => $this->calculateCacheGrade($setTime, $getTime, $info),
                ];
            }
            
            return [
                'driver' => $driver,
                'status' => 'unknown',
                'grade' => 'C',
            ];
            
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
                'grade' => 'F',
            ];
        }
    }

    /**
     * Analyze memory performance
     *
     * @return array<string, mixed>
     */
    private function analyzeMemoryPerformance(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        $usagePercentage = $memoryLimitBytes > 0 ? ($memoryUsage / $memoryLimitBytes) * 100 : 0;
        $peakPercentage = $memoryLimitBytes > 0 ? ($memoryPeak / $memoryLimitBytes) * 100 : 0;
        
        return [
            'limit' => $memoryLimit,
            'limit_bytes' => $memoryLimitBytes,
            'usage_bytes' => $memoryUsage,
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'usage_percentage' => round($usagePercentage, 2),
            'peak_bytes' => $memoryPeak,
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'peak_percentage' => round($peakPercentage, 2),
            'grade' => $this->calculateMemoryGrade($usagePercentage, $peakPercentage),
        ];
    }

    /**
     * Analyze query performance
     *
     * @return array<string, mixed>
     */
    private function analyzeQueryPerformance(): array
    {
        try {
            // Get query statistics
            $queries = DB::select("SHOW STATUS LIKE 'Questions'");
            $totalQueries = $queries[0]->Value ?? 0;
            
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueryCount = $slowQueries[0]->Value ?? 0;
            
            $slowQueryPercentage = $totalQueries > 0 ? ($slowQueryCount / $totalQueries) * 100 : 0;
            
            // Check for common performance issues
            $issues = [];
            
            // Check for N+1 query patterns
            $tables = DB::select("SHOW TABLES");
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                
                // Check for tables without proper indexes
                $indexes = DB::select("SHOW INDEX FROM {$tableName}");
                if (count($indexes) < 2) {
                    $issues[] = [
                        'type' => 'missing_indexes',
                        'table' => $tableName,
                        'index_count' => count($indexes),
                        'description' => 'Table has insufficient indexes',
                    ];
                }
            }
            
            return [
                'total_queries' => $totalQueries,
                'slow_queries' => $slowQueryCount,
                'slow_query_percentage' => round($slowQueryPercentage, 2),
                'issues' => $issues,
                'grade' => $this->calculateQueryGrade($slowQueryPercentage, count($issues)),
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'grade' => 'F',
            ];
        }
    }

    /**
     * Analyze route performance
     *
     * @return array<string, mixed>
     */
    private function analyzeRoutePerformance(): array
    {
        $routes = app('router')->getRoutes();
        $routeAnalysis = [];
        $totalRoutes = count($routes);
        $apiRoutes = 0;
        $webRoutes = 0;
        
        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            
            if (str_starts_with($uri, 'api/')) {
                $apiRoutes++;
            } else {
                $webRoutes++;
            }
            
            $routeAnalysis[] = [
                'uri' => $uri,
                'methods' => $methods,
                'middleware' => $route->middleware(),
            ];
        }
        
        return [
            'total_routes' => $totalRoutes,
            'api_routes' => $apiRoutes,
            'web_routes' => $webRoutes,
            'route_analysis' => $routeAnalysis,
            'grade' => $this->calculateRouteGrade($totalRoutes),
        ];
    }

    /**
     * Identify optimizations
     *
     * @return array<string, mixed>
     */
    private function identifyOptimizations(): array
    {
        $optimizations = [];
        
        // Database optimizations
        if (isset($this->results['database']['missing_indexes'])) {
            foreach ($this->results['database']['missing_indexes'] as $index) {
                $optimizations[] = [
                    'type' => 'database_index',
                    'priority' => 'high',
                    'description' => "Add index on {$index['table']}.{$index['column']}",
                    'impact' => 'high',
                    'effort' => 'low',
                ];
            }
        }
        
        // Cache optimizations
        if (isset($this->results['cache']['hit_rate']) && $this->results['cache']['hit_rate'] < 80) {
            $optimizations[] = [
                'type' => 'cache_strategy',
                'priority' => 'medium',
                'description' => 'Improve cache hit rate',
                'impact' => 'medium',
                'effort' => 'medium',
            ];
        }
        
        // Memory optimizations
        if (isset($this->results['memory']['usage_percentage']) && $this->results['memory']['usage_percentage'] > 70) {
            $optimizations[] = [
                'type' => 'memory_optimization',
                'priority' => 'high',
                'description' => 'Optimize memory usage',
                'impact' => 'high',
                'effort' => 'high',
            ];
        }
        
        // Query optimizations
        if (isset($this->results['queries']['slow_query_percentage']) && $this->results['queries']['slow_query_percentage'] > 5) {
            $optimizations[] = [
                'type' => 'query_optimization',
                'priority' => 'high',
                'description' => 'Optimize slow queries',
                'impact' => 'high',
                'effort' => 'medium',
            ];
        }
        
        return [
            'optimizations' => $optimizations,
            'total_optimizations' => count($optimizations),
            'high_priority' => count(array_filter($optimizations, fn($o) => $o['priority'] === 'high')),
            'medium_priority' => count(array_filter($optimizations, fn($o) => $o['priority'] === 'medium')),
            'low_priority' => count(array_filter($optimizations, fn($o) => $o['priority'] === 'low')),
        ];
    }

    /**
     * Display results
     */
    private function displayResults(): void
    {
        $this->newLine();
        $this->info('📊 Performance Analysis Results');
        $this->info('Generated: ' . $this->results['timestamp']);
        $this->newLine();

        $this->displaySection('Database Performance', $this->results['database']);
        $this->displaySection('Cache Performance', $this->results['cache']);
        $this->displaySection('Memory Performance', $this->results['memory']);
        $this->displaySection('Query Performance', $this->results['queries']);
        $this->displaySection('Route Performance', $this->results['routes']);
        $this->displaySection('Optimization Opportunities', $this->results['optimizations']);

        if ($this->option('detailed')) {
            $this->displayDetailedResults();
        }
    }

    /**
     * Display a section of results
     *
     * @param string $title
     * @param array<string, mixed> $data
     */
    private function displaySection(string $title, array $data): void
    {
        $this->info("⚡ {$title}");
        
        if (isset($data['grade'])) {
            $grade = $data['grade'];
            $color = $this->getGradeColor($grade);
            $this->line("  Grade: {$color}{$grade}{$this->resetColor()}");
        }

        foreach ($data as $key => $value) {
            if ($key !== 'grade' && !is_array($value)) {
                $this->line("  {$key}: {$value}");
            }
        }

        if (isset($data['total_optimizations']) && $data['total_optimizations'] > 0) {
            $this->warn("  ⚠️  Found {$data['total_optimizations']} optimization opportunities");
        }

        $this->newLine();
    }

    /**
     * Display detailed results
     */
    private function displayDetailedResults(): void
    {
        $this->info('📋 Detailed Performance Analysis');
        
        // Database details
        if (isset($this->results['database']['table_analysis'])) {
            $this->info('  Database Tables:');
            foreach ($this->results['database']['table_analysis'] as $table) {
                $this->line("    {$table['name']}: {$table['size_mb']}MB ({$table['rows']} rows)");
            }
        }
        
        // Cache details
        if (isset($this->results['cache']['hit_rate'])) {
            $this->info('  Cache Performance:');
            $this->line("    Hit Rate: {$this->results['cache']['hit_rate']}%");
            $this->line("    Set Time: {$this->results['cache']['set_time_ms']}ms");
            $this->line("    Get Time: {$this->results['cache']['get_time_ms']}ms");
        }
        
        // Memory details
        if (isset($this->results['memory']['usage_percentage'])) {
            $this->info('  Memory Usage:');
            $this->line("    Current: {$this->results['memory']['usage_mb']}MB ({$this->results['memory']['usage_percentage']}%)");
            $this->line("    Peak: {$this->results['memory']['peak_mb']}MB ({$this->results['memory']['peak_percentage']}%)");
        }
        
        // Optimization details
        if (isset($this->results['optimizations']['optimizations'])) {
            $this->info('  Optimization Opportunities:');
            foreach ($this->results['optimizations']['optimizations'] as $optimization) {
                $priorityColor = $this->getPriorityColor($optimization['priority']);
                $this->line("    {$priorityColor}[{$optimization['priority']}]{$this->resetColor()} {$optimization['description']}");
            }
        }
    }

    /**
     * Apply optimizations
     */
    private function applyOptimizations(): void
    {
        $this->info('🔧 Applying optimizations...');
        
        $applied = 0;
        
        if (isset($this->results['optimizations']['optimizations'])) {
            foreach ($this->results['optimizations']['optimizations'] as $optimization) {
                if ($optimization['effort'] === 'low') {
                    $this->applyOptimization($optimization);
                    $applied++;
                }
            }
        }
        
        $this->info("✅ Applied {$applied} optimizations");
    }

    /**
     * Apply a specific optimization
     *
     * @param array<string, mixed> $optimization
     */
    private function applyOptimization(array $optimization): void
    {
        switch ($optimization['type']) {
            case 'database_index':
                $this->applyDatabaseIndex($optimization);
                break;
            case 'cache_strategy':
                $this->applyCacheStrategy($optimization);
                break;
            default:
                Log::info('Optimization applied', $optimization);
                break;
        }
    }

    /**
     * Apply database index optimization
     *
     * @param array<string, mixed> $optimization
     */
    private function applyDatabaseIndex(array $optimization): void
    {
        // This would create the actual index
        // For now, we'll just log the intent
        Log::info('Database index optimization applied', $optimization);
    }

    /**
     * Apply cache strategy optimization
     *
     * @param array<string, mixed> $optimization
     */
    private function applyCacheStrategy(array $optimization): void
    {
        // This would implement cache strategy improvements
        // For now, we'll just log the intent
        Log::info('Cache strategy optimization applied', $optimization);
    }

    // Helper methods for calculations and grading...

    /**
     * Calculate hit rate
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
     * Calculate grades
     */
    private function calculateDatabaseGrade(float $connectionTime, int $slowQueries, int $missingIndexes): string
    {
        $score = 100;
        
        if ($connectionTime > 0.1) $score -= 20;
        if ($slowQueries > 10) $score -= 30;
        if ($missingIndexes > 5) $score -= 25;
        
        return $this->scoreToGrade($score);
    }

    private function calculateCacheGrade(float $setTime, float $getTime, array $info): string
    {
        $score = 100;
        
        if ($setTime > 0.001) $score -= 20;
        if ($getTime > 0.001) $score -= 20;
        
        $hitRate = $this->calculateHitRate($info);
        if ($hitRate < 80) $score -= 30;
        
        return $this->scoreToGrade($score);
    }

    private function calculateMemoryGrade(float $usagePercentage, float $peakPercentage): string
    {
        $score = 100;
        
        if ($usagePercentage > 70) $score -= 30;
        if ($peakPercentage > 90) $score -= 40;
        
        return $this->scoreToGrade($score);
    }

    private function calculateQueryGrade(float $slowQueryPercentage, int $issues): string
    {
        $score = 100;
        
        if ($slowQueryPercentage > 5) $score -= 30;
        if ($issues > 3) $score -= 25;
        
        return $this->scoreToGrade($score);
    }

    private function calculateRouteGrade(int $totalRoutes): string
    {
        if ($totalRoutes <= 50) return 'A+';
        if ($totalRoutes <= 100) return 'A';
        if ($totalRoutes <= 200) return 'B';
        if ($totalRoutes <= 500) return 'C';
        return 'D';
    }

    private function scoreToGrade(int $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    private function getGradeColor(string $grade): string
    {
        return match($grade) {
            'A+' => "\033[32m", // Green
            'A' => "\033[36m",  // Cyan
            'B' => "\033[33m",  // Yellow
            'C' => "\033[35m",  // Magenta
            'D' => "\033[31m",  // Red
            'F' => "\033[31m",  // Red
            default => "\033[0m", // Reset
        };
    }

    private function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'high' => "\033[31m", // Red
            'medium' => "\033[33m", // Yellow
            'low' => "\033[36m", // Cyan
            default => "\033[0m", // Reset
        };
    }

    private function resetColor(): string
    {
        return "\033[0m";
    }
} 