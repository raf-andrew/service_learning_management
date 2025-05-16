<?php

namespace Tests\Unit\Services;

use App\Models\MetricType;
use App\Services\MetricCollectionService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class MetricCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private MetricCollectionService $service;
    private MetricType $cpuMetricType;
    private MetricType $memoryMetricType;
    private MetricType $diskMetricType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MetricCollectionService();

        $this->cpuMetricType = MetricType::create([
            'name' => 'cpu_usage',
            'description' => 'CPU usage percentage',
            'unit' => 'percent',
            'data_type' => 'float',
            'validation_rules' => [
                ['type' => 'min', 'value' => 0],
                ['type' => 'max', 'value' => 100]
            ],
            'aggregation_methods' => ['average', 'max', 'min']
        ]);

        $this->memoryMetricType = MetricType::create([
            'name' => 'memory_usage',
            'description' => 'Memory usage percentage',
            'unit' => 'percent',
            'data_type' => 'float',
            'validation_rules' => [
                ['type' => 'min', 'value' => 0],
                ['type' => 'max', 'value' => 100]
            ],
            'aggregation_methods' => ['average', 'max', 'min']
        ]);

        $this->diskMetricType = MetricType::create([
            'name' => 'disk_usage',
            'description' => 'Disk usage percentage',
            'unit' => 'percent',
            'data_type' => 'float',
            'validation_rules' => [
                ['type' => 'min', 'value' => 0],
                ['type' => 'max', 'value' => 100]
            ],
            'aggregation_methods' => ['average', 'max', 'min']
        ]);
    }

    public function test_service_can_register_collector(): void
    {
        $this->service->registerCollector('test_metric', function() {
            return 42;
        });

        $metrics = $this->service->collectMetrics();
        $this->assertInstanceOf(Collection::class, $metrics);
        $this->assertCount(0, $metrics); // No metrics because no MetricType exists for 'test_metric'
    }

    public function test_service_collects_system_metrics(): void
    {
        $metrics = $this->service->collectSystemMetrics();
        
        $this->assertInstanceOf(Collection::class, $metrics);
        $this->assertCount(3, $metrics);

        $cpuMetric = $metrics->firstWhere('metric_type_id', $this->cpuMetricType->id);
        $this->assertNotNull($cpuMetric);
        $this->assertGreaterThanOrEqual(0, $cpuMetric->value);
        $this->assertLessThanOrEqual(100, $cpuMetric->value);

        $memoryMetric = $metrics->firstWhere('metric_type_id', $this->memoryMetricType->id);
        $this->assertNotNull($memoryMetric);
        $this->assertGreaterThanOrEqual(0, $memoryMetric->value);
        $this->assertLessThanOrEqual(100, $memoryMetric->value);

        $diskMetric = $metrics->firstWhere('metric_type_id', $this->diskMetricType->id);
        $this->assertNotNull($diskMetric);
        $this->assertGreaterThanOrEqual(0, $diskMetric->value);
        $this->assertLessThanOrEqual(100, $diskMetric->value);
    }

    public function test_service_handles_invalid_metric_values(): void
    {
        $this->service->registerCollector('cpu_usage', function() {
            return 150; // Invalid value > 100
        });

        $metrics = $this->service->collectMetrics();
        $this->assertCount(0, $metrics);
    }

    public function test_service_handles_collector_exceptions(): void
    {
        $this->service->registerCollector('cpu_usage', function() {
            throw new \Exception('Test exception');
        });

        $metrics = $this->service->collectMetrics();
        $this->assertCount(0, $metrics);
    }

    public function test_service_adds_metadata_to_metrics(): void
    {
        $metrics = $this->service->collectSystemMetrics();
        $metric = $metrics->first();

        $this->assertArrayHasKey('collector', $metric->labels);
        $this->assertArrayHasKey('host', $metric->labels);
        $this->assertArrayHasKey('environment', $metric->labels);
    }
} 