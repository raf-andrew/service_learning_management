<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Register a new service.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:services',
            'version' => 'required|string',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
            'health_check_interval' => 'nullable|integer|min:1',
            'health_check_timeout' => 'nullable|integer|min:1',
            'health_check_retries' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::create($request->all());

        return response()->json($service, 201);
    }

    /**
     * Get a service by name.
     */
    public function show(string $name): JsonResponse
    {
        $service = Service::where('name', $name)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        return response()->json($service);
    }

    /**
     * Update a service.
     */
    public function update(Request $request, string $name): JsonResponse
    {
        $service = Service::where('name', $name)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'version' => 'nullable|string',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
            'health_check_interval' => 'nullable|integer|min:1',
            'health_check_timeout' => 'nullable|integer|min:1',
            'health_check_retries' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($request->all());

        return response()->json($service);
    }

    /**
     * Delete a service.
     */
    public function destroy(string $name): JsonResponse
    {
        $service = Service::where('name', $name)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $service->delete();

        return response()->json(null, 204);
    }

    /**
     * List all services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query();

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->get();

        return response()->json($services);
    }
} 