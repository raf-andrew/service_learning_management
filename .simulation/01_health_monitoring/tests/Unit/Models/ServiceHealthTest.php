<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ServiceHealth;
use App\Models\Metric;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceHealthTest extends TestCase
{
    use RefreshDatabase;

    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);
    }

    public function test_service_health_creation()
    {
        $this->assertInstanceOf(ServiceHealth::class, $this->service);
        $this->assertEquals('test_service', $this->service->service_name);
        $this->assertEquals('healthy', $this->service->status);
        $this->assertEquals(0, $this->service->error_count);
        $this->assertEquals(0, $this->service->warning_count);
    }

    public function test_service_health_relationships()
    {
        // Create test metrics
        $metric1 = $this->service->metrics()->create([
            'name' => 'cpu_usage',
            'value' => 45.5,
            'unit' => 'percent',
            'threshold' => 80.0,
            'timestamp' => now()
        ]);

        $metric2 = $this->service->metrics()->create([
            'name' => 'memory_usage',
            'value' => 75.5,
            'unit' => 'percent',
            'threshold' => 85.0,
            'timestamp' => now()
        ]);

        // Create test alerts
        $alert1 = $this->service->alerts()->create([
            'type' => 'cpu_usage',
            'level' => 'warning',
            'message' => 'CPU usage is approaching threshold'
        ]);

        $alert2 = $this->service->alerts()->create([
            'type' => 'memory_usage',
            'level' => 'critical',
            'message' => 'Memory usage is above threshold'
        ]);

        // Test metrics relationship
        $this->assertCount(2, $this->service->metrics);
        $this->assertInstanceOf(Metric::class, $this->service->metrics->first());
        $this->assertEquals('cpu_usage', $this->service->metrics->first()->name);
        $this->assertEquals('memory_usage', $this->service->metrics->last()->name);

        // Test alerts relationship
        $this->assertCount(2, $this->service->alerts);
        $this->assertInstanceOf(Alert::class, $this->service->alerts->first());
        $this->assertEquals('warning', $this->service->alerts->first()->level);
        $this->assertEquals('critical', $this->service->alerts->last()->level);
    }

    public function test_is_healthy()
    {
        $this->assertTrue($this->service->isHealthy());

        $this->service->update(['status' => 'unhealthy']);
        $this->assertFalse($this->service->isHealthy());
    }

    public function test_has_warnings()
    {
        $this->assertFalse($this->service->hasWarnings());

        $this->service->update(['warning_count' => 1]);
        $this->assertTrue($this->service->hasWarnings());
    }

    public function test_has_errors()
    {
        $this->assertFalse($this->service->hasErrors());

        $this->service->update(['error_count' => 1]);
        $this->assertTrue($this->service->hasErrors());
    }

    public function test_get_health_status()
    {
        // Test healthy status
        $this->assertEquals('healthy', $this->service->getHealthStatus());

        // Test warning status
        $this->service->update(['warning_count' => 1]);
        $this->assertEquals('warning', $this->service->getHealthStatus());

        // Test critical status
        $this->service->update(['error_count' => 1]);
        $this->assertEquals('critical', $this->service->getHealthStatus());
    }

    public function test_service_health_casts()
    {
        $this->assertIsFloat($this->service->response_time);
        $this->assertIsInt($this->service->error_count);
        $this->assertIsInt($this->service->warning_count);
        $this->assertInstanceOf(\DateTime::class, $this->service->last_check);
    }
} 