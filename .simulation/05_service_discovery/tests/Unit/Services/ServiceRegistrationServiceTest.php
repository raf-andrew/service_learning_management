<?php

namespace Tests\Unit\Services;

use App\Models\Service;
use App\Models\ServiceInstance;
use App\Services\ServiceRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServiceRegistrationService();
    }

    public function test_can_register_service(): void
    {
        $data = [
            'name' => 'test-service',
            'version' => '1.0.0',
            'description' => 'Test service',
            'metadata' => ['environment' => 'test'],
            'tags' => ['api', 'test'],
        ];

        $service = $this->service->registerService($data);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertEquals('test-service', $service->name);
        $this->assertEquals('1.0.0', $service->version);
    }

    public function test_can_register_instance(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $data = [
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'active',
            'metadata' => ['environment' => 'test'],
        ];

        $instance = $this->service->registerInstance('test-service', $data);

        $this->assertInstanceOf(ServiceInstance::class, $instance);
        $this->assertEquals('localhost', $instance->host);
        $this->assertEquals(8080, $instance->port);
    }

    public function test_cannot_register_duplicate_instance(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $data = [
            'host' => 'localhost',
            'port' => 8080,
        ];

        $this->service->registerInstance('test-service', $data);

        $this->expectException(\Exception::class);
        $this->service->registerInstance('test-service', $data);
    }

    public function test_can_update_instance(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $instance = ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $data = [
            'status' => 'inactive',
            'metadata' => ['environment' => 'production'],
        ];

        $updatedInstance = $this->service->updateInstance('test-service', $instance->id, $data);

        $this->assertEquals('inactive', $updatedInstance->status);
        $this->assertEquals(['environment' => 'production'], $updatedInstance->metadata);
    }

    public function test_can_delete_instance(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $instance = ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $this->service->deleteInstance('test-service', $instance->id);

        $this->assertDatabaseMissing('service_instances', [
            'id' => $instance->id,
        ]);
    }

    public function test_can_update_heartbeat(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $instance = ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $updatedInstance = $this->service->updateHeartbeat('test-service', $instance->id);

        $this->assertNotNull($updatedInstance->last_heartbeat);
    }

    public function test_can_get_instances(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8081,
        ]);

        $instances = $this->service->getInstances('test-service');

        $this->assertCount(2, $instances);
    }

    public function test_can_get_service(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $foundService = $this->service->getService('test-service');

        $this->assertEquals($service->id, $foundService->id);
    }

    public function test_can_list_services_with_filters(): void
    {
        Service::create([
            'name' => 'service-1',
            'version' => '1.0.0',
            'tags' => ['api'],
            'status' => 'active',
        ]);

        Service::create([
            'name' => 'service-2',
            'version' => '1.0.0',
            'tags' => ['api', 'test'],
            'status' => 'inactive',
        ]);

        $services = $this->service->listServices('test', 'inactive');

        $this->assertCount(1, $services);
        $this->assertEquals('service-2', $services->first()->name);
    }
} 