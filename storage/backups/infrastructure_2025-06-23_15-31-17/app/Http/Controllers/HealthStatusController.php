<?php

namespace App\Http\Controllers;

use App\Services\HealthMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthStatusController extends Controller
{
    protected $healthMonitoringService;

    public function __construct(HealthMonitoringService $healthMonitoringService)
    {
        $this->healthMonitoringService = $healthMonitoringService;
    }

    public function getSystemHealth(): JsonResponse
    {
        $health = $this->healthMonitoringService->getSystemHealth();
        return response()->json($health);
    }

    public function getServiceStatus(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = $this->healthMonitoringService->getServiceStatus($request->service_name);
        return response()->json($status);
    }

    public function getActiveAlerts(): JsonResponse
    {
        $alerts = $this->healthMonitoringService->getActiveAlerts();
        return response()->json($alerts);
    }

    public function getServiceMetrics(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metrics = $this->healthMonitoringService->getServiceMetrics(
            $request->service_name,
            $request->from,
            $request->to
        );

        return response()->json($metrics);
    }

    public function getServiceHistory(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $history = $this->healthMonitoringService->getServiceHistory(
            $request->service_name,
            $request->from,
            $request->to
        );

        return response()->json($history);
    }

    public function getServiceTrends(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'metric' => 'required|string|in:cpu,memory,disk,network,process',
            'period' => 'required|string|in:hour,day,week,month'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $trends = $this->healthMonitoringService->getServiceTrends(
            $request->service_name,
            $request->metric,
            $request->period
        );

        return response()->json($trends);
    }

    public function getServiceSummary(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'period' => 'required|string|in:day,week,month'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $summary = $this->healthMonitoringService->getServiceSummary(
            $request->service_name,
            $request->period
        );

        return response()->json($summary);
    }
} 