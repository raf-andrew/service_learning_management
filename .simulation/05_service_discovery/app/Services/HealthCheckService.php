<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use App\Models\HealthCheck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    /**
     * Perform a health check on a service instance
     *
     * @param ServiceInstance $instance
     * @return HealthCheck
     */
    public function checkInstance(ServiceInstance $instance): HealthCheck
    {
        $startTime = microtime(true);
        $status = 'healthy';
        $error = null;

        try {
            $response = Http::timeout(5)
                ->get($instance->health_check_url);

            if (!$response->successful()) {
                $status = 'unhealthy';
                $error = "HTTP {$response->status()}: {$response->body()}";
            }
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $error = $e->getMessage();
            Log::error('Health check failed', [
                'instance' => $instance->id,
                'error' => $error,
            ]);
        }

        $responseTime = (microtime(true) - $startTime) * 1000;

        $healthCheck = HealthCheck::create([
            'service_instance_id' => $instance->id,
            'status' => $status,
            'response_time' => $responseTime,
            'error_message' => $error,
        ]);

        $instance->update([
            'status' => $status,
            'last_health_check' => now(),
            'response_time' => $responseTime,
        ]);

        return $healthCheck;
    }

    /**
     * Perform health checks on all instances of a service
     *
     * @param Service $service
     * @return Collection
     */
    public function checkService(Service $service): Collection
    {
        return $service->instances->map(function ($instance) {
            return $this->checkInstance($instance);
        });
    }

    /**
     * Get health check history for a service instance
     *
     * @param ServiceInstance $instance
     * @param int $limit
     * @return Collection
     */
    public function getInstanceHealthHistory(ServiceInstance $instance, int $limit = 10): Collection
    {
        return HealthCheck::where('service_instance_id', $instance->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get service health status
     *
     * @param Service $service
     * @return array
     */
    public function getServiceHealth(Service $service): array
    {
        $instances = $service->instances;
        $healthyCount = $instances->where('status', 'healthy')->count();

        return [
            'total_instances' => $instances->count(),
            'healthy_instances' => $healthyCount,
            'unhealthy_instances' => $instances->count() - $healthyCount,
            'health_percentage' => $instances->count() > 0 
                ? ($healthyCount / $instances->count()) * 100 
                : 0,
            'average_response_time' => $instances->avg('response_time'),
            'last_check' => $instances->max('last_health_check'),
        ];
    }

    /**
     * Schedule health checks for all services
     *
     * @return void
     */
    public function scheduleHealthChecks(): void
    {
        Service::with('instances')->get()->each(function ($service) {
            $service->instances->each(function ($instance) {
                if ($this->shouldCheckInstance($instance)) {
                    $this->checkInstance($instance);
                }
            });
        });
    }

    /**
     * Determine if an instance should be checked
     *
     * @param ServiceInstance $instance
     * @return bool
     */
    private function shouldCheckInstance(ServiceInstance $instance): bool
    {
        if (!$instance->last_health_check) {
            return true;
        }

        $checkInterval = $instance->service->health_check_interval ?? 60;
        return $instance->last_health_check->addSeconds($checkInterval)->isPast();
    }

    /**
     * Get health check statistics
     *
     * @param Service $service
     * @param string $timeRange
     * @return array
     */
    public function getHealthStatistics(Service $service, string $timeRange = '1h'): array
    {
        $startTime = now()->sub($timeRange);
        
        $checks = HealthCheck::whereHas('instance', function ($query) use ($service) {
            $query->where('service_id', $service->id);
        })
        ->where('created_at', '>=', $startTime)
        ->get();

        return [
            'total_checks' => $checks->count(),
            'healthy_checks' => $checks->where('status', 'healthy')->count(),
            'unhealthy_checks' => $checks->where('status', 'unhealthy')->count(),
            'average_response_time' => $checks->avg('response_time'),
            'error_distribution' => $checks->where('status', 'unhealthy')
                ->groupBy('error_message')
                ->map->count()
                ->toArray(),
        ];
    }
} 