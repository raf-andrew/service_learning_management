<?php

namespace App\Http\Controllers;

use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Services\HealthMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthHistoryController extends Controller
{
    protected $healthMonitoringService;

    public function __construct(HealthMonitoringService $healthMonitoringService)
    {
        $this->healthMonitoringService = $healthMonitoringService;
    }

    public function getServiceHistory(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => 'nullable|string|in:healthy,unhealthy'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HealthCheckResult::query()
            ->whereHas('healthCheck', function ($query) use ($request) {
                $query->where('name', $request->service_name);
            });

        if ($request->has('from')) {
            $query->where('checked_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('checked_at', '<=', $request->to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $history = $query->with('healthCheck')
            ->orderBy('checked_at', 'desc')
            ->paginate(20);

        return response()->json($history);
    }

    public function getServiceUptime(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'period' => 'required|string|in:day,week,month,year'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uptime = $this->healthMonitoringService->getServiceUptime(
            $request->service_name,
            $request->period
        );

        return response()->json($uptime);
    }

    public function getServiceIncidents(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'severity' => 'nullable|string|in:warning,critical'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incidents = $this->healthMonitoringService->getServiceIncidents(
            $request->service_name,
            $request->from,
            $request->to,
            $request->severity
        );

        return response()->json($incidents);
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

    public function getServiceAvailability(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'period' => 'required|string|in:day,week,month,year'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $availability = $this->healthMonitoringService->getServiceAvailability(
            $request->service_name,
            $request->period
        );

        return response()->json($availability);
    }

    public function getServicePerformance(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'period' => 'required|string|in:day,week,month'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $performance = $this->healthMonitoringService->getServicePerformance(
            $request->service_name,
            $request->period
        );

        return response()->json($performance);
    }
} 