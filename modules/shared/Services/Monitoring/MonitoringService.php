<?php

namespace App\Modules\Shared\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemAlert;
use Modules\Shared\Contracts\MonitoringContract;
use Modules\Shared\Traits\HasAuditLog;

class MonitoringService implements MonitoringContract
{
    use HasAuditLog;

    /**
     * Monitoring configuration
     */
    protected array $config = [
        'enabled' => true,
        'alert_thresholds' => [
            'response_time' => 2000, // milliseconds
            'memory_usage' => 512, // MB
            'error_rate' => 5, // percentage
            'queue_size' => 1000,
            'database_connections' => 50,
        ],
        'notification_channels' => [
            'email' => true,
            'slack' => false,
            'webhook' => false,
        ],
        'check_intervals' => [
            'health' => 60, // seconds
            'performance' => 300, // seconds
            'security' => 600, // seconds
        ],
    ];

    /**
     * Health check status
     */
    protected array $healthStatus = [
        'database' => 'unknown',
        'cache' => 'unknown',
        'queue' => 'unknown',
        'storage' => 'unknown',
        'services' => 'unknown',
    ];

    /**
     * Performance metrics
     */
    protected array $performanceMetrics = [
        'response_times' => [],
        'memory_usage' => [],
        'query_count' => [],
        'error_count' => 0,
        'request_count' => 0,
    ];

    /**
     * Initialize monitoring
     */
    public function __construct()
    {
        $this->initializeMonitoring();
    }

    /**
     * Initialize monitoring system
     */
    protected function initializeMonitoring(): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        // Register event listeners
        $this->registerEventListeners();

        // Schedule monitoring checks
        $this->scheduleMonitoringChecks();

        Log::info('Monitoring service initialized');
    }

    /**
     * Register event listeners for monitoring
     */
    protected function registerEventListeners(): void
    {
        // Monitor database queries
        DB::listen(function ($query) {
            $this->monitorDatabaseQuery($query);
        });

        // Monitor queue jobs
        Queue::failing(function ($event) {
            $this->monitorQueueFailure($event);
        });

        // Monitor exceptions
        Event::listen('Illuminate\Log\Events\MessageLogged', function ($event) {
            if ($event->level === 'error' || $event->level === 'critical') {
                $this->monitorError($event);
            }
        });
    }

    /**
     * Schedule monitoring checks
     */
    protected function scheduleMonitoringChecks(): void
    {
        // Health checks
        if (app()->runningInConsole()) {
            // Schedule health checks for console applications
            $this->scheduleHealthChecks();
        }
    }

    /**
     * Perform comprehensive health check
     */
    public function performHealthCheck(): array
    {
        $results = [
            'timestamp' => now()->toISOString(),
            'overall_status' => 'healthy',
            'checks' => [],
            'alerts' => [],
        ];

        // Database health check
        $dbHealth = $this->checkDatabaseHealth();
        $results['checks']['database'] = $dbHealth;
        $results['alerts'] = array_merge($results['alerts'], $dbHealth['alerts'] ?? []);

        // Cache health check
        $cacheHealth = $this->checkCacheHealth();
        $results['checks']['cache'] = $cacheHealth;
        $results['alerts'] = array_merge($results['alerts'], $cacheHealth['alerts'] ?? []);

        // Queue health check
        $queueHealth = $this->checkQueueHealth();
        $results['checks']['queue'] = $queueHealth;
        $results['alerts'] = array_merge($results['alerts'], $queueHealth['alerts'] ?? []);

        // Storage health check
        $storageHealth = $this->checkStorageHealth();
        $results['checks']['storage'] = $storageHealth;
        $results['alerts'] = array_merge($results['alerts'], $storageHealth['alerts'] ?? []);

        // Services health check
        $servicesHealth = $this->checkServicesHealth();
        $results['checks']['services'] = $servicesHealth;
        $results['alerts'] = array_merge($results['alerts'], $servicesHealth['alerts'] ?? []);

        // Determine overall status
        $failedChecks = array_filter($results['checks'], fn($check) => $check['status'] === 'failed');
        if (count($failedChecks) > 0) {
            $results['overall_status'] = 'unhealthy';
        } elseif (count($results['alerts']) > 0) {
            $results['overall_status'] = 'warning';
        }

        // Store health check results
        $this->storeHealthCheckResults($results);

        // Send alerts if needed
        if (!empty($results['alerts'])) {
            $this->sendAlerts($results['alerts']);
        }

        return $results;
    }

    /**
     * Check database health
     */
    protected function checkDatabaseHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'details' => [],
            'alerts' => [],
        ];

        try {
            // Test database connection
            DB::connection()->getPdo();
            $check['details'][] = 'Database connection successful';

            // Check connection count
            $connectionCount = DB::connection()->select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
            if ($connectionCount > $this->config['alert_thresholds']['database_connections']) {
                $check['alerts'][] = "High database connection count: {$connectionCount}";
                $check['status'] = 'warning';
            }

            // Check for slow queries
            $slowQueries = DB::connection()->select('SHOW STATUS LIKE "Slow_queries"')[0]->Value ?? 0;
            if ($slowQueries > 0) {
                $check['alerts'][] = "Slow queries detected: {$slowQueries}";
                $check['status'] = 'warning';
            }

        } catch (\Exception $e) {
            $check['status'] = 'failed';
            $check['details'][] = 'Database connection failed: ' . $e->getMessage();
            $check['alerts'][] = 'Database connection failed';
        }

        return $check;
    }

    /**
     * Check cache health
     */
    protected function checkCacheHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'details' => [],
            'alerts' => [],
        ];

        try {
            // Test cache connection
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            if ($value === 'test') {
                $check['details'][] = 'Cache connection successful';
            } else {
                $check['status'] = 'failed';
                $check['alerts'][] = 'Cache read/write test failed';
            }

        } catch (\Exception $e) {
            $check['status'] = 'failed';
            $check['details'][] = 'Cache connection failed: ' . $e->getMessage();
            $check['alerts'][] = 'Cache connection failed';
        }

        return $check;
    }

    /**
     * Check queue health
     */
    protected function checkQueueHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'details' => [],
            'alerts' => [],
        ];

        try {
            // Check queue size (if using database queue)
            if (config('queue.default') === 'database') {
                $queueSize = DB::table('jobs')->count();
                $check['details'][] = "Queue size: {$queueSize}";

                if ($queueSize > $this->config['alert_thresholds']['queue_size']) {
                    $check['alerts'][] = "Large queue size: {$queueSize}";
                    $check['status'] = 'warning';
                }
            } else {
                $check['details'][] = 'Queue driver: ' . config('queue.default');
            }

        } catch (\Exception $e) {
            $check['status'] = 'failed';
            $check['details'][] = 'Queue health check failed: ' . $e->getMessage();
            $check['alerts'][] = 'Queue health check failed';
        }

        return $check;
    }

    /**
     * Check storage health
     */
    protected function checkStorageHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'details' => [],
            'alerts' => [],
        ];

        try {
            // Check disk space
            $diskFree = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

            $check['details'][] = "Disk usage: " . round($diskUsagePercent, 2) . "%";

            if ($diskUsagePercent > 90) {
                $check['alerts'][] = "Critical disk usage: " . round($diskUsagePercent, 2) . "%";
                $check['status'] = 'failed';
            } elseif ($diskUsagePercent > 80) {
                $check['alerts'][] = "High disk usage: " . round($diskUsagePercent, 2) . "%";
                $check['status'] = 'warning';
            }

            // Check storage permissions
            $storagePath = storage_path();
            if (!is_writable($storagePath)) {
                $check['alerts'][] = 'Storage directory not writable';
                $check['status'] = 'failed';
            }

        } catch (\Exception $e) {
            $check['status'] = 'failed';
            $check['details'][] = 'Storage health check failed: ' . $e->getMessage();
            $check['alerts'][] = 'Storage health check failed';
        }

        return $check;
    }

    /**
     * Check services health
     */
    protected function checkServicesHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'details' => [],
            'alerts' => [],
        ];

        try {
            // Check memory usage
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            $check['details'][] = "Memory usage: " . round($memoryUsage, 2) . " MB";

            if ($memoryUsage > $this->config['alert_thresholds']['memory_usage']) {
                $check['alerts'][] = "High memory usage: " . round($memoryUsage, 2) . " MB";
                $check['status'] = 'warning';
            }

            // Check PHP version
            $phpVersion = PHP_VERSION;
            $check['details'][] = "PHP version: {$phpVersion}";

            // Check Laravel version
            $laravelVersion = app()->version();
            $check['details'][] = "Laravel version: {$laravelVersion}";

        } catch (\Exception $e) {
            $check['status'] = 'failed';
            $check['details'][] = 'Services health check failed: ' . $e->getMessage();
            $check['alerts'][] = 'Services health check failed';
        }

        return $check;
    }

    /**
     * Monitor database query performance
     */
    protected function monitorDatabaseQuery($query): void
    {
        $executionTime = $query->time;
        
        if ($executionTime > $this->config['alert_thresholds']['response_time']) {
            $this->createAlert('slow_query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'execution_time' => $executionTime,
                'connection' => $query->connectionName,
            ]);
        }

        // Store performance metrics
        $this->performanceMetrics['query_count'][] = [
            'time' => now(),
            'execution_time' => $executionTime,
            'sql' => $query->sql,
        ];
    }

    /**
     * Monitor queue failures
     */
    protected function monitorQueueFailure($event): void
    {
        $this->createAlert('queue_failure', [
            'job' => get_class($event->job),
            'exception' => $event->exception->getMessage(),
            'queue' => $event->job->getQueue(),
        ]);

        $this->performanceMetrics['error_count']++;
    }

    /**
     * Monitor errors
     */
    protected function monitorError($event): void
    {
        $this->createAlert('error', [
            'message' => $event->message,
            'level' => $event->level,
            'context' => $event->context,
        ]);

        $this->performanceMetrics['error_count']++;
    }

    /**
     * Create an alert
     */
    protected function createAlert(string $type, array $data): void
    {
        $alert = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'data' => $data,
            'severity' => $this->determineAlertSeverity($type),
        ];

        // Store alert
        $this->storeAlert($alert);

        // Send immediate notification for critical alerts
        if ($alert['severity'] === 'critical') {
            $this->sendImmediateAlert($alert);
        }
    }

    /**
     * Determine alert severity
     */
    protected function determineAlertSeverity(string $type): string
    {
        $severityMap = [
            'database_connection_failed' => 'critical',
            'cache_connection_failed' => 'warning',
            'queue_failure' => 'warning',
            'slow_query' => 'info',
            'high_memory_usage' => 'warning',
            'disk_full' => 'critical',
            'error' => 'warning',
        ];

        return $severityMap[$type] ?? 'info';
    }

    /**
     * Store health check results
     */
    protected function storeHealthCheckResults(array $results): void
    {
        $key = 'monitoring:health_check:' . now()->format('Y-m-d_H');
        Cache::put($key, $results, 3600); // Store for 1 hour
    }

    /**
     * Store alert
     */
    protected function storeAlert(array $alert): void
    {
        $key = 'monitoring:alerts:' . now()->format('Y-m-d');
        $alerts = Cache::get($key, []);
        $alerts[] = $alert;
        Cache::put($key, $alerts, 86400); // Store for 24 hours
    }

    /**
     * Send alerts
     */
    protected function sendAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            $this->sendAlert($alert);
        }
    }

    /**
     * Send immediate alert
     */
    protected function sendImmediateAlert(array $alert): void
    {
        $this->sendAlert($alert);
    }

    /**
     * Send alert notification
     */
    protected function sendAlert(array $alert): void
    {
        if ($this->config['notification_channels']['email']) {
            try {
                // Send email notification
                $this->sendEmailAlert($alert);
            } catch (\Exception $e) {
                Log::error('Failed to send email alert', [
                    'error' => $e->getMessage(),
                    'alert' => $alert,
                ]);
            }
        }

        // Log alert
        Log::warning('System alert', $alert);
    }

    /**
     * Send email alert
     */
    protected function sendEmailAlert(array $alert): void
    {
        // This would typically send to system administrators
        // For now, we'll just log it
        Log::info('Email alert would be sent', $alert);
    }

    /**
     * Get monitoring statistics
     */
    public function getMonitoringStats(): array
    {
        return [
            'config' => $this->config,
            'health_status' => $this->healthStatus,
            'performance_metrics' => $this->performanceMetrics,
            'recent_alerts' => $this->getRecentAlerts(),
        ];
    }

    /**
     * Get recent alerts
     */
    protected function getRecentAlerts(): array
    {
        $key = 'monitoring:alerts:' . now()->format('Y-m-d');
        return Cache::get($key, []);
    }

    /**
     * Schedule health checks (for console applications)
     */
    protected function scheduleHealthChecks(): void
    {
        // This would be implemented in a console command
        // For now, we'll just log that it would be scheduled
        Log::info('Health checks would be scheduled');
    }
} 