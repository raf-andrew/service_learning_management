<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceRegistrationService
{
    /**
     * Register a new service.
     */
    public function registerService(array $data): Service
    {
        return Service::create($data);
    }

    /**
     * Register a new service instance.
     */
    public function registerInstance(string $serviceName, array $data): ServiceInstance
    {
        $service = Service::where('name', $serviceName)->firstOrFail();

        try {
            return ServiceInstance::create([
                'service_id' => $service->id,
                'host' => $data['host'],
                'port' => $data['port'],
                'status' => $data['status'] ?? 'unknown',
                'metadata' => $data['metadata'] ?? null,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Instance already exists');
        }
    }

    /**
     * Update a service instance.
     */
    public function updateInstance(string $serviceName, int $instanceId, array $data): ServiceInstance
    {
        $service = Service::where('name', $serviceName)->firstOrFail();
        $instance = $service->instances()->findOrFail($instanceId);

        $instance->update($data);

        return $instance;
    }

    /**
     * Delete a service instance.
     */
    public function deleteInstance(string $serviceName, int $instanceId): void
    {
        $service = Service::where('name', $serviceName)->firstOrFail();
        $instance = $service->instances()->findOrFail($instanceId);

        $instance->delete();
    }

    /**
     * Update instance heartbeat.
     */
    public function updateHeartbeat(string $serviceName, int $instanceId): ServiceInstance
    {
        $service = Service::where('name', $serviceName)->firstOrFail();
        $instance = $service->instances()->findOrFail($instanceId);

        $instance->updateHeartbeat();

        return $instance;
    }

    /**
     * Get service instances.
     */
    public function getInstances(string $serviceName): Collection
    {
        $service = Service::where('name', $serviceName)->firstOrFail();
        return $service->instances;
    }

    /**
     * Get service by name.
     */
    public function getService(string $name): Service
    {
        return Service::where('name', $name)->firstOrFail();
    }

    /**
     * List services with optional filters.
     */
    public function listServices(?string $tag = null, ?string $status = null): Collection
    {
        $query = Service::query();

        if ($tag) {
            $query->whereJsonContains('tags', $tag);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
} 