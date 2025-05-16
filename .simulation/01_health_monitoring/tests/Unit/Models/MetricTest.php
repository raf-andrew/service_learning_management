<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Metric;
use App\Models\ServiceHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricTest extends TestCase
{
    use RefreshDatabase;

    private $service;
    private $metric;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        $this->metric = Metric::create([
            'service_health_id' => $this->service->id,
            'name' => 'cpu_usage',
            'value' => 45.5,
            'unit' => 'percent',
            'threshold' => 80.0,
            'timestamp' => now()
        ]);
    }

    public function test_metric_creation()
    {
        $this->assertInstanceOf(Metric::class, $this->metric);
        $this->assertEquals('cpu_usage', $this->metric->name);
        $this->assertEquals(45.5, $this->metric->value);
        $this->assertEquals('percent', $this->metric->unit);
        $this->assertEquals(80.0, $this->metric->threshold);
    }

    public function test_metric_relationships()
    {
        $this->assertInstanceOf(ServiceHealth::class, $this->metric->service);
        $this->assertEquals($this->service->id, $this->metric->service->id);
    }

    public function test_is_above_threshold()
    {
        // Test below threshold
        $this->assertFalse($this->metric->isAboveThreshold());

        // Test above threshold
        $this->metric->update(['value' => 85.0]);
        $this->assertTrue($this->metric->isAboveThreshold());
    }

    public function test_is_below_threshold()
    {
        // Test above threshold
        $this->assertTrue($this->metric->isBelowThreshold());

        // Test below threshold
        $this->metric->update(['value' => 85.0]);
        $this->assertFalse($this->metric->isBelowThreshold());
    }

    public function test_get_formatted_value()
    {
        $this->assertEquals('45.5%', $this->metric->getFormattedValue());

        // Test with different unit
        $this->metric->update(['unit' => 'ms', 'value' => 150.5]);
        $this->assertEquals('150.5ms', $this->metric->getFormattedValue());
    }

    public function test_metric_casts()
    {
        $this->assertIsFloat($this->metric->value);
        $this->assertIsFloat($this->metric->threshold);
        $this->assertInstanceOf(\DateTime::class, $this->metric->timestamp);
    }

    public function test_metric_validation()
    {
        // Test required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        Metric::create([
            'service_health_id' => $this->service->id,
            'name' => 'test_metric'
        ]);

        // Test value range
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->metric->update(['value' => -1]);

        // Test threshold range
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->metric->update(['threshold' => 101]);
    }
} 