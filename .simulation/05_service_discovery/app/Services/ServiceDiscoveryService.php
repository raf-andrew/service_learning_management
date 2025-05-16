<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class ServiceDiscoveryService
{
    protected $cache;
    protected $loadBalancer;

    public function __construct(LoadBalancingService $loadBalancer)
    {
        $this->loadBalancer = $loadBalancer;
    }

    /**
     * Find a service by name
     *
     * @param string $name
     * @return Service|null
     */
    public function findService(string $name): ?Service
    {
        return Cache::remember("service:{$name}", 300, function () use ($name) {
            return Service::where('name', $name)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * Find services by tags
     *
     * @param array $tags
     * @return Collection
     */
    public function findServicesByTags(array $tags): Collection
    {
        $cacheKey = 'services:tags:' . md5(json_encode($tags));
        
        return Cache::remember($cacheKey, 300, function () use ($tags) {
            return Service::where('status', 'active')
                ->whereJsonContains('tags', $tags)
                ->get();
        });
    }

    /**
     * Find services by metadata
     *
     * @param array $metadata
     * @return Collection
     */
    public function findServicesByMetadata(array $metadata): Collection
    {
        $cacheKey = 'services:metadata:' . md5(json_encode($metadata));
        
        return Cache::remember($cacheKey, 300, function () use ($metadata) {
            $query = Service::where('status', 'active');
            
            foreach ($metadata as $key => $value) {
                $query->whereJsonContains("metadata->{$key}", $value);
            }
            
            return $query->get();
        });
    }

    /**
     * Get available instances for a service
     *
     * @param string $serviceName
     * @return Collection
     */
    public function getServiceInstances(string $serviceName): Collection
    {
        $cacheKey = "service:{$serviceName}:instances";
        
        return Cache::remember($cacheKey, 60, function () use ($serviceName) {
            return ServiceInstance::whereHas('service', function (Builder $query) use ($serviceName) {
                $query->where('name', $serviceName)
                    ->where('status', 'active');
            })
            ->where('status', 'healthy')
            ->get();
        });
    }

    /**
     * Get the next available instance for a service using load balancing
     *
     * @param string $serviceName
     * @return ServiceInstance|null
     */
    public function getNextInstance(string $serviceName): ?ServiceInstance
    {
        $instances = $this->getServiceInstances($serviceName);
        
        if ($instances->isEmpty()) {
            return null;
        }

        return $this->loadBalancer->selectInstance($instances);
    }

    /**
     * Get service dependencies
     *
     * @param string $serviceName
     * @return Collection
     */
    public function getServiceDependencies(string $serviceName): Collection
    {
        $cacheKey = "service:{$serviceName}:dependencies";
        
        return Cache::remember($cacheKey, 300, function () use ($serviceName) {
            $service = $this->findService($serviceName);
            
            if (!$service) {
                return collect();
            }

            return Service::whereIn('name', $service->dependencies)
                ->where('status', 'active')
                ->get();
        });
    }

    /**
     * Check if a service is available
     *
     * @param string $serviceName
     * @return bool
     */
    public function isServiceAvailable(string $serviceName): bool
    {
        return $this->getServiceInstances($serviceName)->isNotEmpty();
    }

    /**
     * Get service health status
     *
     * @param string $serviceName
     * @return array
     */
    public function getServiceHealth(string $serviceName): array
    {
        $instances = $this->getServiceInstances($serviceName);
        
        return [
            'available' => $instances->isNotEmpty(),
            'instance_count' => $instances->count(),
            'healthy_instances' => $instances->where('status', 'healthy')->count(),
            'last_health_check' => $instances->max('last_health_check'),
            'average_response_time' => $instances->avg('response_time')
        ];
    }

    /**
     * Clear service discovery cache
     *
     * @param string|null $serviceName
     * @return void
     */
    public function clearCache(?string $serviceName = null): void
    {
        if ($serviceName) {
            Cache::forget("service:{$serviceName}");
            Cache::forget("service:{$serviceName}:instances");
            Cache::forget("service:{$serviceName}:dependencies");
        } else {
            Cache::tags(['services'])->flush();
        }
    }
} 