<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\MetricType;
use App\Models\MetricAggregation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MetricProcessingService
{
    private MetricCollectionService $collectionService;

    public function __construct(MetricCollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public function processMetrics(): Collection
    {
        $metrics = $this->collectionService->collectMetrics();
        $processedMetrics = collect();

        foreach ($metrics as $metric) {
            try {
                $processedMetric = $this->processMetric($metric);
                if ($processedMetric) {
                    $processedMetrics->push($processedMetric);
                }
            } catch (\Exception $e) {
                Log::error("Failed to process metric {$metric->id}: " . $e->getMessage());
            }
        }

        return $processedMetrics;
    }

    private function processMetric(Metric $metric): ?Metric
    {
        $type = $metric->metricType;
        if (!$type) {
            return null;
        }

        // Apply any transformations
        $value = $this->transformValue($metric->value, $type);

        // Check for anomalies
        if ($this->isAnomaly($value, $type)) {
            $this->handleAnomaly($metric, $value);
        }

        // Update metric with processed value
        $metric->value = $value;
        $metric->save();

        return $metric;
    }

    private function transformValue($value, MetricType $type): float
    {
        // Apply any type-specific transformations
        return match ($type->data_type) {
            'float' => (float) $value,
            'integer' => (int) $value,
            'boolean' => (bool) $value ? 1.0 : 0.0,
            default => (float) $value,
        };
    }

    private function isAnomaly($value, MetricType $type): bool
    {
        $cacheKey = "metric_stats:{$type->id}";
        $stats = Cache::remember($cacheKey, 3600, function () use ($type) {
            return $this->calculateStats($type);
        });

        if (!$stats) {
            return false;
        }

        $mean = $stats['mean'];
        $stdDev = $stats['std_dev'];
        $threshold = 3; // Number of standard deviations

        return abs($value - $mean) > ($threshold * $stdDev);
    }

    private function calculateStats(MetricType $type): ?array
    {
        $metrics = Metric::where('metric_type_id', $type->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($metrics->isEmpty()) {
            return null;
        }

        $values = $metrics->pluck('value')->toArray();
        $mean = array_sum($values) / count($values);
        
        $variance = array_reduce($values, function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / count($values);
        
        $stdDev = sqrt($variance);

        return [
            'mean' => $mean,
            'std_dev' => $stdDev,
            'min' => min($values),
            'max' => max($values),
            'count' => count($values)
        ];
    }

    private function handleAnomaly(Metric $metric, $value): void
    {
        Log::warning("Anomaly detected for metric {$metric->id}", [
            'metric_type' => $metric->metricType->name,
            'value' => $value,
            'timestamp' => $metric->timestamp
        ]);

        // TODO: Implement alert generation
    }

    public function analyzeMetrics(MetricType $type, array $filters = []): array
    {
        $query = Metric::where('metric_type_id', $type->id);

        foreach ($filters as $field => $value) {
            $query->where("labels->{$field}", $value);
        }

        $metrics = $query->get();
        
        return [
            'count' => $metrics->count(),
            'min' => $metrics->min('value'),
            'max' => $metrics->max('value'),
            'avg' => $metrics->avg('value'),
            'latest' => $metrics->sortByDesc('timestamp')->first(),
            'trend' => $this->calculateTrend($metrics)
        ];
    }

    private function calculateTrend(Collection $metrics): array
    {
        if ($metrics->isEmpty()) {
            return ['direction' => 'stable', 'change' => 0];
        }

        $sorted = $metrics->sortBy('timestamp');
        $first = $sorted->first();
        $last = $sorted->last();
        
        $change = $last->value - $first->value;
        $percentChange = $first->value != 0 ? ($change / $first->value) * 100 : 0;

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'change' => $percentChange
        ];
    }
} 