<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Console\Commands\HealthMonitorCommand;
use App\Services\HealthMonitoringService;
use App\Services\HealthCheckService;
use App\Services\AlertService;
use Mockery;

class HealthMonitorCommandFormatTest extends TestCase
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

    public function test_it_formats_healthy_status_correctly()
    {
        $method = new \ReflectionMethod($this->command, 'formatStatus');
        $method->setAccessible(true);
        $this->assertEquals('✅ Healthy', $method->invoke($this->command, true));
        $this->assertEquals('❌ Unhealthy', $method->invoke($this->command, false));
    }

    public function test_it_determines_correct_exit_code()
    {
        $method = new \ReflectionMethod($this->command, 'determineExitCode');
        $method->setAccessible(true);
        $allHealthy = [
            ['healthy' => true],
            ['healthy' => true]
        ];
        $someUnhealthy = [
            ['healthy' => true],
            ['healthy' => false]
        ];
        $this->assertEquals(0, $method->invoke($this->command, $allHealthy));
        $this->assertEquals(1, $method->invoke($this->command, $someUnhealthy));
    }
} 