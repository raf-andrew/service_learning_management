<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricAggregation extends Model
{
    protected $fillable = [
        'metric_type_id',
        'name',
        'description',
        'aggregation_method',
        'time_window',
        'group_by',
        'filters',
        'result'
    ];

    protected $casts = [
        'group_by' => 'array',
        'filters' => 'array',
        'result' => 'array',
        'time_window' => 'integer'
    ];

    public function metricType(): BelongsTo
    {
        return $this->belongsTo(MetricType::class);
    }

    public function calculate(array $metrics): array
    {
        return match ($this->aggregation_method) {
            'sum' => $this->calculateSum($metrics),
            'average' => $this->calculateAverage($metrics),
            'min' => $this->calculateMin($metrics),
            'max' => $this->calculateMax($metrics),
            'count' => $this->calculateCount($metrics),
            'percentile' => $this->calculatePercentile($metrics),
            default => throw new \InvalidArgumentException("Unknown aggregation method: {$this->aggregation_method}"),
        };
    }

    private function calculateSum(array $metrics): array
    {
        $sum = 0;
        foreach ($metrics as $metric) {
            $sum += $metric['value'];
        }
        return ['value' => $sum];
    }

    private function calculateAverage(array $metrics): array
    {
        if (empty($metrics)) {
            return ['value' => 0];
        }
        $sum = $this->calculateSum($metrics)['value'];
        return ['value' => $sum / count($metrics)];
    }

    private function calculateMin(array $metrics): array
    {
        if (empty($metrics)) {
            return ['value' => null];
        }
        $min = PHP_FLOAT_MAX;
        foreach ($metrics as $metric) {
            $min = min($min, $metric['value']);
        }
        return ['value' => $min];
    }

    private function calculateMax(array $metrics): array
    {
        if (empty($metrics)) {
            return ['value' => null];
        }
        $max = PHP_FLOAT_MIN;
        foreach ($metrics as $metric) {
            $max = max($max, $metric['value']);
        }
        return ['value' => $max];
    }

    private function calculateCount(array $metrics): array
    {
        return ['value' => count($metrics)];
    }

    private function calculatePercentile(array $metrics): array
    {
        if (empty($metrics)) {
            return ['value' => null];
        }
        $values = array_column($metrics, 'value');
        sort($values);
        $index = ceil(count($values) * 0.95) - 1;
        return ['value' => $values[$index]];
    }
} 