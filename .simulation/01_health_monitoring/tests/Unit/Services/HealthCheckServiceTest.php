<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\ServiceHealth;
use App\Services\HealthCheckService;
use App\Services\MetricService;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    private $healthCheckService;
    private $metricService;
    private $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricService = Mockery::mock(MetricService::class);
        $this->alertService = Mockery::mock(AlertService::class);
        $this->healthCheckService = new HealthCheckService($this->metricService, $this->alertService);
    }

    public function test_check_all_services()
    {
        // Create test services
        $service1 = ServiceHealth::create([
            'service_name' => 'service1',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        $service2 = ServiceHealth::create([
            'service_name' => 'service2',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.2
        ]);

        // Mock metric collection
        $this->metricService->shouldReceive('collectMetrics')
            ->twice()
            ->andReturn(collect([
                [
                    'name' => 'cpu_usage',
                    'value' => 45.5,
                    'unit' => 'percent',
                    'threshold' => 80.0
                ]
            ]));

        $result = $this->healthCheckService->checkAllServices();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_status', $result);
        $this->assertArrayHasKey('services', $result);
        $this->assertCount(2, $result['services']);
    }

    public function test_check_service_success()
    {
        $service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        // Mock metric collection
        $this->metricService->shouldReceive('collectMetrics')
            ->once()
            ->andReturn(collect([
                [
                    'name' => 'cpu_usage',
                    'value' => 45.5,
                    'unit' => 'percent',
                    'threshold' => 80.0
                ]
            ]));

        $result = $this->healthCheckService->checkService($service);

        $this->assertIsArray($result);
        $this->assertEquals('healthy', $result['status']);
        $this->assertEquals('test_service', $result['service_name']);
    }

    public function test_check_service_failure()
    {
        $service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        // Mock metric collection to throw an exception
        $this->metricService->shouldReceive('collectMetrics')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $result = $this->healthCheckService->checkService($service);

        $this->assertIsArray($result);
        $this->assertEquals('unhealthy', $result['status']);
        $this->assertEquals('critical', $result['health_status']);
        $this->assertEquals('Test error', $result['error']);
    }

    public function test_process_metrics()
    {
        $service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        $metrics = collect([
            [
                'name' => 'cpu_usage',
                'value' => 85.5,
                'unit' => 'percent',
                'threshold' => 80.0
            ],
            [
                'name' => 'memory_usage',
                'value' => 75.5,
                'unit' => 'percent',
                'threshold' => 85.0
            ]
        ]);

        // Mock alert creation
        $this->alertService->shouldReceive('createAlert')
            ->once()
            ->withArgs(function ($service, $alertData) {
                return $alertData['type'] === 'cpu_usage' && $alertData['level'] === 'critical';
            });

        $this->healthCheckService->processMetrics($service, $metrics);

        $this->assertDatabaseHas('metrics', [
            'service_health_id' => $service->id,
            'name' => 'cpu_usage',
            'value' => 85.5
        ]);

        $this->assertDatabaseHas('metrics', [
            'service_health_id' => $service->id,
            'name' => 'memory_usage',
            'value' => 75.5
        ]);
    }

    public function test_run_load_test()
    {
        // Create test service
        ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);

        // Mock metric collection
        $this->metricService->shouldReceive('collectMetrics')
            ->times(100)
            ->andReturn(collect([
                [
                    'name' => 'cpu_usage',
                    'value' => 45.5,
                    'unit' => 'percent',
                    'threshold' => 80.0
                ]
            ]));

        $result = $this->healthCheckService->runLoadTest();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertArrayHasKey('requests_per_second', $result);
        $this->assertArrayHasKey('error_rate', $result);
        $this->assertIsFloat($result['response_time']);
        $this->assertIsFloat($result['requests_per_second']);
        $this->assertIsFloat($result['error_rate']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 