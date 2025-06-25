<?php

namespace App\Http\Controllers;

use App\Models\HealthMetric;
use App\Services\MetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthMetricsController extends BaseApiController
{
    protected $metricService;

    public function __construct(MetricService $metricService)
    {
        $this->metricService = $metricService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('health_metrics:index');
            
            $rules = [
                'service_name' => 'nullable|string',
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
                'metric_type' => 'nullable|string|in:cpu,memory,disk,network,process'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $query = HealthMetric::query();

            if (!empty($validatedData['service_name'])) {
                $query->where('service_name', $validatedData['service_name']);
            }

            if (!empty($validatedData['from'])) {
                $query->where('collected_at', '>=', $validatedData['from']);
            }

            if (!empty($validatedData['to'])) {
                $query->where('collected_at', '<=', $validatedData['to']);
            }

            if (!empty($validatedData['metric_type'])) {
                $query->whereJsonContains('metrics->' . $validatedData['metric_type'], true);
            }

            $metrics = $this->paginateWithOptimization($query, $request);
            
            return $this->paginatedResponse($metrics, 'Health metrics retrieved successfully');
        }, 'HealthMetricsController::index');
    }

    public function show(HealthMetric $healthMetric): JsonResponse
    {
        return $this->executeDbOperation(function () use ($healthMetric) {
            $this->applyRateLimit('health_metrics:show');
            
            return $this->successResponse($healthMetric, 'Health metric retrieved successfully');
        }, 'HealthMetricsController::show');
    }

    public function getLatestMetrics(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('health_metrics:latest');
            
            $rules = ['service_name' => 'required|string'];
            
            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $cacheKey = $this->getCacheKey("latest_metrics:{$validatedData['service_name']}");
            
            $metrics = $this->cachedQuery(
                HealthMetric::where('service_name', $validatedData['service_name'])->latest(),
                $cacheKey,
                300 // 5 minutes cache
            );

            return $this->successResponse($metrics->first(), 'Latest metrics retrieved successfully');
        }, 'HealthMetricsController::getLatestMetrics');
    }

    public function getMetricHistory(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('health_metrics:history');
            
            $rules = [
                'service_name' => 'required|string',
                'metric_type' => 'required|string|in:cpu,memory,disk,network,process',
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $query = HealthMetric::where('service_name', $validatedData['service_name']);

            if (!empty($validatedData['from'])) {
                $query->where('collected_at', '>=', $validatedData['from']);
            }

            if (!empty($validatedData['to'])) {
                $query->where('collected_at', '<=', $validatedData['to']);
            }

            $history = $query->select(['collected_at', 'metrics->' . $validatedData['metric_type'] . ' as value'])
                ->orderBy('collected_at')
                ->get();

            return $this->successResponse($history, 'Metric history retrieved successfully');
        }, 'HealthMetricsController::getMetricHistory');
    }

    public function getMetricStats(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('health_metrics:stats');
            
            $rules = [
                'service_name' => 'required|string',
                'metric_type' => 'required|string|in:cpu,memory,disk,network,process',
                'period' => 'required|string|in:hour,day,week,month'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $cacheKey = $this->getCacheKey("metric_stats:{$validatedData['service_name']}:{$validatedData['metric_type']}:{$validatedData['period']}");
            
            $stats = $this->cachedQuery(
                HealthMetric::where('service_name', $validatedData['service_name']),
                $cacheKey,
                600 // 10 minutes cache
            );

            $processedStats = $this->metricService->getMetricStats(
                $validatedData['service_name'],
                $validatedData['metric_type'],
                $validatedData['period']
            );

            return $this->successResponse($processedStats, 'Metric statistics retrieved successfully');
        }, 'HealthMetricsController::getMetricStats');
    }

    public function getMetricThresholds(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('health_metrics:thresholds');
            
            $rules = [
                'service_name' => 'required|string',
                'metric_type' => 'required|string|in:cpu,memory,disk,network,process'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $thresholds = $this->metricService->getMetricThresholds(
                $validatedData['service_name'],
                $validatedData['metric_type']
            );

            return $this->successResponse($thresholds, 'Metric thresholds retrieved successfully');
        }, 'HealthMetricsController::getMetricThresholds');
    }

    public function updateMetricThresholds(Request $request): JsonResponse
    {
        return $this->executeDbOperation(function () use ($request) {
            $this->applyRateLimit('health_metrics:update_thresholds');
            
            $rules = [
                'service_name' => 'required|string',
                'metric_type' => 'required|string|in:cpu,memory,disk,network,process',
                'warning_threshold' => 'required|numeric|min:0|max:100',
                'critical_threshold' => 'required|numeric|min:0|max:100|gt:warning_threshold'
            ];

            $validatedData = $this->validateAndGetData($request, $rules);
            
            if ($validatedData instanceof JsonResponse) {
                return $validatedData;
            }

            $thresholds = $this->metricService->updateMetricThresholds(
                $validatedData['service_name'],
                $validatedData['metric_type'],
                $validatedData['warning_threshold'],
                $validatedData['critical_threshold']
            );

            // Clear related cache
            $cacheKey = $this->getCacheKey("metric_thresholds:{$validatedData['service_name']}:{$validatedData['metric_type']}");
            cache()->forget($cacheKey);

            return $this->successResponse($thresholds, 'Metric thresholds updated successfully');
        }, 'HealthMetricsController::updateMetricThresholds');
    }

    /**
     * Get searchable fields for health metrics
     */
    protected function getSearchableFields(): array
    {
        return ['service_name', 'metric_type'];
    }

    /**
     * Get sortable fields for health metrics
     */
    protected function getSortableFields(): array
    {
        return ['created_at', 'updated_at', 'collected_at', 'service_name'];
    }
} 