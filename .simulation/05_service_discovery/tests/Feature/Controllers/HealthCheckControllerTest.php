<?php

namespace Tests\Feature\Controllers;

use App\Models\Service;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);
    }

    public function test_can_record_health_check(): void
    {
        $response = $this->postJson("/api/services/{$this->service->name}/health-checks", [
            'status' => 'success',
            'response_time' => 100.5,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'response_time' => 100.5,
            ]);

        $this->assertDatabaseHas('health_checks', [
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
        ]);
    }

    public function test_can_record_failed_health_check(): void
    {
        $response = $this->postJson("/api/services/{$this->service->name}/health-checks", [
            'status' => 'failed',
            'response_time' => 100.5,
            'error_message' => 'Connection timeout',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'failed',
                'response_time' => 100.5,
                'error_message' => 'Connection timeout',
            ]);
    }

    public function test_can_get_health_check_history(): void
    {
        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now()->subHour(),
        ]);

        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 200.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $response = $this->getJson("/api/services/{$this->service->name}/health-checks/history");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_filter_health_check_history(): void
    {
        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now()->subHour(),
        ]);

        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 200.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $response = $this->getJson("/api/services/{$this->service->name}/health-checks/history?status=failed");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'status' => 'failed',
                    'response_time' => 200.5,
                ],
            ]);
    }

    public function test_can_get_latest_health_check(): void
    {
        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now()->subHour(),
        ]);

        $latest = HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 200.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $response = $this->getJson("/api/services/{$this->service->name}/health-checks/latest");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $latest->id,
                'status' => 'failed',
                'response_time' => 200.5,
            ]);
    }

    public function test_can_get_health_check_statistics(): void
    {
        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now()->subHour(),
        ]);

        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 200.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $response = $this->getJson("/api/services/{$this->service->name}/health-checks/statistics");

        $response->assertStatus(200)
            ->assertJson([
                'total_checks' => 2,
                'successful_checks' => 1,
                'failed_checks' => 1,
                'average_response_time' => 150.5,
                'current_status' => 'unhealthy',
            ]);
    }

    public function test_returns_404_for_nonexistent_service(): void
    {
        $response = $this->getJson('/api/services/nonexistent/health-checks/latest');

        $response->assertStatus(404);
    }

    public function test_returns_404_when_no_health_checks_exist(): void
    {
        $response = $this->getJson("/api/services/{$this->service->name}/health-checks/latest");

        $response->assertStatus(404);
    }
} 