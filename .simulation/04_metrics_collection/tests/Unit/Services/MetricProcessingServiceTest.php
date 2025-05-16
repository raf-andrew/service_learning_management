<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Metric;
use App\Models\MetricType;
use App\Services\MetricProcessingService;
use App\Services\MetricCollectionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;

class MetricProcessingServiceTest extends TestCase
{
    private MetricProcessingService $service;
    private MetricCollectionService $collectionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->collectionService = Mockery::mock(MetricCollectionService::class);
        $this->service = new MetricProcessingService($this->collectionService);
    }

    public function test_process_metrics_handles_empty_collection()
    {
        $this->collectionService
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn(collect());

        $result = $this->service->processMetrics();
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_process_metrics_handles_valid_metrics()
    {
        $metricType = MetricType::factory()->create([
            'data_type' => 'float'
        ]);

        $metric = Metric::factory()->create([
            'metric_type_id' => $metricType->id,
            'value' => 42.5
        ]);

        $this->collectionService
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn(collect([$metric]));

        $result = $this->service->processMetrics();
        
        $this->assertCount(1, $result);
        $this->assertEquals(42.5, $result->first()->value);
    }

    public function test_process_metrics_handles_invalid_metric_type()
    {
        $metric = Metric::factory()->create([
            'metric_type_id' => 999999, // Non-existent type
            'value' => 42.5
        ]);

        $this->collectionService
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn(collect([$metric]));

        $result = $this->service->processMetrics();
        
        $this->assertCount(0, $result);
    }

    public function test_transform_value_handles_different_data_types()
    {
        $floatType = MetricType::factory()->create(['data_type' => 'float']);
        $intType = MetricType::factory()->create(['data_type' => 'integer']);
        $boolType = MetricType::factory()->create(['data_type' => 'boolean']);

        $this->assertEquals(42.5, $this->invokePrivateMethod($this->service, 'transformValue', ['42.5', $floatType]));
        $this->assertEquals(42.0, $this->invokePrivateMethod($this->service, 'transformValue', ['42', $intType]));
        $this->assertEquals(1.0, $this->invokePrivateMethod($this->service, 'transformValue', ['true', $boolType]));
        $this->assertEquals(0.0, $this->invokePrivateMethod($this->service, 'transformValue', ['false', $boolType]));
    }

    public function test_is_anomaly_detects_outliers()
    {
        $metricType = MetricType::factory()->create();
        
        // Create metrics with a mean of 100 and std dev of 10
        Metric::factory()->count(10)->create([
            'metric_type_id' => $metricType->id,
            'value' => 100
        ]);

        // Test value within normal range
        $this->assertFalse($this->invokePrivateMethod($this->service, 'isAnomaly', [105, $metricType]));

        // Test value outside normal range (more than 3 std devs)
        $this->assertTrue($this->invokePrivateMethod($this->service, 'isAnomaly', [150, $metricType]));
    }

    public function test_analyze_metrics_returns_correct_statistics()
    {
        $metricType = MetricType::factory()->create();
        
        Metric::factory()->create([
            'metric_type_id' => $metricType->id,
            'value' => 100,
            'created_at' => now()->subHours(2)
        ]);

        Metric::factory()->create([
            'metric_type_id' => $metricType->id,
            'value' => 120,
            'created_at' => now()->subHour()
        ]);

        $result = $this->service->analyzeMetrics($metricType);

        $this->assertEquals(2, $result['count']);
        $this->assertEquals(100, $result['min']);
        $this->assertEquals(120, $result['max']);
        $this->assertEquals(110, $result['avg']);
        $this->assertEquals('up', $result['trend']['direction']);
        $this->assertEquals(20, $result['trend']['change']);
    }

    public function test_analyze_metrics_with_filters()
    {
        $metricType = MetricType::factory()->create();
        
        Metric::factory()->create([
            'metric_type_id' => $metricType->id,
            'value' => 100,
            'labels' => ['environment' => 'production']
        ]);

        Metric::factory()->create([
            'metric_type_id' => $metricType->id,
            'value' => 120,
            'labels' => ['environment' => 'staging']
        ]);

        $result = $this->service->analyzeMetrics($metricType, ['environment' => 'production']);

        $this->assertEquals(1, $result['count']);
        $this->assertEquals(100, $result['min']);
        $this->assertEquals(100, $result['max']);
    }

    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 