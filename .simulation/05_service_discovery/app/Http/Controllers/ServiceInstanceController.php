<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ServiceInstanceController extends Controller
{
    /**
     * Register a new service instance.
     */
    public function register(Request $request, string $serviceName): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'status' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $instance = ServiceInstance::create([
                'service_id' => $service->id,
                'host' => $request->host,
                'port' => $request->port,
                'status' => $request->status ?? 'unknown',
                'metadata' => $request->metadata,
            ]);

            return response()->json($instance, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Instance already exists'], 409);
        }
    }

    /**
     * Get service instances.
     */
    public function index(string $serviceName): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $instances = $service->instances()->get();

        return response()->json($instances);
    }

    /**
     * Update a service instance.
     */
    public function update(Request $request, string $serviceName, int $instanceId): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $instance = $service->instances()->find($instanceId);

        if (!$instance) {
            return response()->json(['message' => 'Instance not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $instance->update($request->all());

        return response()->json($instance);
    }

    /**
     * Delete a service instance.
     */
    public function destroy(string $serviceName, int $instanceId): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $instance = $service->instances()->find($instanceId);

        if (!$instance) {
            return response()->json(['message' => 'Instance not found'], 404);
        }

        $instance->delete();

        return response()->json(null, 204);
    }

    /**
     * Update instance heartbeat.
     */
    public function heartbeat(string $serviceName, int $instanceId): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $instance = $service->instances()->find($instanceId);

        if (!$instance) {
            return response()->json(['message' => 'Instance not found'], 404);
        }

        $instance->updateHeartbeat();

        return response()->json($instance);
    }
} 