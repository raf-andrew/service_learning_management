<?php

namespace Tests\Unit\Models;

use App\Models\MetricType;
use App\Models\MetricAggregation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricAggregationTest extends TestCase
{
    use RefreshDatabase;

    private MetricType $metricType;
    private MetricAggregation $aggregation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time',
            'unit' => 'milliseconds',
            'data_type' => 'float',
            'validation_rules' => [
                ['type' => 'min', 'value' => 0]
            ],
            'aggregation_methods' => ['average', 'max', 'min', 'percentile']
        ]);

        $this->aggregation = MetricAggregation::create([
            'metric_type_id' => $this->metricType->id,
            'name' => 'p95_response_time',
            'description' => '95th percentile response time',
            'aggregation_method' => 'percentile',
            'time_window' => 3600,
            'group_by' => ['endpoint'],
            'filters' => [
                ['field' => 'status', 'operator' => '=', 'value' => 200]
            ]
        ]);
    }

    public function test_aggregation_can_be_created(): void
    {
        $this->assertInstanceOf(MetricAggregation::class, $this->aggregation);
        $this->assertEquals('p95_response_time', $this->aggregation->name);
        $this->assertEquals('percentile', $this->aggregation->aggregation_method);
        $this->assertEquals(3600, $this->aggregation->time_window);
    }

    public function test_aggregation_calculates_sum_correctly(): void
    {
        $metrics = [
            ['value' => 100],
            ['value' => 200],
            ['value' => 300]
        ];

        $this->aggregation->aggregation_method = 'sum';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(600, $result['value']);
    }

    public function test_aggregation_calculates_average_correctly(): void
    {
        $metrics = [
            ['value' => 100],
            ['value' => 200],
            ['value' => 300]
        ];

        $this->aggregation->aggregation_method = 'average';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(200, $result['value']);
    }

    public function test_aggregation_calculates_min_correctly(): void
    {
        $metrics = [
            ['value' => 100],
            ['value' => 200],
            ['value' => 300]
        ];

        $this->aggregation->aggregation_method = 'min';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(100, $result['value']);
    }

    public function test_aggregation_calculates_max_correctly(): void
    {
        $metrics = [
            ['value' => 100],
            ['value' => 200],
            ['value' => 300]
        ];

        $this->aggregation->aggregation_method = 'max';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(300, $result['value']);
    }

    public function test_aggregation_calculates_count_correctly(): void
    {
        $metrics = [
            ['value' => 100],
            ['value' => 200],
            ['value' => 300]
        ];

        $this->aggregation->aggregation_method = 'count';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(3, $result['value']);
    }

    public function test_aggregation_calculates_percentile_correctly(): void
    {
        $metrics = [
            ['value' => 100],
            ['value' => 200],
            ['value' => 300],
            ['value' => 400],
            ['value' => 500]
        ];

        $this->aggregation->aggregation_method = 'percentile';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(500, $result['value']);
    }

    public function test_aggregation_handles_empty_metrics(): void
    {
        $metrics = [];

        $this->aggregation->aggregation_method = 'average';
        $result = $this->aggregation->calculate($metrics);
        
        $this->assertEquals(0, $result['value']);
    }

    public function test_aggregation_has_metric_type_relationship(): void
    {
        $this->assertInstanceOf(MetricType::class, $this->aggregation->metricType);
        $this->assertEquals($this->metricType->id, $this->aggregation->metricType->id);
    }
} 