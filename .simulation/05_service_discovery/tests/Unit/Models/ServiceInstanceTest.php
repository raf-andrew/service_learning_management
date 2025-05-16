<?php

namespace Tests\Unit\Models;

use App\Models\Service;
use App\Models\ServiceInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceInstanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_service_instance(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $instance = ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
            'status' => 'active',
            'metadata' => ['environment' => 'test'],
        ]);

        $this->assertInstanceOf(ServiceInstance::class, $instance);
        $this->assertEquals('localhost', $instance->host);
        $this->assertEquals(8080, $instance->port);
        $this->assertEquals('active', $instance->status);
        $this->assertEquals(['environment' => 'test'], $instance->metadata);
    }

    public function test_can_check_instance_activity(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $instance = ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
            'is_active' => true,
        ]);

        $this->assertTrue($instance->isActive());

        $instance->update(['is_active' => false]);
        $this->assertFalse($instance->isActive());
    }

    public function test_can_get_metadata(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $instance = ServiceInstance::create([
            'service_id' => $service->id,
            'host' => 'localhost',
            'port' => 8080,
            'metadata' => ['environment' => 'test', 'region' => 'us-east'],
        ]);

        $this->assertEquals('test', $instance->getMetadata('environment'));
        $this->assertEquals('us-east', $instance->getMetadata('region'));
        $this->assertNull($instance->getMetadata('unknown'));
        $this->assertEquals('default', $instance->getMetadata('unknown', 'default'));
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

        $this->assertNull($instance->last_heartbeat);

        $instance->updateHeartbeat();
        $this->assertNotNull($instance->last_heartbeat);
    }

    public function test_belongs_to_service(): void
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

        $this->assertInstanceOf(Service::class, $instance->service);
        $this->assertEquals($service->id, $instance->service->id);
    }

    public function test_instance_cannot_have_duplicate_host_port(): void
    {
        $this->expectException(\Exception::class);

        ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);
    }
} 