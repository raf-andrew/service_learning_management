<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Console\Commands\HealthMonitorCommand;
use App\Services\HealthMonitoringService;
use App\Services\HealthCheckService;
use App\Services\AlertService;
use Mockery;

class HealthMonitorCommandDisplayTest extends TestCase
{
    protected $command;
    protected $healthMonitoringService;
    protected $healthCheckService;
    protected $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthMonitoringService = Mockery::mock(HealthMonitoringService::class);
        $this->healthCheckService = Mockery::mock(HealthCheckService::class);
        $this->alertService = Mockery::mock(AlertService::class);
        $this->command = new HealthMonitorCommand(
            $this->healthMonitoringService,
            $this->healthCheckService,
            $this->alertService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_displays_health_results_in_table_format()
    {
        $method = new \ReflectionMethod($this->command, 'getTableRows');
        $method->setAccessible(true);
        $healthStatus = [
            'service1' => [
                'service' => 'service1',
                'healthy' => true,
                'last_check' => '2024-03-20 10:00:00',
                'details' => 'All systems operational'
            ]
        ];
        $rows = array_values($method->invoke($this->command, $healthStatus, false));
        $this->assertEquals(
            [
                [
                    'service1',
                    '✅ Healthy',
                    '2024-03-20 10:00:00',
                    'Use --detailed for more info'
                ]
            ],
            $rows
        );
    }
} 