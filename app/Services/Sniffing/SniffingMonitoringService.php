<?php

namespace App\Services\Sniffing;

use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SniffingMonitoringService
{
    protected $sniffResultRepository;
    protected $metrics = [];

    public function __construct(SniffResultRepository $sniffResultRepository)
    {
        $this->sniffResultRepository = $sniffResultRepository;
    }

    /**
     * Track sniffing execution
     */
    public function trackExecution(array $data): void
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage();

        try {
            // Log execution start
            Log::info('Sniffing execution started', [
                'files' => $data['files'] ?? [],
                'severity' => $data['severity'] ?? 'error',
            ]);

            // Track metrics
            $this->metrics['start_time'] = $startTime;
            $this->metrics['memory_start'] = $memoryStart;
            $this->metrics['files_count'] = count($data['files'] ?? []);
            $this->metrics['severity'] = $data['severity'] ?? 'error';

            // Cache metrics
            $this->cacheMetrics();

        } catch (\Exception $e) {
            Log::error('Error tracking sniffing execution', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Record execution completion
     */
    public function recordCompletion(array $results): void
    {
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage();

        try {
            // Calculate metrics
            $this->metrics['end_time'] = $endTime;
            $this->metrics['execution_time'] = $endTime - $this->metrics['start_time'];
            $this->metrics['memory_usage'] = $memoryEnd - $this->metrics['memory_start'];
            $this->metrics['results_count'] = count($results);
            $this->metrics['status'] = 'completed';

            // Log completion
            Log::info('Sniffing execution completed', [
                'execution_time' => $this->metrics['execution_time'],
                'memory_usage' => $this->metrics['memory_usage'],
                'results_count' => $this->metrics['results_count'],
            ]);

            // Update cache
            $this->cacheMetrics();

            // Store metrics in database
            $this->storeMetrics();

        } catch (\Exception $e) {
            Log::error('Error recording sniffing completion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get performance metrics
     */
    public function getMetrics(): array
    {
        return [
            'current' => $this->getCurrentMetrics(),
            'historical' => $this->getHistoricalMetrics(),
            'trends' => $this->getTrendMetrics(),
        ];
    }

    /**
     * Get current metrics
     */
    protected function getCurrentMetrics(): array
    {
        return Cache::get('sniffing_metrics', []);
    }

    /**
     * Get historical metrics
     */
    protected function getHistoricalMetrics(): array
    {
        return DB::table('sniffing_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->toArray();
    }

    /**
     * Get trend metrics
     */
    protected function getTrendMetrics(): array
    {
        return [
            'execution_time' => $this->calculateTrend('execution_time'),
            'memory_usage' => $this->calculateTrend('memory_usage'),
            'results_count' => $this->calculateTrend('results_count'),
        ];
    }

    /**
     * Calculate trend for a metric
     */
    protected function calculateTrend(string $metric): array
    {
        $values = DB::table('sniffing_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->pluck($metric)
            ->toArray();

        if (count($values) < 2) {
            return [
                'current' => $values[0] ?? 0,
                'trend' => 'stable',
                'percentage' => 0,
            ];
        }

        $current = $values[0];
        $previous = $values[1];
        $percentage = $previous ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'current' => $current,
            'trend' => $percentage > 0 ? 'increasing' : ($percentage < 0 ? 'decreasing' : 'stable'),
            'percentage' => abs($percentage),
        ];
    }

    /**
     * Cache metrics
     */
    protected function cacheMetrics(): void
    {
        Cache::put('sniffing_metrics', $this->metrics, now()->addHours(24));
    }

    /**
     * Store metrics in database
     */
    protected function storeMetrics(): void
    {
        DB::table('sniffing_metrics')->insert([
            'execution_time' => $this->metrics['execution_time'],
            'memory_usage' => $this->metrics['memory_usage'],
            'files_count' => $this->metrics['files_count'],
            'results_count' => $this->metrics['results_count'],
            'severity' => $this->metrics['severity'],
            'status' => $this->metrics['status'],
            'created_at' => now(),
        ]);
    }
} 