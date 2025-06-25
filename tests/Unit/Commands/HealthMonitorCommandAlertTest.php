<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Console\Commands\HealthMonitorCommand;
use App\Services\HealthMonitoringService;
use App\Services\HealthCheckService;
use App\Services\AlertService;
use Mockery;

class HealthMonitorCommandAlertTest extends TestCase
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

    public function test_it_handles_alerts_for_unhealthy_services()
    {
        $method = new \ReflectionMethod($this->command, 'handleAlerts');
        $method->setAccessible(true);
        $healthStatus = [
            'service1' => ['healthy' => true],
            'service2' => ['healthy' => false],
            'service3' => ['healthy' => false]
        ];
        $this->alertService
            ->shouldReceive('sendAlert')
            ->once()
            ->with(
                'Health Check Alert',
                'The following services are unhealthy: service2, service3',
                'warning'
            );
        $method->invoke($this->command, $healthStatus, $this->alertService);
    }

    public function test_it_does_not_send_alerts_when_all_services_are_healthy()
    {
        $method = new \ReflectionMethod($this->command, 'handleAlerts');
        $method->setAccessible(true);
        $healthStatus = [
            'service1' => ['healthy' => true],
            'service2' => ['healthy' => true]
        ];
        $this->alertService
            ->shouldNotReceive('sendAlert');
        $method->invoke($this->command, $healthStatus, $this->alertService);
    }
} 