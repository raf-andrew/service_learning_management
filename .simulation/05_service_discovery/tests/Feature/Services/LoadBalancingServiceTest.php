<?php

namespace Tests\Feature\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use App\Services\LoadBalancingService;
use App\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class LoadBalancingServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoadBalancingService $loadBalancingService;
    private HealthCheckService $healthCheckService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthCheckService = $this->mock(HealthCheckService::class);
        $this->loadBalancingService = new LoadBalancingService($this->healthCheckService);
    }

    public function test_returns_null_when_no_healthy_instances()
    {
        $service = Service::factory()->create();
        ServiceInstance::factory()->count(3)->create([
            'service_id' => $service->id,
            'status' => 'unhealthy',
        ]);

        $instance = $this->loadBalancingService->getNextInstance($service);

        $this->assertNull($instance);
    }

    public function test_round_robin_strategy_cycles_through_instances()
    {
        $service = Service::factory()->create();
        $instances = ServiceInstance::factory()->count(3)->create([
            'service_id' => $service->id,
            'status' => 'healthy',
        ]);

        // First call
        $instance1 = $this->loadBalancingService->getNextInstance($service, 'round-robin');
        $this->assertEquals($instances[0]->id, $instance1->id);

        // Second call
        $instance2 = $this->loadBalancingService->getNextInstance($service, 'round-robin');
        $this->assertEquals($instances[1]->id, $instance2->id);

        // Third call
        $instance3 = $this->loadBalancingService->getNextInstance($service, 'round-robin');
        $this->assertEquals($instances[2]->id, $instance3->id);

        // Fourth call should start over
        $instance4 = $this->loadBalancingService->getNextInstance($service, 'round-robin');
        $this->assertEquals($instances[0]->id, $instance4->id);
    }

    public function test_least_connections_strategy_selects_correct_instance()
    {
        $service = Service::factory()->create();
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'current_connections' => 10,
        ]);
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'current_connections' => 5,
        ]);
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'current_connections' => 15,
        ]);

        $instance = $this->loadBalancingService->getNextInstance($service, 'least-connections');

        $this->assertEquals(5, $instance->current_connections);
    }

    public function test_response_time_strategy_selects_correct_instance()
    {
        $service = Service::factory()->create();
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 200,
        ]);
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100,
        ]);
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 300,
        ]);

        $instance = $this->loadBalancingService->getNextInstance($service, 'response-time');

        $this->assertEquals(100, $instance->response_time);
    }

    public function test_can_update_connection_count()
    {
        $service = Service::factory()->create();
        $instance = ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'current_connections' => 0,
        ]);

        $this->loadBalancingService->updateConnectionCount($instance, 5);

        $instance->refresh();
        $this->assertEquals(5, $instance->current_connections);
    }

    public function test_can_get_load_balancing_stats()
    {
        $service = Service::factory()->create();
        ServiceInstance::factory()->count(2)->create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'current_connections' => 5,
            'response_time' => 100,
        ]);
        ServiceInstance::factory()->create([
            'service_id' => $service->id,
            'status' => 'unhealthy',
            'current_connections' => 10,
            'response_time' => 200,
        ]);

        $stats = $this->loadBalancingService->getLoadBalancingStats($service);

        $this->assertEquals(3, $stats['total_instances']);
        $this->assertEquals(2, $stats['healthy_instances']);
        $this->assertEquals(20, $stats['total_connections']);
        $this->assertEquals(133.33, round($stats['average_response_time'], 2));
        $this->assertCount(3, $stats['instance_stats']);
    }

    public function test_round_robin_strategy_persists_last_instance()
    {
        $service = Service::factory()->create();
        $instances = ServiceInstance::factory()->count(3)->create([
            'service_id' => $service->id,
            'status' => 'healthy',
        ]);

        // First call
        $this->loadBalancingService->getNextInstance($service, 'round-robin');

        // Verify cache was set
        $this->assertTrue(Cache::has("service:{$service->id}:last_instance"));
        $this->assertEquals($instances[0]->id, Cache::get("service:{$service->id}:last_instance"));
    }

    public function test_round_robin_strategy_resets_after_cache_expiry()
    {
        $service = Service::factory()->create();
        $instances = ServiceInstance::factory()->count(3)->create([
            'service_id' => $service->id,
            'status' => 'healthy',
        ]);

        // First call
        $this->loadBalancingService->getNextInstance($service, 'round-robin');

        // Clear cache
        Cache::forget("service:{$service->id}:last_instance");

        // Next call should start from beginning
        $instance = $this->loadBalancingService->getNextInstance($service, 'round-robin');
        $this->assertEquals($instances[0]->id, $instance->id);
    }
} 