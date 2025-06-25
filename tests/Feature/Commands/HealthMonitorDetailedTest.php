<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\HealthMonitoringService;
use Mockery;

class HealthMonitorDetailedTest extends TestCase
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

    public function test_it_shows_detailed_health_information_when_requested()
    {
        $mockHealthStatus = [
            [
                'service' => 'service1',
                'healthy' => true,
                'last_check' => '2024-03-20 10:00:00',
                'details' => 'All systems operational'
            ]
        ];
        $this->healthMonitoringService
            ->shouldReceive('checkAllServices')
            ->once()
            ->andReturn($mockHealthStatus);
        $this->artisan('health:monitor --detailed')
            ->expectsTable(
                ['Service', 'Status', 'Last Check', 'Details'],
                [
                    ['service1', '✅ Healthy', '2024-03-20 10:00:00', 'All systems operational']
                ]
            )
            ->assertExitCode(0);
    }
} 