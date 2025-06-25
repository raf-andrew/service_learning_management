<?php

namespace App\Http\Controllers\Sniffing;

use App\Http\Controllers\Controller;
use App\Services\Sniffing\SniffingMonitoringService;
use App\Services\Sniffing\SniffingAuditService;
use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SniffingDashboardController extends Controller
{
    protected $monitoringService;
    protected $auditService;
    protected $sniffResultRepository;

    public function __construct(
        SniffingMonitoringService $monitoringService,
        SniffingAuditService $auditService,
        SniffResultRepository $sniffResultRepository
    ) {
        $this->monitoringService = $monitoringService;
        $this->auditService = $auditService;
        $this->sniffResultRepository = $sniffResultRepository;
    }

    /**
     * Display the main dashboard
     */
    public function index()
    {
        $metrics = $this->getMetrics();
        $alerts = $this->getAlerts();
        $health = $this->getHealthStatus();

        return view('sniffing.dashboard', compact('metrics', 'alerts', 'health'));
    }

    /**
     * Get performance metrics
     */
    protected function getMetrics(): array
    {
        return [
            'current' => $this->monitoringService->getMetrics(),
            'historical' => $this->getHistoricalMetrics(),
            'trends' => $this->getTrendMetrics(),
        ];
    }

    /**
     * Get historical metrics
     */
    protected function getHistoricalMetrics(): array
    {
        return Cache::remember('sniffing_historical_metrics', 3600, function () {
            return DB::table('sniffing_metrics')
                ->select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('AVG(execution_time) as avg_execution_time'),
                    DB::raw('AVG(memory_usage) as avg_memory_usage'),
                    DB::raw('COUNT(*) as total_runs'),
                    DB::raw('SUM(results_count) as total_results'),
                ])
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
                ->toArray();
        });
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
            'success_rate' => $this->calculateSuccessRate(),
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
     * Calculate success rate
     */
    protected function calculateSuccessRate(): array
    {
        $total = DB::table('sniffing_metrics')->count();
        $successful = DB::table('sniffing_metrics')
            ->where('status', 'completed')
            ->count();

        $rate = $total > 0 ? ($successful / $total) * 100 : 0;

        return [
            'current' => $rate,
            'trend' => $rate >= 95 ? 'good' : ($rate >= 80 ? 'warning' : 'critical'),
            'percentage' => $rate,
        ];
    }

    /**
     * Get active alerts
     */
    protected function getAlerts(): array
    {
        return Cache::remember('sniffing_alerts', 300, function () {
            $alerts = [];

            // Check performance alerts
            $performanceAlerts = $this->checkPerformanceAlerts();
            if (!empty($performanceAlerts)) {
                $alerts['performance'] = $performanceAlerts;
            }

            // Check error rate alerts
            $errorAlerts = $this->checkErrorRateAlerts();
            if (!empty($errorAlerts)) {
                $alerts['errors'] = $errorAlerts;
            }

            // Check system health alerts
            $healthAlerts = $this->checkHealthAlerts();
            if (!empty($healthAlerts)) {
                $alerts['health'] = $healthAlerts;
            }

            return $alerts;
        });
    }

    /**
     * Check performance alerts
     */
    protected function checkPerformanceAlerts(): array
    {
        $alerts = [];
        $metrics = $this->monitoringService->getMetrics();

        // Check execution time
        if ($metrics['current']['execution_time'] > 5) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'warning',
                'message' => 'High execution time detected',
                'value' => $metrics['current']['execution_time'],
                'threshold' => 5,
            ];
        }

        // Check memory usage
        if ($metrics['current']['memory_usage'] > 100 * 1024 * 1024) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'warning',
                'message' => 'High memory usage detected',
                'value' => $metrics['current']['memory_usage'],
                'threshold' => 100 * 1024 * 1024,
            ];
        }

        return $alerts;
    }

    /**
     * Check error rate alerts
     */
    protected function checkErrorRateAlerts(): array
    {
        $alerts = [];
        $errorRate = $this->calculateErrorRate();

        if ($errorRate > 20) {
            $alerts[] = [
                'type' => 'errors',
                'severity' => 'critical',
                'message' => 'High error rate detected',
                'value' => $errorRate,
                'threshold' => 20,
            ];
        }

        return $alerts;
    }

    /**
     * Check health alerts
     */
    protected function checkHealthAlerts(): array
    {
        $alerts = [];
        $health = $this->getHealthStatus();

        foreach ($health as $component => $status) {
            if ($status['status'] !== 'healthy') {
                $alerts[] = [
                    'type' => 'health',
                    'severity' => $status['status'] === 'warning' ? 'warning' : 'critical',
                    'message' => "{$component} is not healthy",
                    'details' => $status['message'],
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get system health status
     */
    protected function getHealthStatus(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'api' => $this->checkApiHealth(),
        ];
    }

    /**
     * Check database health
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection is working',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health
     */
    protected function checkCacheHealth(): array
    {
        try {
            Cache::put('health_check', true, 1);
            $result = Cache::get('health_check');
            return [
                'status' => $result ? 'healthy' : 'warning',
                'message' => $result ? 'Cache is working' : 'Cache write succeeded but read failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Cache system failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     */
    protected function checkStorageHealth(): array
    {
        try {
            $testFile = 'sniffing/health_check.txt';
            Storage::put($testFile, 'test');
            $result = Storage::exists($testFile);
            Storage::delete($testFile);

            return [
                'status' => $result ? 'healthy' : 'warning',
                'message' => $result ? 'Storage system is working' : 'Storage write succeeded but read failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Storage system failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check API health
     */
    protected function checkApiHealth(): array
    {
        try {
            $response = $this->sniffResultRepository->getAll();
            return [
                'status' => 'healthy',
                'message' => 'API is responding correctly',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'API failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate error rate
     */
    protected function calculateErrorRate(): float
    {
        $total = DB::table('sniffing_metrics')->count();
        $errors = DB::table('sniffing_metrics')
            ->where('status', 'error')
            ->count();

        return $total > 0 ? ($errors / $total) * 100 : 0;
    }
} 