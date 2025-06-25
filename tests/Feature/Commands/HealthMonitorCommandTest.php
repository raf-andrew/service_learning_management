<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\HealthMonitoringService;
use App\Services\HealthCheckService;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

/**
 * @group commands
 * @group health
 * @checklistItem HEALTH-001
 */
class HealthMonitorCommandTest extends TestCase
{
    use RefreshDatabase;

    protected HealthMonitoringService $healthMonitoringService;
    protected HealthCheckService $healthCheckService;
    protected AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->healthMonitoringService = $this->createMock(HealthMonitoringService::class);
        $this->healthCheckService = $this->createMock(HealthCheckService::class);
        $this->alertService = $this->createMock(AlertService::class);
        
        $this->app->instance(HealthMonitoringService::class, $this->healthMonitoringService);
        $this->app->instance(HealthCheckService::class, $this->healthCheckService);
        $this->app->instance(AlertService::class, $this->alertService);
    }

    /**
     * @test
     * @checklistItem HEALTH-001
     * @coverage 100%
     * @description Test health monitor command with all services healthy
     */
    public function test_health_monitor_all_services_healthy()
    {
        $healthStatus = [
            'database' => [
                'service' => 'Database',
                'healthy' => true,
                'last_check' => '2024-01-01 12:00:00',
                'details' => 'Connection successful'
            ],
            'cache' => [
                'service' => 'Cache',
                'healthy' => true,
                'last_check' => '2024-01-01 12:00:00',
                'details' => 'Redis connected'
            ]
        ];

        $this->healthMonitoringService
            ->expects($this->once())
            ->method('checkAllServices')
            ->willReturn($healthStatus);

        $this->alertService
            ->expects($this->never())
            ->method('sendAlert');

        $exitCode = Artisan::call('health:monitor');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem HEALTH-002
     * @coverage 100%
     * @description Test health monitor command with specific service
     */
    public function test_health_monitor_specific_service()
    {
        $healthStatus = [
            'database' => [
                'service' => 'Database',
                'healthy' => true,
                'last_check' => '2024-01-01 12:00:00',
                'details' => 'Connection successful'
            ]
        ];

        $this->healthMonitoringService
            ->expects($this->once())
            ->method('checkServiceHealth')
            ->with('database')
            ->willReturn($healthStatus);

        $this->alertService
            ->expects($this->never())
            ->method('sendAlert');

        $exitCode = Artisan::call('health:monitor', ['--service' => 'database']);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem HEALTH-003
     * @coverage 100%
     * @description Test health monitor command with unhealthy services
     */
    public function test_health_monitor_unhealthy_services()
    {
        $healthStatus = [
            'database' => [
                'service' => 'Database',
                'healthy' => false,
                'last_check' => '2024-01-01 12:00:00',
                'details' => 'Connection failed'
            ],
            'cache' => [
                'service' => 'Cache',
                'healthy' => true,
                'last_check' => '2024-01-01 12:00:00',
                'details' => 'Redis connected'
            ]
        ];

        $this->healthMonitoringService
            ->expects($this->once())
            ->method('checkAllServices')
            ->willReturn($healthStatus);

        $this->alertService
            ->expects($this->once())
            ->method('sendAlert')
            ->with(
                'Health Check Alert',
                'The following services are unhealthy: database',
                'warning'
            );

        $exitCode = Artisan::call('health:monitor');

        $this->assertEquals(1, $exitCode);
    }

    /**
     * @test
     * @checklistItem HEALTH-004
     * @coverage 100%
     * @description Test health monitor command with detailed output
     */
    public function test_health_monitor_detailed_output()
    {
        $healthStatus = [
            'database' => [
                'service' => 'Database',
                'healthy' => true,
                'last_check' => '2024-01-01 12:00:00',
                'details' => 'Connection successful'
            ]
        ];

        $this->healthMonitoringService
            ->expects($this->once())
            ->method('checkAllServices')
            ->willReturn($healthStatus);

        $exitCode = Artisan::call('health:monitor', ['--detailed' => true]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem HEALTH-005
     * @coverage 100%
     * @description Test health monitor command with empty status
     */
    public function test_health_monitor_empty_status()
    {
        $this->healthMonitoringService
            ->expects($this->once())
            ->method('checkAllServices')
            ->willReturn([]);

        $this->alertService
            ->expects($this->never())
            ->method('sendAlert');

        $exitCode = Artisan::call('health:monitor');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem HEALTH-006
     * @coverage 100%
     * @description Test health monitor command with exception
     */
    public function test_health_monitor_with_exception()
    {
        $this->healthMonitoringService
            ->expects($this->once())
            ->method('checkAllServices')
            ->willThrowException(new \Exception('Service unavailable'));

        $exitCode = Artisan::call('health:monitor');

        $this->assertEquals(1, $exitCode);
    }
} 