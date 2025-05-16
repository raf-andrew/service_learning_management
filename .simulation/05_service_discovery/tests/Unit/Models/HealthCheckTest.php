<?php

namespace Tests\Unit\Models;

use App\Models\Service;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;
    private HealthCheck $healthCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'test-service',
            'version' => '1.0.0',
        ]);

        $this->healthCheck = HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'success',
            'response_time' => 100.5,
            'check_time' => now(),
        ]);
    }

    public function test_health_check_belongs_to_service(): void
    {
        $this->assertEquals($this->service->id, $this->healthCheck->service->id);
    }

    public function test_health_check_can_check_successful_response(): void
    {
        $this->assertTrue($this->healthCheck->isSuccessful());
    }

    public function test_health_check_can_check_failed_response(): void
    {
        $failedCheck = HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 100.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $this->assertFalse($failedCheck->isSuccessful());
    }

    public function test_health_check_can_format_response_time(): void
    {
        $this->assertEquals('100.50ms', $this->healthCheck->formatResponseTime());
    }

    public function test_health_check_can_get_error_message(): void
    {
        $failedCheck = HealthCheck::create([
            'service_id' => $this->service->id,
            'status' => 'failed',
            'response_time' => 100.5,
            'error_message' => 'Connection timeout',
            'check_time' => now(),
        ]);

        $this->assertEquals('Connection timeout', $failedCheck->getErrorMessage());
    }
} 