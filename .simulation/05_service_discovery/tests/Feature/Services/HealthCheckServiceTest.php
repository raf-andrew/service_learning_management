<?php

namespace Tests\Feature\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use App\Models\HealthCheck;
use App\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    private HealthCheckService $healthCheckService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthCheckService = new HealthCheckService();
    }

    public function test_can_check_healthy_instance()
    {
        Http::fake([
            'http://example.com/health' => Http::response('OK', 200),
        ]);

        $service = Service::factory()->create();
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health',
        ]);

        $healthCheck = $this->healthCheckService->checkInstance($instance);

        $this->assertEquals('healthy', $healthCheck->status);
        $this->assertNull($healthCheck->error_message);
        $this->assertNotNull($healthCheck->response_time);
        
        $instance->refresh();
        $this->assertEquals('healthy', $instance->status);
    }

    public function test_can_check_unhealthy_instance()
    {
        Http::fake([
            'http://example.com/health' => Http::response('Error', 500),
        ]);

        $service = Service::factory()->create();
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health',
        ]);

        $healthCheck = $this->healthCheckService->checkInstance($instance);

        $this->assertEquals('unhealthy', $healthCheck->status);
        $this->assertNotNull($healthCheck->error_message);
        $this->assertStringContainsString('HTTP 500', $healthCheck->error_message);
        
        $instance->refresh();
        $this->assertEquals('unhealthy', $instance->status);
    }

    public function test_can_check_instance_with_connection_error()
    {
        Http::fake([
            'http://example.com/health' => Http::throw(new \Exception('Connection refused')),
        ]);

        $service = Service::factory()->create();
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health',
        ]);

        $healthCheck = $this->healthCheckService->checkInstance($instance);

        $this->assertEquals('unhealthy', $healthCheck->status);
        $this->assertEquals('Connection refused', $healthCheck->error_message);
        
        $instance->refresh();
        $this->assertEquals('unhealthy', $instance->status);
    }

    public function test_can_check_all_service_instances()
    {
        Http::fake([
            'http://example.com/health1' => Http::response('OK', 200),
            'http://example.com/health2' => Http::response('Error', 500),
        ]);

        $service = Service::factory()->create();
        $instance1 = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health1',
        ]);
        $instance2 = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health2',
        ]);

        $healthChecks = $this->healthCheckService->checkService($service);

        $this->assertCount(2, $healthChecks);
        $this->assertEquals('healthy', $healthChecks[0]->status);
        $this->assertEquals('unhealthy', $healthChecks[1]->status);
    }

    public function test_can_get_instance_health_history()
    {
        $service = Service::factory()->create();
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
        ]);

        HealthCheck::factory()->count(15)->create([
            'service_instance_id' => $instance->id,
        ]);

        $history = $this->healthCheckService->getInstanceHealthHistory($instance, 10);

        $this->assertCount(10, $history);
        $this->assertTrue($history->isSortedByDesc('created_at'));
    }

    public function test_can_get_service_health_status()
    {
        $service = Service::factory()->create();
        
        ServiceInstance::factory()->count(3)->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100,
        ]);
        
        ServiceInstance::factory()->count(2)->create([
            'service_id' => $service->id,
            'status' => 'unhealthy',
            'response_time' => 200,
        ]);

        $health = $this->healthCheckService->getServiceHealth($service);

        $this->assertEquals(5, $health['total_instances']);
        $this->assertEquals(3, $health['healthy_instances']);
        $this->assertEquals(2, $health['unhealthy_instances']);
        $this->assertEquals(60, $health['health_percentage']);
        $this->assertEquals(140, $health['average_response_time']);
    }

    public function test_schedules_health_checks_based_on_interval()
    {
        Http::fake([
            'http://example.com/health' => Http::response('OK', 200),
        ]);

        $service = Service::factory()->create([
            'health_check_interval' => 60,
        ]);
        
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health',
            'last_health_check' => now()->subMinutes(30),
        ]);

        $this->healthCheckService->scheduleHealthChecks();
        
        $instance->refresh();
        $this->assertEquals('healthy', $instance->status);
    }

    public function test_skips_health_check_if_interval_not_elapsed()
    {
        Http::fake([
            'http://example.com/health' => Http::response('OK', 200),
        ]);

        $service = Service::factory()->create([
            'health_check_interval' => 60,
        ]);
        
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'health_check_url' => 'http://example.com/health',
            'last_health_check' => now()->subSeconds(30),
        ]);

        $this->healthCheckService->scheduleHealthChecks();
        
        Http::assertNotSent(function ($request) {
            return $request->url() === 'http://example.com/health';
        });
    }

    public function test_can_get_health_statistics()
    {
        $service = Service::factory()->create();
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
        ]);

        HealthCheck::factory()->count(5)->create([
            'service_instance_id' => $instance->id,
            'status' => 'healthy',
            'response_time' => 100,
            'created_at' => now()->subMinutes(30),
        ]);

        HealthCheck::factory()->count(3)->create([
            'service_instance_id' => $instance->id,
            'status' => 'unhealthy',
            'error_message' => 'Connection timeout',
            'response_time' => 200,
            'created_at' => now()->subMinutes(30),
        ]);

        $stats = $this->healthCheckService->getHealthStatistics($service, '1h');

        $this->assertEquals(8, $stats['total_checks']);
        $this->assertEquals(5, $stats['healthy_checks']);
        $this->assertEquals(3, $stats['unhealthy_checks']);
        $this->assertEquals(137.5, $stats['average_response_time']);
        $this->assertEquals(3, $stats['error_distribution']['Connection timeout']);
    }
} 