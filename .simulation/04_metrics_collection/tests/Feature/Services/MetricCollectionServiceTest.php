<?php

namespace Tests\Feature\Services;

use App\Models\MetricType;
use App\Models\MetricAggregation;
use App\Services\MetricCollectionService;
use App\Services\MetricProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\MetricCollected;
use App\Events\MetricProcessed;

class MetricCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $metricCollectionService;
    protected $metricProcessingService;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->metricProcessingService = app(MetricProcessingService::class);
        $this->metricCollectionService = app(MetricCollectionService::class);
    }

    public function test_can_collect_metrics()
    {
        // Create metric type
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        // Collect metrics
        $metrics = [
            'value' => 150,
            'labels' => [
                'endpoint' => '/api/users',
                'method' => 'GET'
            ],
            'timestamp' => now()->timestamp
        ];

        $result = $this->metricCollectionService->collectMetric($metricType->name, $metrics);

        $this->assertDatabaseHas('metric_values', [
            'metric_type_id' => $metricType->id,
            'value' => 150,
            'labels' => json_encode([
                'endpoint' => '/api/users',
                'method' => 'GET'
            ])
        ]);

        Event::assertDispatched(MetricCollected::class);
    }

    public function test_can_collect_multiple_metrics()
    {
        // Create metric types
        $responseTime = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        $errorRate = MetricType::create([
            'name' => 'error_rate',
            'description' => 'API error rate',
            'unit' => 'percentage',
            'type' => 'gauge',
            'labels' => ['endpoint', 'error_type']
        ]);

        // Collect multiple metrics
        $metrics = [
            [
                'type' => 'response_time',
                'value' => 150,
                'labels' => [
                    'endpoint' => '/api/users',
                    'method' => 'GET'
                ]
            ],
            [
                'type' => 'error_rate',
                'value' => 0.5,
                'labels' => [
                    'endpoint' => '/api/users',
                    'error_type' => 'validation'
                ]
            ]
        ];

        $result = $this->metricCollectionService->collectMetrics($metrics);

        $this->assertCount(2, $result);
        $this->assertDatabaseCount('metric_values', 2);

        Event::assertDispatched(MetricCollected::class, 2);
    }

    public function test_validates_metric_labels()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        $metrics = [
            'value' => 150,
            'labels' => [
                'endpoint' => '/api/users'
                // Missing 'method' label
            ],
            'timestamp' => now()->timestamp
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->metricCollectionService->collectMetric($metricType->name, $metrics);
    }

    public function test_creates_metric_aggregations()
    {
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
        $this->metricProcessingService->processAggregations($metricType->id);

        $this->assertDatabaseHas('metric_aggregations', [
            'metric_type_id' => $metricType->id,
            'aggregation_type' => 'avg',
            'time_bucket' => '1h'
        ]);

        Event::assertDispatched(MetricProcessed::class);
    }

    public function test_handles_nonexistent_metric_type()
    {
        $metrics = [
            'value' => 150,
            'labels' => [
                'endpoint' => '/api/users',
                'method' => 'GET'
            ],
            'timestamp' => now()->timestamp
        ];

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->metricCollectionService->collectMetric('nonexistent_metric', $metrics);
    }

    public function test_validates_metric_value_type()
    {
        $metricType = MetricType::create([
            'name' => 'response_time',
            'description' => 'API response time in milliseconds',
            'unit' => 'ms',
            'type' => 'gauge',
            'labels' => ['endpoint', 'method']
        ]);

        $metrics = [
            'value' => 'invalid', // Should be numeric
            'labels' => [
                'endpoint' => '/api/users',
                'method' => 'GET'
            ],
            'timestamp' => now()->timestamp
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->metricCollectionService->collectMetric($metricType->name, $metrics);
    }
} 