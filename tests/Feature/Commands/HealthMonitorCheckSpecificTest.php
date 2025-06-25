<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\HealthMonitoringService;
use Mockery;

class HealthMonitorCheckSpecificTest extends TestCase
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

    public function test_it_can_check_specific_service_health()
    {
        $this->healthMonitoringService
            ->shouldReceive('checkServiceHealth')
            ->with('service1')
            ->once()
            ->andReturn([
                [
                    'service' => 'service1',
                    'healthy' => true,
                    'last_check' => '2024-03-20 10:00:00',
                    'details' => 'All systems operational'
                ]
            ]);
        $this->artisan('health:monitor --service=service1')
            ->assertExitCode(0);
    }
} 