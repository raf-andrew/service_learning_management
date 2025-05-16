<?php

namespace Tests\Simulations\HealthMonitoring;

use Tests\TestCase;
use App\Simulations\HealthMonitoring\HealthMonitoringSimulation;
use App\Services\HealthCheckService;
use App\Services\MetricService;
use App\Services\AlertService;
use Illuminate\Support\Facades\Log;
use Mockery;

class HealthMonitoringSimulationTest extends TestCase
{
    protected $healthCheckService;
    protected $metricService;
    protected $alertService;
    protected $simulation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->healthCheckService = Mockery::mock(HealthCheckService::class);
        $this->metricService = Mockery::mock(MetricService::class);
        $this->alertService = Mockery::mock(AlertService::class);

        // Create simulation instance
        $this->simulation = new HealthMonitoringSimulation(
            $this->healthCheckService,
            $this->metricService,
            $this->alertService
        );
    }

    public function test_initialization()
    {
        $this->simulation->initialize();
        $results = $this->simulation->getResults();

        $this->assertArrayHasKey('web_server', $results['services']);
        $this->assertArrayHasKey('database', $results['services']);
        $this->assertArrayHasKey('cache', $results['services']);
    }

    public function test_service_health_check()
    {
        $this->healthCheckService
            ->shouldReceive('checkService')
            ->with('web_server', Mockery::any())
            ->andReturn(['status' => 'healthy']);

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertEquals('healthy', $results['services']['web_server']['status']);
    }

    public function test_metrics_collection()
    {
        $testMetrics = [
            'cpu' => 45.5,
            'memory' => 60.2,
            'disk' => 75.8
        ];

        $this->metricService
            ->shouldReceive('collectMetrics')
            ->with('web_server')
            ->andReturn($testMetrics);

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertEquals($testMetrics, $results['metrics']['web_server']);
    }

    public function test_alert_processing()
    {
        $testMetrics = [
            'cpu' => 95.5,
            'memory' => 90.2,
            'disk' => 85.8
        ];

        $testAlerts = [
            [
                'type' => 'cpu',
                'level' => 'critical',
                'message' => 'CPU usage is above threshold'
            ]
        ];

        $this->metricService
            ->shouldReceive('collectMetrics')
            ->with('web_server')
            ->andReturn($testMetrics);

        $this->alertService
            ->shouldReceive('processMetrics')
            ->with('web_server', $testMetrics)
            ->andReturn($testAlerts);

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertEquals($testAlerts, $results['alerts']['web_server']);
    }

    public function test_error_handling()
    {
        $this->healthCheckService
            ->shouldReceive('checkService')
            ->andThrow(new \Exception('Service unavailable'));

        $this->simulation->initialize();
        $result = $this->simulation->run();

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 