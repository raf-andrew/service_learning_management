<?php

namespace App\Http\Controllers;

use App\Models\HealthMetric;
use App\Services\MetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthMetricsController extends Controller
{
    protected $metricService;

    public function __construct(MetricService $metricService)
    {
        $this->metricService = $metricService;
    }

    public function index(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'metric_type' => 'nullable|string|in:cpu,memory,disk,network,process'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HealthMetric::query();

        if ($request->has('service_name')) {
            $query->where('service_name', $request->service_name);
        }

        if ($request->has('from')) {
            $query->where('collected_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('collected_at', '<=', $request->to);
        }

        if ($request->has('metric_type')) {
            $query->whereJsonContains('metrics->' . $request->metric_type, true);
        }

        $metrics = $query->latest()->paginate(20);
        return response()->json($metrics);
    }

    public function show(HealthMetric $healthMetric): JsonResponse
    {
        return response()->json($healthMetric);
    }

    public function getLatestMetrics(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metrics = HealthMetric::where('service_name', $request->service_name)
            ->latest()
            ->first();

        return response()->json($metrics);
    }

    public function getMetricHistory(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'metric_type' => 'required|string|in:cpu,memory,disk,network,process',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HealthMetric::where('service_name', $request->service_name);

        if ($request->has('from')) {
            $query->where('collected_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('collected_at', '<=', $request->to);
        }

        $history = $query->select(['collected_at', 'metrics->' . $request->metric_type . ' as value'])
            ->orderBy('collected_at')
            ->get();

        return response()->json($history);
    }

    public function getMetricStats(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'metric_type' => 'required|string|in:cpu,memory,disk,network,process',
            'period' => 'required|string|in:hour,day,week,month'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stats = $this->metricService->getMetricStats(
            $request->service_name,
            $request->metric_type,
            $request->period
        );

        return response()->json($stats);
    }

    public function getMetricThresholds(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'metric_type' => 'required|string|in:cpu,memory,disk,network,process'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $thresholds = $this->metricService->getMetricThresholds(
            $request->service_name,
            $request->metric_type
        );

        return response()->json($thresholds);
    }

    public function updateMetricThresholds(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'service_name' => 'required|string',
            'metric_type' => 'required|string|in:cpu,memory,disk,network,process',
            'warning_threshold' => 'required|numeric|min:0|max:100',
            'critical_threshold' => 'required|numeric|min:0|max:100|gt:warning_threshold'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $thresholds = $this->metricService->updateMetricThresholds(
            $request->service_name,
            $request->metric_type,
            $request->warning_threshold,
            $request->critical_threshold
        );

        return response()->json($thresholds);
    }
} 