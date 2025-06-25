<?php

namespace App\Services;

use App\Models\HealthAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing health alerts and monitoring thresholds
 * 
 * @package App\Services
 */
class AlertService implements AlertServiceInterface
{
    protected $thresholds = [
        'cpu' => [
            'warning' => 70,
            'critical' => 90
        ],
        'memory' => [
            'warning' => 75,
            'critical' => 90
        ],
        'disk' => [
            'warning' => 80,
            'critical' => 95
        ]
    ];

    /**
     * Process metrics and generate alerts
     * 
     * @param string $serviceName The name of the service being monitored
     * @param array $metrics The metrics data to process
     * @return array Array of generated alerts
     * @throws \Exception If processing fails
     */
    public function processMetrics(string $serviceName, array $metrics): array
    {
        $alerts = [];

        try {
            // Check CPU usage
            if (isset($metrics['cpu']['usage'])) {
                $cpuAlerts = $this->checkCpuUsage($metrics['cpu']);
                if (!empty($cpuAlerts)) {
                    $alerts = array_merge($alerts, $cpuAlerts);
                }
            }

            // Check memory usage
            if (isset($metrics['memory']['usage'])) {
                $memoryAlerts = $this->checkMemoryUsage($metrics['memory']);
                if (!empty($memoryAlerts)) {
                    $alerts = array_merge($alerts, $memoryAlerts);
                }
            }

            // Check disk usage
            if (isset($metrics['disk']['usage'])) {
                $diskAlerts = $this->checkDiskUsage($metrics['disk']);
                if (!empty($diskAlerts)) {
                    $alerts = array_merge($alerts, $diskAlerts);
                }
            }

            // Store alerts if any were generated
            if (!empty($alerts)) {
                $this->storeAlerts($serviceName, $alerts);
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Alert processing failed', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);

            return [
                [
                    'type' => 'error',
                    'level' => 'critical',
                    'message' => 'Failed to process alerts: ' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Validate the input data
     * 
     * @param array $data The data to validate
     * @return bool True if valid, false otherwise
     * @throws \InvalidArgumentException If validation fails
     */
    public function validate(array $data): bool
    {
        try {
            if (empty($data['service_name'])) {
                throw new \InvalidArgumentException('Service name is required');
            }

            if (empty($data['metrics'])) {
                throw new \InvalidArgumentException('Metrics data is required');
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Validation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process the input data
     * 
     * @param array $data The data to process
     * @return array The processed data
     * @throws \Exception If processing fails
     */
    public function process(array $data): array
    {
        try {
            $this->validate($data);
            return $this->processMetrics($data['service_name'], $data['metrics']);
        } catch (\Exception $e) {
            Log::error('Processing failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle the alert processing
     * 
     * @param array $data The data to handle
     * @return array The handled data
     * @throws \Exception If handling fails
     */
    public function handle(array $data): array
    {
        try {
            $alerts = $this->process($data);
            return [
                'success' => true,
                'alerts' => $alerts
            ];
        } catch (\Exception $e) {
            Log::error('Alert handling failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check CPU usage and generate alerts
     * 
     * @param array $cpuMetrics The CPU metrics to check
     * @return array Array of generated alerts
     */
    protected function checkCpuUsage(array $cpuMetrics): array
    {
        try {
            $alerts = [];
            $usage = $cpuMetrics['usage'];

            if ($usage >= $this->thresholds['cpu']['critical']) {
                $alerts[] = [
                    'type' => 'cpu',
                    'level' => 'critical',
                    'message' => "CPU usage is critically high: {$usage}%"
                ];
            } elseif ($usage >= $this->thresholds['cpu']['warning']) {
                $alerts[] = [
                    'type' => 'cpu',
                    'level' => 'warning',
                    'message' => "CPU usage is high: {$usage}%"
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('CPU usage check failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check memory usage and generate alerts
     * 
     * @param array $memoryMetrics The memory metrics to check
     * @return array Array of generated alerts
     */
    protected function checkMemoryUsage(array $memoryMetrics): array
    {
        try {
            $alerts = [];
            $usage = $memoryMetrics['usage'];

            if ($usage >= $this->thresholds['memory']['critical']) {
                $alerts[] = [
                    'type' => 'memory',
                    'level' => 'critical',
                    'message' => "Memory usage is critically high: {$usage}%"
                ];
            } elseif ($usage >= $this->thresholds['memory']['warning']) {
                $alerts[] = [
                    'type' => 'memory',
                    'level' => 'warning',
                    'message' => "Memory usage is high: {$usage}%"
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Memory usage check failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check disk usage and generate alerts
     * 
     * @param array $diskMetrics The disk metrics to check
     * @return array Array of generated alerts
     */
    protected function checkDiskUsage(array $diskMetrics): array
    {
        try {
            $alerts = [];
            $usage = $diskMetrics['usage'];

            if ($usage >= $this->thresholds['disk']['critical']) {
                $alerts[] = [
                    'type' => 'disk',
                    'level' => 'critical',
                    'message' => "Disk usage is critically high: {$usage}%"
                ];
            } elseif ($usage >= $this->thresholds['disk']['warning']) {
                $alerts[] = [
                    'type' => 'disk',
                    'level' => 'warning',
                    'message' => "Disk usage is high: {$usage}%"
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Disk usage check failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Store alerts in the database
     * 
     * @param string $serviceName The name of the service
     * @param array $alerts The alerts to store
     * @return void
     */
    protected function storeAlerts(string $serviceName, array $alerts): void
    {
        try {
            foreach ($alerts as $alert) {
                HealthAlert::create([
                    'service_name' => $serviceName,
                    'type' => $alert['type'],
                    'level' => $alert['level'],
                    'message' => $alert['message'],
                    'triggered_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store alerts', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all active alerts
     * 
     * @return array Array of active alerts
     */
    public function getActiveAlerts(): array
    {
        try {
            return HealthAlert::where('resolved_at', null)
                ->orderBy('triggered_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get active alerts', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Resolve an alert
     * 
     * @param int $alertId The ID of the alert to resolve
     * @return bool True if resolved successfully
     */
    public function resolveAlert(int $alertId): bool
    {
        try {
            $alert = HealthAlert::find($alertId);
            if (!$alert) {
                return false;
            }

            $alert->update([
                'resolved_at' => now(),
                'status' => 'resolved'
            ]);

            Log::info('Alert resolved', [
                'alert_id' => $alertId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to resolve alert', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send an alert
     * 
     * @param string $title The alert title
     * @param string $message The alert message
     * @param string $level The alert level (info, warning, error, critical)
     * @return bool True if alert was sent successfully
     */
    public function sendAlert(string $title, string $message, string $level = 'info'): bool
    {
        try {
            $alert = HealthAlert::create([
                'title' => $title,
                'message' => $message,
                'level' => $level,
                'status' => 'active',
                'created_at' => now()
            ]);

            Log::info('Alert sent', [
                'alert_id' => $alert->id,
                'title' => $title,
                'level' => $level
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send alert', [
                'title' => $title,
                'message' => $message,
                'level' => $level,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 