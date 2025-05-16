<?php

namespace Tests\Unit\Models;

use App\Models\Service;
use App\Models\ServiceInstance;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
            'description' => 'Test service',
            'metadata' => ['environment' => 'test'],
            'tags' => ['api', 'test'],
        ]);
    }

    public function test_service_can_have_instances(): void
    {
        $instance = ServiceInstance::create([
            'service_id' => $this->service->id,
            'host' => 'localhost',
            'port' => 8080,
        ]);

        $this->assertTrue($this->service->instances->contains($instance));
    }

    public function test_service_can_have_health_checks(): void
    {
        $healthCheck = HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);

        $this->assertTrue($this->service->healthChecks->contains($healthCheck));
    }

    public function test_service_is_active_by_default(): void
    {
        $this->assertTrue($this->service->isActive());
    }

    public function test_service_can_be_deactivated(): void
    {
        $this->service->update(['is_active' => false]);
        $this->assertFalse($this->service->isActive());
    }

    public function test_service_can_get_metadata(): void
    {
        $this->assertEquals('test', $this->service->getMetadata('environment'));
        $this->assertNull($this->service->getMetadata('nonexistent'));
    }

    public function test_service_can_check_tags(): void
    {
        $this->assertTrue($this->service->hasTag('api'));
        $this->assertFalse($this->service->hasTag('nonexistent'));
    }

    public function test_service_can_get_latest_health_check(): void
    {
        $healthCheck = HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);

        $this->assertEquals($healthCheck->id, $this->service->getLatestHealthCheck()->id);
    }

    public function test_service_is_healthy_when_latest_check_is_successful(): void
    {
        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);

        $this->assertTrue($this->service->isHealthy());
    }

    public function test_service_is_unhealthy_when_latest_check_failed(): void
    {
        HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 100.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $this->assertFalse($this->service->isHealthy());
    }

    public function test_can_create_service(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
            'description' => 'Test service',
            'metadata' => ['environment' => 'test'],
            'tags' => ['api', 'test'],
        ]);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertEquals('test-service', $service->name);
        $this->assertEquals('1.0.0', $service->version);
        $this->assertEquals(['environment' => 'test'], $service->metadata);
        $this->assertEquals(['api', 'test'], $service->tags);
    }

    public function test_can_check_service_health(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $this->assertFalse($service->isHealthy());

        HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100,
        ]);

        $this->assertTrue($service->isHealthy());
    }

    public function test_can_get_latest_health_check(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100,
        ]);

        $this->assertEquals($healthCheck->id, $service->getLatestHealthCheck()->id);
    }

    public function test_can_check_service_tags(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
            'tags' => ['api', 'test'],
        ]);

        $this->assertTrue($service->hasTag('api'));
        $this->assertTrue($service->hasTag('test'));
        $this->assertFalse($service->hasTag('unknown'));
    }

    public function test_can_get_metadata(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
            'metadata' => ['environment' => 'test', 'region' => 'us-east'],
        ]);

        $this->assertEquals('test', $service->getMetadata('environment'));
        $this->assertEquals('us-east', $service->getMetadata('region'));
        $this->assertNull($service->getMetadata('unknown'));
        $this->assertEquals('default', $service->getMetadata('unknown', 'default'));
    }

    public function test_can_manage_service_instances(): void
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

        $this->assertCount(1, $service->instances);
        $this->assertEquals($instance->id, $service->instances->first()->id);
    }
} 