<?php

namespace Tests\Feature\Services;

use App\Models\MetricType;
use App\Models\MetricAggregation;
use App\Services\MetricProcessingService;
use App\Services\MetricCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\MetricProcessed;

class MetricProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $metricProcessingService;
    protected $metricCollectionService;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->metricProcessingService = app(MetricProcessingService::class);
        $this->metricCollectionService = app(MetricCollectionService::class);
    }

    public function test_can_process_metric_aggregations()
    {
        // Create metric type
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        // Collect metrics over time
        for ($i = 0; $i < 10; $i++) {
            $this->metricCollectionService->collectMetric($metricType->name, [
                'value' => 100 + $i,
                'labels' => [
                    'endpoint' => '/api/users',
                    'method' => 'GET'
                ],
                'timestamp' => now()->addMinutes($i)->timestamp
            ]);
        }

        // Process aggregations
        $result = $this->metricProcessingService->processAggregations($metricType->id);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'aggregation_type' => 'avg',
            'time_bucket' => '1h'
        ]);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'aggregation_type' => 'min',
            'time_bucket' => '1h'
        ]);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'aggregation_type' => 'max',
            'time_bucket' => '1h'
        ]);

        Event::assertDispatched(MetricProcessed::class);
    }

    public function test_can_process_aggregations_with_different_time_buckets()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        // Collect metrics over a longer period
        for ($i = 0; $i < 24; $i++) {
            $this->metricCollectionService->collectMetric($metricType->name, [
                'value' => 100 + $i,
                'labels' => [
                    'endpoint' => '/api/users',
                    'method' => 'GET'
                ],
                'timestamp' => now()->addHours($i)->timestamp
            ]);
        }

        // Process aggregations with different time buckets
        $result = $this->metricProcessingService->processAggregations($metricType->id, ['1h', '6h', '24h']);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'time_bucket' => '1h'
        ]);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'time_bucket' => '6h'
        ]);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'time_bucket' => '24h'
        ]);

        Event::assertDispatched(MetricProcessed::class);
    }

    public function test_can_process_aggregations_with_custom_aggregation_types()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        // Collect metrics
        for ($i = 0; $i < 10; $i++) {
            $this->metricCollectionService->collectMetric($metricType->name, [
                'value' => 100 + $i,
                'labels' => [
                    'endpoint' => '/api/users',
                    'method' => 'GET'
                ],
                'timestamp' => now()->addMinutes($i)->timestamp
            ]);
        }

        // Process aggregations with custom types
        $result = $this->metricProcessingService->processAggregations(
            $metricType->id,
            ['1h'],
            ['p95', 'p99']
        );

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'aggregation_type' => 'p95',
            'time_bucket' => '1h'
        ]);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'aggregation_type' => 'p99',
            'time_bucket' => '1h'
        ]);

        Event::assertDispatched(MetricProcessed::class);
    }

    public function test_handles_empty_metric_data()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        $result = $this->metricProcessingService->processAggregations($metricType->id);

        $this->assertEmpty($result);
        $this->assertDatabaseMissing('metric_aggregations', [
            'metric_type_id' => $metricType->id
        ]);

        Event::assertNotDispatched(MetricProcessed::class);
    }

    public function test_validates_aggregation_types()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->metricProcessingService->processAggregations(
            $metricType->id,
            ['1h'],
            ['invalid_type']
        );
    }

    public function test_validates_time_buckets()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->metricProcessingService->processAggregations(
            $metricType->id,
            ['invalid_bucket']
        );
    }
} 