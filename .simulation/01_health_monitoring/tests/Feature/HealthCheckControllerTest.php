<?php

namespace Tests\Feature;

use App\Models\ServiceHealth;
use App\Models\Alert;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create API key for testing
        $this->apiKey = ApiKey::create([
            'name' => 'Test API Key',
            'key' => 'test-api-key',
            'is_active' => true
        ]);

        // Create a test service
        $this->service = ServiceHealth::create([
            'service_name' => 'test-service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 100,
            'error_count' => 0,
            'warning_count' => 0
        ]);
    }

    public function test_can_check_all_services()
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->getJson('/api/health/check');

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

    public function test_can_get_service_status()
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->getJson("/api/health/service/{$this->service->service_name}");

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

    public function test_can_get_service_metrics()
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->getJson("/api/health/metrics/{$this->service->service_name}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'service_name',
                    'metrics'
                ]
            ]);
    }

    public function test_can_get_service_alerts()
    {
        // Create a test alert
        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'error',
            'message' => 'Test alert',
            'severity' => 'high'
        ]);

        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->getJson("/api/health/alerts/{$this->service->service_name}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'service_name',
                    'alerts'
                ]
            ]);
    }

    public function test_can_acknowledge_alert()
    {
        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'error',
            'message' => 'Test alert',
            'severity' => 'high'
        ]);

        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson("/api/health/alerts/{$alert->id}/acknowledge", [
                'acknowledged_by' => 'test-user'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Alert acknowledged successfully'
            ]);

        $this->assertNotNull($alert->fresh()->acknowledged_at);
        $this->assertEquals('test-user', $alert->fresh()->acknowledged_by);
    }

    public function test_can_resolve_alert()
    {
        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'error',
            'message' => 'Test alert',
            'severity' => 'high'
        ]);

        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson("/api/health/alerts/{$alert->id}/resolve", [
                'resolved_by' => 'test-user'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Alert resolved successfully'
            ]);

        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertEquals('test-user', $alert->fresh()->resolved_by);
    }

    public function test_requires_api_key()
    {
        $response = $this->getJson('/api/health/check');
        $response->assertStatus(401);
    }

    public function test_validates_api_key()
    {
        $response = $this->withHeader('X-API-Key', 'invalid-key')
            ->getJson('/api/health/check');
        $response->assertStatus(401);
    }

    public function test_handles_nonexistent_service()
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->getJson('/api/health/service/nonexistent-service');
        $response->assertStatus(404);
    }

    public function test_handles_nonexistent_alert()
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/health/alerts/999/acknowledge', [
                'acknowledged_by' => 'test-user'
            ]);
        $response->assertStatus(404);
    }
} 