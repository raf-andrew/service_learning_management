<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\ServiceHealth;
use App\Services\MetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricServiceTest extends TestCase
{
    use RefreshDatabase;

    private $metricService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricService = new MetricService();
        
        $this->service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);
    }

    public function test_collect_metrics()
    {
        $metrics = $this->metricService->collectMetrics($this->service);

        $this->assertIsObject($metrics);
        $this->assertTrue($metrics->isNotEmpty());
        
        $metrics->each(function ($metric) {
            $this->assertArrayHasKey('name', $metric);
            $this->assertArrayHasKey('value', $metric);
            $this->assertArrayHasKey('unit', $metric);
            $this->assertArrayHasKey('threshold', $metric);
        });
    }

    public function test_store_metrics()
    {
        $metrics = [
            [
                'service_health_id' => $this->service->id,
                'name' => 'cpu_usage',
                'value' => 45.5,
                'unit' => 'percent',
                'threshold' => 80.0
            ],
            [
                'service_health_id' => $this->service->id,
                'name' => 'memory_usage',
                'value' => 75.5,
                'unit' => 'percent',
                'threshold' => 85.0
            ]
        ];

        $this->metricService->storeMetrics($metrics);

        $this->assertDatabaseHas('metrics', [
            'service_health_id' => $this->service->id,
            'name' => 'cpu_usage',
            'value' => 45.5
        ]);

        $this->assertDatabaseHas('metrics', [
            'service_health_id' => $this->service->id,
            'name' => 'memory_usage',
            'value' => 75.5
        ]);
    }

    public function test_get_stored_metrics()
    {
        // Create some test metrics
        $this->service->metrics()->create([
            'name' => 'cpu_usage',
            'value' => 45.5,
            'unit' => 'percent',
            'threshold' => 80.0,
            'timestamp' => now()
        ]);

        $this->service->metrics()->create([
            'name' => 'memory_usage',
            'value' => 75.5,
            'unit' => 'percent',
            'threshold' => 85.0,
            'timestamp' => now()
        ]);

        $metrics = $this->metricService->getStoredMetrics();

        $this->assertIsObject($metrics);
        $this->assertCount(2, $metrics);
        
        $metrics->each(function ($metric) {
            $this->assertIsObject($metric);
            $this->assertNotNull($metric->serviceHealth);
            $this->assertEquals($this->service->id, $metric->service_health_id);
        });
    }

    public function test_get_cpu_usage()
    {
        $cpuUsage = $this->metricService->getCpuUsage();
        
        $this->assertIsFloat($cpuUsage);
        $this->assertGreaterThanOrEqual(0, $cpuUsage);
        $this->assertLessThanOrEqual(100, $cpuUsage);
    }

    public function test_get_memory_usage()
    {
        $memoryUsage = $this->metricService->getMemoryUsage();
        
        $this->assertIsFloat($memoryUsage);
        $this->assertGreaterThanOrEqual(0, $memoryUsage);
        $this->assertLessThanOrEqual(100, $memoryUsage);
    }

    public function test_get_disk_usage()
    {
        $diskUsage = $this->metricService->getDiskUsage();
        
        $this->assertIsFloat($diskUsage);
        $this->assertGreaterThanOrEqual(0, $diskUsage);
        $this->assertLessThanOrEqual(100, $diskUsage);
    }

    public function test_get_response_time()
    {
        $responseTime = $this->metricService->getResponseTime($this->service);
        
        $this->assertIsFloat($responseTime);
        $this->assertEquals(100, $responseTime); // 0.1 seconds = 100 milliseconds
    }
} 