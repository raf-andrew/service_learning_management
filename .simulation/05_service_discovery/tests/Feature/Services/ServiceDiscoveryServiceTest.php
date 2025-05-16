<?php

namespace Tests\Feature\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use App\Services\ServiceDiscoveryService;
use App\Services\LoadBalancingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ServiceDiscoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $discoveryService;
    protected $loadBalancer;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadBalancer = $this->mock(LoadBalancingService::class);
        $this->discoveryService = new ServiceDiscoveryService($this->loadBalancer);

        $this->service = Service::create([
            'name' => 'test-service',
            'description' => 'Test Service',
            'version' => '1.0.0',
            'status' => 'active',
            'tags' => ['test', 'api'],
            'metadata' => [
                'environment' => 'testing',
                'region' => 'us-east'
            ],
            'dependencies' => ['auth-service', 'db-service']
        ]);
    }

    public function test_can_find_service_by_name()
    {
        $result = $this->discoveryService->findService('test-service');

        $this->assertInstanceOf(Service::class, $result);
        $this->assertEquals('test-service', $result->name);
        $this->assertEquals('active', $result->status);
    }

    public function test_can_find_services_by_tags()
    {
        $result = $this->discoveryService->findServicesByTags(['test']);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->tags->contains('test'));
    }

    public function test_can_find_services_by_metadata()
    {
        $result = $this->discoveryService->findServicesByMetadata([
            'environment' => 'testing'
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('testing', $result->first()->metadata['environment']);
    }

    public function test_can_get_service_instances()
    {
        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'healthy',
            'last_health_check' => now(),
            'response_time' => 100
        ]);

        $result = $this->discoveryService->getServiceInstances('test-service');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('healthy', $result->first()->status);
    }

    public function test_can_get_next_instance()
    {
        $instance = ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'healthy',
            'last_health_check' => now(),
            'response_time' => 100
        ]);

        $this->loadBalancer
            ->shouldReceive('selectInstance')
            ->once()
            ->andReturn($instance);

        $result = $this->discoveryService->getNextInstance('test-service');

        $this->assertInstanceOf(ServiceInstance::class, $result);
        $this->assertEquals($instance->id, $result->id);
    }

    public function test_can_get_service_dependencies()
    {
        Service::create([
            'name' => 'auth-service',
            'description' => 'Auth Service',
            'version' => '1.0.0',
            'status' => 'active'
        ]);

        Service::create([
            'name' => 'db-service',
            'description' => 'Database Service',
            'version' => '1.0.0',
            'status' => 'active'
        ]);

        $result = $this->discoveryService->getServiceDependencies('test-service');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->pluck('name')->contains('auth-service'));
        $this->assertTrue($result->pluck('name')->contains('db-service'));
    }

    public function test_can_check_service_availability()
    {
        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'healthy',
            'last_health_check' => now(),
            'response_time' => 100
        ]);

        $result = $this->discoveryService->isServiceAvailable('test-service');

        $this->assertTrue($result);
    }

    public function test_can_get_service_health()
    {
        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'healthy',
            'last_health_check' => now(),
            'response_time' => 100
        ]);

        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8081,
            'status' => 'unhealthy',
            'last_health_check' => now(),
            'response_time' => 200
        ]);

        $result = $this->discoveryService->getServiceHealth('test-service');

        $this->assertIsArray($result);
        $this->assertTrue($result['available']);
        $this->assertEquals(2, $result['instance_count']);
        $this->assertEquals(1, $result['healthy_instances']);
        $this->assertEquals(150, $result['average_response_time']);
    }

    public function test_caches_service_lookup()
    {
        $this->discoveryService->findService('test-service');

        // Second call should use cache
        $this->discoveryService->findService('test-service');

        $this->assertTrue(Cache::has('service:test-service'));
    }

    public function test_can_clear_cache()
    {
        $this->discoveryService->findService('test-service');
        $this->discoveryService->clearCache('test-service');

        $this->assertFalse(Cache::has('service:test-service'));
    }

    public function test_returns_null_for_nonexistent_service()
    {
        $result = $this->discoveryService->findService('nonexistent-service');

        $this->assertNull($result);
    }

    public function test_returns_empty_collection_for_nonexistent_tags()
    {
        $result = $this->discoveryService->findServicesByTags(['nonexistent']);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
} 