<?php

namespace App\Http\Controllers;

use App\Models\ServiceHealth;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    private $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    public function check(): JsonResponse
    {
        $healthStatus = $this->healthCheckService->checkAllServices();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'overall_status' => $healthStatus['overall_status'],
                'services' => $healthStatus['services'],
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }

    public function serviceStatus(string $serviceName): JsonResponse
    {
        $service = ServiceHealth::where('service_name', $serviceName)->firstOrFail();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'service_name' => $service->service_name,
                'status' => $service->status,
                'last_check' => $service->last_check,
                'response_time' => $service->response_time,
                'error_count' => $service->error_count,
                'warning_count' => $service->warning_count,
                'health_status' => $service->getHealthStatus()
            ]
        ]);
    }

    public function metrics(string $serviceName): JsonResponse
    {
        $service = ServiceHealth::where('service_name', $serviceName)->firstOrFail();
        $metrics = $service->metrics()
            ->orderBy('timestamp', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'service_name' => $service->service_name,
                'metrics' => $metrics
            ]
        ]);
    }

    public function alerts(string $serviceName): JsonResponse
    {
        $service = ServiceHealth::where('service_name', $serviceName)->firstOrFail();
        $alerts = $service->alerts()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'service_name' => $service->service_name,
                'alerts' => $alerts
            ]
        ]);
    }

    public function acknowledgeAlert(Request $request, int $alertId): JsonResponse
    {
        $request->validate([
            'acknowledged_by' => 'required|string'
        ]);

        $alert = Alert::findOrFail($alertId);
        $alert->acknowledge($request->acknowledged_by);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert acknowledged successfully',
            'data' => $alert
        ]);
    }

    public function resolveAlert(Request $request, int $alertId): JsonResponse
    {
        $request->validate([
            'resolved_by' => 'required|string'
        ]);

        $alert = Alert::findOrFail($alertId);
        $alert->resolve($request->resolved_by);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert resolved successfully',
            'data' => $alert
        ]);
    }
} 