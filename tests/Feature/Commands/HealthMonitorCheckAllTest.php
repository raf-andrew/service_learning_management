<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\HealthMonitoringService;
use App\Services\AlertService;
use Mockery;

class HealthMonitorCheckAllTest extends TestCase
{
    protected $healthMonitoringService;
    protected $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthMonitoringService = Mockery::mock(HealthMonitoringService::class);
        $this->alertService = Mockery::mock(AlertService::class);
        $this->app->instance(HealthMonitoringService::class, $this->healthMonitoringService);
        $this->app->instance(AlertService::class, $this->alertService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_check_all_services_health()
    {
        $this->healthMonitoringService
            ->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'service1' => [
                    'service' => 'service1',
                    'healthy' => true,
                    'last_check' => '2024-03-20 10:00:00',
                    'details' => 'All systems operational'
                ],
                'service2' => [
                    'service' => 'service2',
                    'healthy' => false,
                    'last_check' => '2024-03-20 10:00:00',
                    'details' => 'Connection timeout'
                ]
            ]);
        $this->alertService
            ->shouldReceive('sendAlert')
            ->once()
            ->with(
                'Health Check Alert',
                'The following services are unhealthy: service2',
                'warning'
            );
        $this->artisan('health:monitor')
            ->assertExitCode(1);
    }
} 