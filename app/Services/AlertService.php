<?php

namespace App\Services;

use App\Models\HealthAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AlertService
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

    protected function checkCpuUsage(array $cpuMetrics): array
    {
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
    }

    protected function checkMemoryUsage(array $memoryMetrics): array
    {
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
    }

    protected function checkDiskUsage(array $diskMetrics): array
    {
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
    }

    protected function storeAlerts(string $serviceName, array $alerts): void
    {
        foreach ($alerts as $alert) {
            HealthAlert::create([
                'service_name' => $serviceName,
                'type' => $alert['type'],
                'level' => $alert['level'],
                'message' => $alert['message'],
                'triggered_at' => now()
            ]);
        }
    }

    public function getActiveAlerts(): array
    {
        return HealthAlert::where('resolved_at', null)
            ->orderBy('triggered_at', 'desc')
            ->get()
            ->toArray();
    }

    public function resolveAlert(int $alertId): bool
    {
        try {
            $alert = HealthAlert::findOrFail($alertId);
            $alert->resolved_at = now();
            $alert->save();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to resolve alert', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 