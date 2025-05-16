<?php

namespace App\Services;

use App\Models\ServiceHealth;
use App\Models\Metric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MetricService
{
    public function collectMetrics(ServiceHealth $service): Collection
    {
        try {
            // Collect system metrics
            $metrics = collect([
                [
                    'name' => 'cpu_usage',
                    'value' => $this->getCpuUsage(),
                    'unit' => 'percent',
                    'threshold' => 80.0
                ],
                [
                    'name' => 'memory_usage',
                    'value' => $this->getMemoryUsage(),
                    'unit' => 'percent',
                    'threshold' => 85.0
                ],
                [
                    'name' => 'disk_usage',
                    'value' => $this->getDiskUsage(),
                    'unit' => 'percent',
                    'threshold' => 90.0
                ],
                [
                    'name' => 'response_time',
                    'value' => $this->getResponseTime($service),
                    'unit' => 'milliseconds',
                    'threshold' => 1000.0
                ]
            ]);

            return $metrics;
        } catch (\Exception $e) {
            Log::error("Failed to collect metrics for service {$service->service_name}: " . $e->getMessage());
            throw $e;
        }
    }

    public function storeMetrics(array $metrics): void
    {
        try {
            foreach ($metrics as $metric) {
                Metric::create([
                    'service_health_id' => $metric['service_health_id'],
                    'name' => $metric['name'],
                    'value' => $metric['value'],
                    'unit' => $metric['unit'],
                    'threshold' => $metric['threshold'],
                    'timestamp' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to store metrics: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStoredMetrics(): Collection
    {
        return Metric::with('serviceHealth')
            ->orderBy('timestamp', 'desc')
            ->limit(100)
            ->get();
    }

    private function getCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] * 100;
        }
        return 0.0;
    }

    private function getMemoryUsage(): float
    {
        if (function_exists('memory_get_usage')) {
            $totalMemory = memory_get_usage(true);
            $freeMemory = memory_get_peak_usage(true) - $totalMemory;
            return ($totalMemory / ($totalMemory + $freeMemory)) * 100;
        }
        return 0.0;
    }

    private function getDiskUsage(): float
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        return (($totalSpace - $freeSpace) / $totalSpace) * 100;
    }

    private function getResponseTime(ServiceHealth $service): float
    {
        return $service->response_time * 1000; // Convert to milliseconds
    }
} 