<?php

namespace Tests\Unit\Models;

use App\Models\MetricType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricTypeTest extends TestCase
{
    use RefreshDatabase;

    private MetricType $metricType;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->metricType = MetricType::create([
            'name' => 'cpu_usage',
            'description' => 'CPU usage percentage',
            'unit' => 'percent',
            'data_type' => 'float',
            'validation_rules' => [
                ['type' => 'min', 'value' => 0],
                ['type' => 'max', 'value' => 100]
            ],
            'aggregation_methods' => ['average', 'max', 'min']
        ]);
    }

    public function test_metric_type_can_be_created(): void
    {
        $this->assertInstanceOf(MetricType::class, $this->metricType);
        $this->assertEquals('cpu_usage', $this->metricType->name);
        $this->assertEquals('percent', $this->metricType->unit);
        $this->assertEquals('float', $this->metricType->data_type);
    }

    public function test_metric_type_validates_values_correctly(): void
    {
        $this->assertTrue($this->metricType->validateValue(50.5));
        $this->assertTrue($this->metricType->validateValue(0));
        $this->assertTrue($this->metricType->validateValue(100));
        $this->assertFalse($this->metricType->validateValue(-1));
        $this->assertFalse($this->metricType->validateValue(101));
        $this->assertFalse($this->metricType->validateValue('invalid'));
    }

    public function test_metric_type_has_metrics_relationship(): void
    {
        $this->assertEmpty($this->metricType->metrics);
    }

    public function test_metric_type_has_aggregations_relationship(): void
    {
        $this->assertEmpty($this->metricType->aggregations);
    }
} 