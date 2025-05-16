<?php

namespace App\Services;

use App\Models\MetricAggregation;
use App\Models\MetricType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MetricVisualizationService
{
    protected $cache;
    protected $metricCollectionService;
    protected $metricProcessingService;

    public function __construct(
        MetricCollectionService $metricCollectionService,
        MetricProcessingService $metricProcessingService
    ) {
        $this->metricCollectionService = $metricCollectionService;
        $this->metricProcessingService = $metricProcessingService;
    }

    /**
     * Get time series data for a metric
     *
     * @param string $metricName
     * @param string $timeRange
     * @param array $labels
     * @return Collection
     */
    public function getTimeSeriesData(string $metricName, string $timeRange = '1h', array $labels = []): Collection
    {
        $cacheKey = "metric:{$metricName}:timeseries:{$timeRange}:" . md5(json_encode($labels));
        
        return Cache::remember($cacheKey, 300, function () use ($metricName, $timeRange, $labels) {
            $query = MetricAggregation::where('metric_name', $metricName)
                ->where('time_range', $timeRange);
            
            foreach ($labels as $key => $value) {
                $query->whereJsonContains("labels->{$key}", $value);
            }
            
            return $query->orderBy('timestamp')
                ->get()
                ->map(function ($aggregation) {
                    return [
                        'timestamp' => $aggregation->timestamp,
                        'value' => $aggregation->value,
                        'labels' => $aggregation->labels,
                    ];
                });
        });
    }

    /**
     * Get statistics for a metric
     *
     * @param string $metricName
     * @param array $labels
     * @return array
     */
    public function getMetricStatistics(string $metricName, array $labels = []): array
    {
        $cacheKey = "metric:{$metricName}:stats:" . md5(json_encode($labels));
        
        return Cache::remember($cacheKey, 300, function () use ($metricName, $labels) {
            $query = MetricAggregation::where('metric_name', $metricName);
            
            foreach ($labels as $key => $value) {
                $query->whereJsonContains("labels->{$key}", $value);
            }
            
            return [
                'min' => $query->min('value'),
                'max' => $query->max('value'),
                'avg' => $query->avg('value'),
                'sum' => $query->sum('value'),
                'count' => $query->count(),
            ];
        });
    }

    /**
     * Get comparison data between two time ranges
     *
     * @param string $metricName
     * @param string $currentRange
     * @param string $previousRange
     * @param array $labels
     * @return array
     */
    public function getComparisonData(string $metricName, string $currentRange, string $previousRange, array $labels = []): array
    {
        $current = $this->getTimeSeriesData($metricName, $currentRange, $labels);
        $previous = $this->getTimeSeriesData($metricName, $previousRange, $labels);
        
        return [
            'current' => $current,
            'previous' => $previous,
            'comparison' => $this->calculateComparison($current, $previous),
        ];
    }

    /**
     * Get top metrics by value
     *
     * @param string $metricName
     * @param int $limit
     * @param array $labels
     * @return Collection
     */
    public function getTopMetrics(string $metricName, int $limit = 10, array $labels = []): Collection
    {
        $query = MetricAggregation::where('metric_name', $metricName)
            ->orderBy('value', 'desc')
            ->limit($limit);
        
        foreach ($labels as $key => $value) {
            $query->whereJsonContains("labels->{$key}", $value);
        }
        
        return $query->get();
    }

    /**
     * Get distribution data for a metric
     *
     * @param string $metricName
     * @param int $buckets
     * @param array $labels
     * @return array
     */
    public function getDistributionData(string $metricName, int $buckets = 10, array $labels = []): array
    {
        $query = MetricAggregation::where('metric_name', $metricName);
        
        foreach ($labels as $key => $value) {
            $query->whereJsonContains("labels->{$key}", $value);
        }
        
        $min = $query->min('value');
        $max = $query->max('value');
        $range = $max - $min;
        $bucketSize = $range / $buckets;
        
        $distribution = [];
        for ($i = 0; $i < $buckets; $i++) {
            $bucketMin = $min + ($i * $bucketSize);
            $bucketMax = $bucketMin + $bucketSize;
            
            $count = $query->whereBetween('value', [$bucketMin, $bucketMax])->count();
            $distribution[] = [
                'range' => [$bucketMin, $bucketMax],
                'count' => $count,
            ];
        }
        
        return $distribution;
    }

    /**
     * Calculate comparison between two time series
     *
     * @param Collection $current
     * @param Collection $previous
     * @return array
     */
    private function calculateComparison(Collection $current, Collection $previous): array
    {
        $currentAvg = $current->avg('value');
        $previousAvg = $previous->avg('value');
        
        if ($previousAvg === 0) {
            return [
                'percentage_change' => 0,
                'absolute_change' => 0,
            ];
        }
        
        $percentageChange = (($currentAvg - $previousAvg) / $previousAvg) * 100;
        
        return [
            'percentage_change' => $percentageChange,
            'absolute_change' => $currentAvg - $previousAvg,
        ];
    }
} 