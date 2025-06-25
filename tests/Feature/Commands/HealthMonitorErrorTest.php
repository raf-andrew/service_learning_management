<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\HealthMonitoringService;
use Mockery;

class HealthMonitorErrorTest extends TestCase
{
    protected $healthMonitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthMonitoringService = Mockery::mock(HealthMonitoringService::class);
        $this->app->instance(HealthMonitoringService::class, $this->healthMonitoringService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_handles_service_check_failures_gracefully()
    {
        $this->healthMonitoringService
            ->shouldReceive('checkAllServices')
            ->once()
            ->andThrow(new \Exception('Service check failed'));
        $this->artisan('health:monitor')
            ->expectsOutput('Error: Service check failed')
            ->assertExitCode(1);
    }
} 