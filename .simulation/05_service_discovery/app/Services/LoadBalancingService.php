<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LoadBalancingService
{
    protected $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Get the next available instance for a service
     *
     * @param Service $service
     * @param string $strategy
     * @return ServiceInstance|null
     */
    public function getNextInstance(Service $service, string $strategy = 'round-robin'): ?ServiceInstance
    {
        $instances = $this->getHealthyInstances($service);

        if ($instances->isEmpty()) {
            return null;
        }

        return match ($strategy) {
            'round-robin' => $this->getRoundRobinInstance($service, $instances),
            'least-connections' => $this->getLeastConnectionsInstance($instances),
            'response-time' => $this->getBestResponseTimeInstance($instances),
            default => $this->getRoundRobinInstance($service, $instances),
        };
    }

    /**
     * Get healthy instances for a service
     *
     * @param Service $service
     * @return Collection
     */
    protected function getHealthyInstances(Service $service): Collection
    {
        return $service->instances()
            ->where('status', 'healthy')
            ->get();
    }

    /**
     * Get next instance using round-robin strategy
     *
     * @param Service $service
     * @param Collection $instances
     * @return ServiceInstance
     */
    protected function getRoundRobinInstance(Service $service, Collection $instances): ServiceInstance
    {
        $cacheKey = "service:{$service->id}:last_instance";
        $lastInstanceId = Cache::get($cacheKey);

        $nextInstance = $instances->first(function ($instance) use ($lastInstanceId) {
            return $instance->id > $lastInstanceId;
        });

        if (!$nextInstance) {
            $nextInstance = $instances->first();
        }

        Cache::put($cacheKey, $nextInstance->id, 60);

        return $nextInstance;
    }

    /**
     * Get instance with least connections
     *
     * @param Collection $instances
     * @return ServiceInstance
     */
    protected function getLeastConnectionsInstance(Collection $instances): ServiceInstance
    {
        return $instances->sortBy('current_connections')->first();
    }

    /**
     * Get instance with best response time
     *
     * @param Collection $instances
     * @return ServiceInstance
     */
    protected function getBestResponseTimeInstance(Collection $instances): ServiceInstance
    {
        return $instances->sortBy('response_time')->first();
    }

    /**
     * Update instance connection count
     *
     * @param ServiceInstance $instance
     * @param int $count
     * @return void
     */
    public function updateConnectionCount(ServiceInstance $instance, int $count): void
    {
        $instance->update(['current_connections' => $count]);
    }

    /**
     * Get load balancing statistics
     *
     * @param Service $service
     * @return array
     */
    public function getLoadBalancingStats(Service $service): array
    {
        $instances = $service->instances;

        return [
            'total_instances' => $instances->count(),
            'healthy_instances' => $instances->where('status', 'healthy')->count(),
            'total_connections' => $instances->sum('current_connections'),
            'average_response_time' => $instances->avg('response_time'),
            'instance_stats' => $instances->map(function ($instance) {
                return [
                    'id' => $instance->id,
                    'status' => $instance->status,
                    'connections' => $instance->current_connections,
                    'response_time' => $instance->response_time,
                ];
            })->toArray(),
        ];
    }
} 