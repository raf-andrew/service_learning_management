<?php

namespace Tests\Unit\Models;

use App\Models\Service;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_health_check(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100.5,
            'check_time' => now(),
            'metadata' => ['environment' => 'test'],
        ]);

        $this->assertInstanceOf(HealthCheck::class, $healthCheck);
        $this->assertEquals('healthy', $healthCheck->status);
        $this->assertEquals(100.5, $healthCheck->response_time);
        $this->assertEquals(['environment' => 'test'], $healthCheck->metadata);
    }

    public function test_can_check_health_status(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);

        $this->assertTrue($healthCheck->isSuccessful());

        $healthCheck->update(['status' => 'unhealthy']);
        $this->assertFalse($healthCheck->isSuccessful());
    }

    public function test_can_get_metadata(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100.5,
            'check_time' => now(),
            'metadata' => ['environment' => 'test', 'region' => 'us-east'],
        ]);

        $this->assertEquals('test', $healthCheck->getMetadata('environment'));
        $this->assertEquals('us-east', $healthCheck->getMetadata('region'));
        $this->assertNull($healthCheck->getMetadata('unknown'));
        $this->assertEquals('default', $healthCheck->getMetadata('unknown', 'default'));
    }

    public function test_can_get_formatted_response_time(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);

        $this->assertEquals('100.50ms', $healthCheck->getFormattedResponseTime());
    }

    public function test_can_get_status_with_details(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100.5,
            'check_time' => now(),
            'error_message' => 'Test error',
        ]);

        $statusDetails = $healthCheck->getStatusWithDetails();

        $this->assertEquals('healthy', $statusDetails['status']);
        $this->assertEquals('100.50ms', $statusDetails['response_time']);
        $this->assertEquals('Test error', $statusDetails['error_message']);
        $this->assertIsString($statusDetails['check_time']);
    }

    public function test_belongs_to_service(): void
    {
        $service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $healthCheck = HealthCheck::create([
            'service_id' => $service->id,
            'status' => 'healthy',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);

        $this->assertInstanceOf(Service::class, $healthCheck->service);
        $this->assertEquals($service->id, $healthCheck->service->id);
    }
} 