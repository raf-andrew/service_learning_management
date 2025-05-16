<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\HealthCheck;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class HealthCheckController extends Controller
{
    /**
     * Record a health check result.
     */
    public function record(Request $request, string $serviceName): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:success,failed',
            'response_time' => 'required|numeric|min:0',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => $request->status,
            'response_time' => $request->response_time,
            'error_message' => $request->error_message,
            'check_time' => now(),
        ]);

        $service->update(['last_health_check' => now()]);

        return response()->json($healthCheck, 201);
    }

    /**
     * Get health check history for a service.
     */
    public function history(string $serviceName, Request $request): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $query = $service->healthChecks();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->where('check_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('check_time', '<=', $request->end_date);
        }

        $healthChecks = $query->orderBy('check_time', 'desc')
            ->limit($request->get('limit', 100))
            ->get();

        return response()->json($healthChecks);
    }

    /**
     * Get the latest health check for a service.
     */
    public function latest(string $serviceName): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $healthCheck = $service->healthChecks()
            ->latest('check_time')
            ->first();

        if (!$healthCheck) {
            return response()->json(['message' => 'No health checks found'], 404);
        }

        return response()->json($healthCheck);
    }

    /**
     * Get health check statistics for a service.
     */
    public function statistics(string $serviceName): JsonResponse
    {
        $service = Service::where('name', $serviceName)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $stats = [
            'total_checks' => $service->healthChecks()->count(),
            'successful_checks' => $service->healthChecks()->where('status', 'success')->count(),
            'failed_checks' => $service->healthChecks()->where('status', 'failed')->count(),
            'average_response_time' => $service->healthChecks()->avg('response_time'),
            'last_check' => $service->last_health_check,
            'current_status' => $service->isHealthy() ? 'healthy' : 'unhealthy',
        ];

        return response()->json($stats);
    }
} 