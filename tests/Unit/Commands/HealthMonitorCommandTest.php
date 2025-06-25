<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Console\Commands\HealthMonitorCommand;
use App\Services\HealthMonitoringService;
use App\Services\HealthCheckService;
use App\Services\AlertService;
use Mockery;

class HealthMonitorCommandTest extends TestCase
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

    /** @test */
    public function it_formats_healthy_status_correctly()
    {
        $method = new \ReflectionMethod($this->command, 'formatStatus');
        $method->setAccessible(true);

        $this->assertEquals('✅ Healthy', $method->invoke($this->command, true));
        $this->assertEquals('❌ Unhealthy', $method->invoke($this->command, false));
    }

    /** @test */
    public function it_determines_correct_exit_code()
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

    /** @test */
    public function it_handles_alerts_for_unhealthy_services()
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
        
        // Assert that the method completed without throwing exceptions
        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_send_alerts_when_all_services_are_healthy()
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
        
        // Assert that the method completed without throwing exceptions
        $this->assertTrue(true);
    }

    /** @test */
    public function it_displays_health_results_in_table_format()
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

        $rows = $method->invoke($this->command, $healthStatus, false);

        $this->assertEquals(
            [
                'service1' => [
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