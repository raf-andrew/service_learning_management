<?php

namespace App\Services;

use App\Models\MetricType;
use App\Models\Metric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MetricCollectionService
{
    private array $collectors = [];

    public function registerCollector(string $name, callable $collector): void
    {
        $this->collectors[$name] = $collector;
    }

    public function collectMetrics(): Collection
    {
        $metrics = collect();

        foreach ($this->collectors as $name => $collector) {
            try {
                $value = $collector();
                $metricType = $this->getMetricType($name);
                
                if ($metricType && $metricType->validateValue($value)) {
                    $metrics->push($this->createMetric($metricType, $value));
                } else {
                    Log::warning("Invalid metric value for {$name}: {$value}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to collect metric {$name}: " . $e->getMessage());
            }
        }

        return $metrics;
    }

    private function getMetricType(string $name): ?MetricType
    {
        return MetricType::where('name', $name)->first();
    }

    private function createMetric(MetricType $type, $value): Metric
    {
        return Metric::create([
            'metric_type_id' => $type->id,
            'value' => $value,
            'timestamp' => now(),
            'labels' => [
                'collector' => $type->name,
                'host' => gethostname(),
                'environment' => config('app.env')
            ]
        ]);
    }

    public function collectSystemMetrics(): Collection
    {
        $this->registerCollector('cpu_usage', function() {
            return $this->getCpuUsage();
        });

        $this->registerCollector('memory_usage', function() {
            return $this->getMemoryUsage();
        });

        $this->registerCollector('disk_usage', function() {
            return $this->getDiskUsage();
        });

        return $this->collectMetrics();
    }

    private function getCpuUsage(): float
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'wmic cpu get loadpercentage';
            exec($cmd, $output);
            return (float) $output[1];
        }

        $load = sys_getloadavg();
        return $load[0] * 100;
    }

    private function getMemoryUsage(): float
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value';
            exec($cmd, $output);
            $total = 0;
            $free = 0;
            foreach ($output as $line) {
                if (strpos($line, 'TotalVisibleMemorySize') !== false) {
                    $total = (int) explode('=', $line)[1];
                }
                if (strpos($line, 'FreePhysicalMemory') !== false) {
                    $free = (int) explode('=', $line)[1];
                }
            }
            return $total > 0 ? (($total - $free) / $total) * 100 : 0;
        }

        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        return $mem[2] / $mem[1] * 100;
    }

    private function getDiskUsage(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        return $total > 0 ? (($total - $free) / $total) * 100 : 0;
    }
} 