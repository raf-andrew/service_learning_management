<?php

namespace App\Services;

use App\Models\HealthMetric;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MetricService
{
    public function collectMetrics(string $serviceName): array
    {
        try {
            $metrics = [
                'cpu' => $this->getCpuUsage(),
                'memory' => $this->getMemoryUsage(),
                'disk' => $this->getDiskUsage(),
                'network' => $this->getNetworkMetrics(),
                'process' => $this->getProcessMetrics()
            ];

            // Store metrics
            $this->storeMetrics($serviceName, $metrics);

            return $metrics;
        } catch (\Exception $e) {
            Log::error('Metric collection failed', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getCpuUsage(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cpuUsage = shell_exec('wmic cpu get loadpercentage');
            $usage = (int) preg_replace('/[^0-9]/', '', $cpuUsage);
        } else {
            $load = sys_getloadavg();
            $usage = $load[0] * 100;
        }

        return [
            'usage' => $usage,
            'cores' => $this->getCpuCores()
        ];
    }

    protected function getMemoryUsage(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $memory = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
            preg_match('/FreePhysicalMemory=(\d+)/', $memory, $free);
            preg_match('/TotalVisibleMemorySize=(\d+)/', $memory, $total);
            
            $freeMemory = (int) $free[1];
            $totalMemory = (int) $total[1];
            $usedMemory = $totalMemory - $freeMemory;
            $usage = ($usedMemory / $totalMemory) * 100;
        } else {
            $free = shell_exec('free');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", $free_arr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);
            
            $totalMemory = $mem[1];
            $usedMemory = $mem[2];
            $usage = ($usedMemory / $totalMemory) * 100;
        }

        return [
            'total' => $totalMemory,
            'used' => $usedMemory,
            'free' => $freeMemory,
            'usage' => $usage
        ];
    }

    protected function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $usage = ($used / $total) * 100;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage' => $usage
        ];
    }

    protected function getNetworkMetrics(): array
    {
        // This is a simplified version. In production, you'd want to use
        // more sophisticated network monitoring tools
        return [
            'connections' => $this->getActiveConnections(),
            'bandwidth' => $this->getBandwidthUsage()
        ];
    }

    protected function getProcessMetrics(): array
    {
        return [
            'count' => $this->getProcessCount(),
            'memory' => $this->getProcessMemoryUsage()
        ];
    }

    protected function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cores = shell_exec('wmic cpu get NumberOfCores');
            return (int) preg_replace('/[^0-9]/', '', $cores);
        }
        return (int) shell_exec('nproc');
    }

    protected function getActiveConnections(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $connections = shell_exec('netstat -an | find "ESTABLISHED" /c');
        } else {
            $connections = shell_exec('netstat -an | grep ESTABLISHED | wc -l');
        }
        return (int) trim($connections);
    }

    protected function getBandwidthUsage(): array
    {
        // This is a placeholder. In production, you'd want to use
        // proper network monitoring tools
        return [
            'in' => 0,
            'out' => 0
        ];
    }

    protected function getProcessCount(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $count = shell_exec('tasklist /NH | find /C /V ""');
        } else {
            $count = shell_exec('ps aux | wc -l');
        }
        return (int) trim($count);
    }

    protected function getProcessMemoryUsage(): int
    {
        return memory_get_usage(true);
    }

    protected function storeMetrics(string $serviceName, array $metrics): void
    {
        HealthMetric::create([
            'service_name' => $serviceName,
            'metrics' => $metrics,
            'collected_at' => now()
        ]);
    }
} 