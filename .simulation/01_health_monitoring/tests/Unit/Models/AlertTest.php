<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Alert;
use App\Models\ServiceHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private $service;
    private $alert;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        $this->alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'warning',
            'message' => 'CPU usage is approaching threshold',
            'status' => 'active'
        ]);
    }

    public function test_alert_creation()
    {
        $this->assertInstanceOf(Alert::class, $this->alert);
        $this->assertEquals('cpu_usage', $this->alert->type);
        $this->assertEquals('warning', $this->alert->level);
        $this->assertEquals('CPU usage is approaching threshold', $this->alert->message);
        $this->assertEquals('active', $this->alert->status);
    }

    public function test_alert_relationships()
    {
        $this->assertInstanceOf(ServiceHealth::class, $this->alert->service);
        $this->assertEquals($this->service->id, $this->alert->service->id);
    }

    public function test_is_active()
    {
        $this->assertTrue($this->alert->isActive());

        $this->alert->update(['status' => 'acknowledged']);
        $this->assertFalse($this->alert->isActive());
    }

    public function test_is_acknowledged()
    {
        $this->assertFalse($this->alert->isAcknowledged());

        $this->alert->update(['status' => 'acknowledged']);
        $this->assertTrue($this->alert->isAcknowledged());
    }

    public function test_is_resolved()
    {
        $this->assertFalse($this->alert->isResolved());

        $this->alert->update(['status' => 'resolved']);
        $this->assertTrue($this->alert->isResolved());
    }

    public function test_acknowledge()
    {
        $this->alert->acknowledge();
        $this->assertEquals('acknowledged', $this->alert->status);
        $this->assertNotNull($this->alert->acknowledged_at);
    }

    public function test_resolve()
    {
        $this->alert->resolve();
        $this->assertEquals('resolved', $this->alert->status);
        $this->assertNotNull($this->alert->resolved_at);
    }

    public function test_alert_casts()
    {
        $this->assertInstanceOf(\DateTime::class, $this->alert->created_at);
        $this->assertNull($this->alert->acknowledged_at);
        $this->assertNull($this->alert->resolved_at);
    }

    public function test_alert_validation()
    {
        // Test required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'test_alert'
        ]);

        // Test invalid level
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->alert->update(['level' => 'invalid_level']);

        // Test invalid status
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->alert->update(['status' => 'invalid_status']);
    }

    public function test_alert_scopes()
    {
        // Create additional alerts
        Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'memory_usage',
            'level' => 'critical',
            'message' => 'Memory usage is above threshold',
            'status' => 'active'
        ]);

        Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'disk_usage',
            'level' => 'warning',
            'message' => 'Disk usage is approaching threshold',
            'status' => 'acknowledged'
        ]);

        // Test active scope
        $this->assertCount(2, Alert::active()->get());

        // Test acknowledged scope
        $this->assertCount(1, Alert::acknowledged()->get());

        // Test critical scope
        $this->assertCount(1, Alert::critical()->get());

        // Test warning scope
        $this->assertCount(2, Alert::warning()->get());
    }
} 