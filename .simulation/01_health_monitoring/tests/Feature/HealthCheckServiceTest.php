<?php

namespace Tests\Feature;

use App\Models\ServiceHealth;
use App\Services\HealthCheckService;
use App\Services\AlertService;
use App\Services\MetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\HealthCheckCompleted;
use App\Events\HealthAlertTriggered;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $healthCheckService;
    protected $alertService;
    protected $metricService;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->alertService = app(AlertService::class);
        $this->metricService = app(MetricService::class);
        $this->healthCheckService = app(HealthCheckService::class);
    }

    public function test_can_check_all_services()
    {
        // Create test services
        ServiceHealth::create([
            'service_name' => 'api-service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 100,
            'error_count' => 0,
            'warning_count' => 0
        ]);

        ServiceHealth::create([
            'service_name' => 'database-service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 50,
            'error_count' => 0,
            'warning_count' => 0
        ]);

        $result = $this->healthCheckService->checkAllServices();

        $this->assertArrayHasKey('overall_status', $result);
        $this->assertArrayHasKey('services', $result);
        $this->assertCount(2, $result['services']);
        $this->assertEquals('healthy', $result['overall_status']);

        Event::assertDispatched(HealthCheckCompleted::class);
    }

    public function test_can_check_single_service()
    {
        $service = ServiceHealth::create([
            'service_name' => 'api-service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 100,
            'error_count' => 0,
            'warning_count' => 0
        ]);

        $result = $this->healthCheckService->checkService($service->service_name);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertArrayHasKey('error_count', $result);
        $this->assertArrayHasKey('warning_count', $result);
        $this->assertEquals('healthy', $result['status']);

        Event::assertDispatched(HealthCheckCompleted::class);
    }

    public function test_creates_alert_on_service_failure()
    {
        $service = ServiceHealth::create([
            'service_name' => 'api-service',
            'status' => 'unhealthy',
            'last_check' => now(),
            'response_time' => 1000,
            'error_count' => 5,
            'warning_count' => 2
        ]);

        $this->healthCheckService->checkService($service->service_name);

        $this->assertDatabaseHas('alerts', [
            'service_health_id' => $service->id,
            'type' => 'error',
            'message' => 'Service is unhealthy',
            'status' => 'active'
        ]);

        Event::assertDispatched(HealthCheckCompleted::class);
    }

    public function test_updates_service_metrics()
    {
        $service = ServiceHealth::create([
            'service_name' => 'api-service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 100,
            'error_count' => 0,
            'warning_count' => 0
        ]);

        $this->healthCheckService->checkService($service->service_name);

        $this->assertDatabaseHas('metrics', [
            'service_health_id' => $service->id,
            'type' => 'response_time',
            'value' => 100
        ]);

        Event::assertDispatched(HealthCheckCompleted::class);
    }

    public function test_handles_nonexistent_service()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->healthCheckService->checkService('nonexistent-service');
    }

    public function test_calculates_overall_health_status()
    {
        ServiceHealth::create([
            'service_name' => 'api-service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 100,
            'error_count' => 0,
            'warning_count' => 0
        ]);

        ServiceHealth::create([
            'service_name' => 'database-service',
            'status' => 'unhealthy',
            'last_check' => now(),
            'response_time' => 1000,
            'error_count' => 5,
            'warning_count' => 2
        ]);

        $result = $this->healthCheckService->checkAllServices();

        $this->assertEquals('unhealthy', $result['overall_status']);
        $this->assertCount(2, $result['services']);

        Event::assertDispatched(HealthCheckCompleted::class);
    }
} 