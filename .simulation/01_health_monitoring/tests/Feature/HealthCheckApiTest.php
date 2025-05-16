<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ServiceHealth;
use App\Models\Metric;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HealthCheckApiTest extends TestCase
{
    use RefreshDatabase;

    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test service
        $this->service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);
    }

    public function test_check_all_services()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'overall_status',
                    'services',
                    'timestamp'
                ]
            ]);
    }

    public function test_get_service_status()
    {
        $response = $this->getJson("/api/health/{$this->service->service_name}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'service_name',
                    'status',
                    'last_check',
                    'response_time',
                    'error_count',
                    'warning_count',
                    'health_status'
                ]
            ]);
    }

    public function test_get_service_metrics()
    {
        // Create some test metrics
        Metric::create([
            'service_health_id' => $this->service->id,
            'name' => 'cpu_usage',
            'value' => 45.5,
            'unit' => 'percent',
            'threshold' => 80.0,
            'timestamp' => now()
        ]);

        $response = $this->getJson("/api/health/{$this->service->service_name}/metrics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'service_name',
                    'metrics' => [
                        '*' => [
                            'id',
                            'name',
                            'value',
                            'unit',
                            'threshold',
                            'timestamp'
                        ]
                    ]
                ]
            ]);
    }

    public function test_get_service_alerts()
    {
        // Create some test alerts
        Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'warning',
            'message' => 'CPU usage is approaching threshold'
        ]);

        $response = $this->getJson("/api/health/{$this->service->service_name}/alerts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'service_name',
                    'alerts' => [
                        '*' => [
                            'id',
                            'type',
                            'level',
                            'message',
                            'resolved',
                            'resolved_at',
                            'acknowledged',
                            'acknowledged_at',
                            'acknowledged_by'
                        ]
                    ]
                ]
            ]);
    }

    public function test_acknowledge_alert()
    {
        // Create a test alert
        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'warning',
            'message' => 'CPU usage is approaching threshold'
        ]);

        $response = $this->postJson("/api/alerts/{$alert->id}/acknowledge", [
            'acknowledged_by' => 'test_user'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'acknowledged',
                    'acknowledged_at',
                    'acknowledged_by'
                ]
            ]);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'acknowledged' => true
        ]);
    }

    public function test_resolve_alert()
    {
        // Create a test alert
        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'warning',
            'message' => 'CPU usage is approaching threshold'
        ]);

        $response = $this->postJson("/api/alerts/{$alert->id}/resolve", [
            'resolved_by' => 'test_user'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'resolved',
                    'resolved_at',
                    'acknowledged',
                    'acknowledged_at',
                    'acknowledged_by'
                ]
            ]);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'resolved' => true
        ]);
    }

    public function test_invalid_service_name()
    {
        $response = $this->getJson('/api/health/nonexistent_service');

        $response->assertStatus(404);
    }

    public function test_invalid_alert_id()
    {
        $response = $this->postJson('/api/alerts/999/acknowledge', [
            'acknowledged_by' => 'test_user'
        ]);

        $response->assertStatus(404);
    }

    public function test_missing_acknowledgment_data()
    {
        $response = $this->postJson('/api/alerts/1/acknowledge', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['acknowledged_by']);
    }

    public function test_missing_resolution_data()
    {
        $response = $this->postJson('/api/alerts/1/resolve', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resolved_by']);
    }
} 