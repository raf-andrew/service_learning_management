<?php

namespace Tests\Feature\Services;

use App\Models\MetricAggregation;
use App\Models\MetricType;
use App\Services\MetricVisualizationService;
use App\Services\MetricCollectionService;
use App\Services\MetricProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class MetricVisualizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $visualizationService;
    protected $collectionService;
    protected $processingService;
    protected $metricType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collectionService = $this->mock(MetricCollectionService::class);
        $this->processingService = $this->mock(MetricProcessingService::class);
        $this->visualizationService = new MetricVisualizationService(
            $this->collectionService,
            $this->processingService
        );

        $this->metricType = MetricType::create([
            'name' => 'test_metric',
            'description' => 'Test Metric',
            'unit' => 'count',
            'type' => 'gauge'
        ]);
    }

    public function test_can_get_time_series_data()
    {
        $metricName = 'test_metric';
        $timeRange = '1h';
        
        MetricAggregation::create([
            'metric_name' => $metricName,
            'time_range' => $timeRange,
            'timestamp' => now(),
            'value' => 100,
            'labels' => ['env' => 'test'],
        ]);

        $data = $this->visualizationService->getTimeSeriesData($metricName, $timeRange);

        $this->assertCount(1, $data);
        $this->assertEquals(100, $data->first()['value']);
        $this->assertEquals(['env' => 'test'], $data->first()['labels']);
    }

    public function test_can_get_metric_statistics()
    {
        $metricName = 'test_metric';
        
        MetricAggregation::create([
            'metric_name' => $metricName,
            'value' => 100,
            'labels' => ['env' => 'test'],
        ]);

        MetricAggregation::create([
            'metric_name' => $metricName,
            'value' => 200,
            'labels' => ['env' => 'test'],
        ]);

        $stats = $this->visualizationService->getMetricStatistics($metricName);

        $this->assertEquals(100, $stats['min']);
        $this->assertEquals(200, $stats['max']);
        $this->assertEquals(150, $stats['avg']);
        $this->assertEquals(300, $stats['sum']);
        $this->assertEquals(2, $stats['count']);
    }

    public function test_can_get_comparison_data()
    {
        $metricName = 'test_metric';
        
        // Current period data
        MetricAggregation::create([
            'metric_name' => $metricName,
            'time_range' => '1h',
            'value' => 200,
            'timestamp' => now(),
        ]);

        // Previous period data
        MetricAggregation::create([
            'metric_name' => $metricName,
            'time_range' => '1d',
            'value' => 100,
            'timestamp' => now()->subDay(),
        ]);

        $comparison = $this->visualizationService->getComparisonData(
            $metricName,
            '1h',
            '1d'
        );

        $this->assertArrayHasKey('current', $comparison);
        $this->assertArrayHasKey('previous', $comparison);
        $this->assertArrayHasKey('comparison', $comparison);
        $this->assertEquals(100, $comparison['comparison']['percentage_change']);
    }

    public function test_can_get_top_metrics()
    {
        $metricName = 'test_metric';
        
        MetricAggregation::create([
            'metric_name' => $metricName,
            'value' => 100,
        ]);

        MetricAggregation::create([
            'metric_name' => $metricName,
            'value' => 300,
        ]);

        MetricAggregation::create([
            'metric_name' => $metricName,
            'value' => 200,
        ]);

        $topMetrics = $this->visualizationService->getTopMetrics($metricName, 2);

        $this->assertCount(2, $topMetrics);
        $this->assertEquals(300, $topMetrics->first()->value);
        $this->assertEquals(200, $topMetrics->last()->value);
    }

    public function test_can_get_distribution_data()
    {
        $metricName = 'test_metric';
        
        // Create data points from 0 to 100
        for ($i = 0; $i <= 100; $i += 10) {
            MetricAggregation::create([
                'metric_name' => $metricName,
                'value' => $i,
            ]);
        }

        $distribution = $this->visualizationService->getDistributionData($metricName, 10);

        $this->assertCount(10, $distribution);
        $this->assertEquals([0, 10], $distribution[0]['range']);
        $this->assertEquals([90, 100], $distribution[9]['range']);
    }

    public function test_caches_time_series_data()
    {
        $metricName = 'test_metric';
        $timeRange = '1h';
        
        MetricAggregation::create([
            'metric_name' => $metricName,
            'time_range' => $timeRange,
            'value' => 100,
        ]);

        // First call should cache the data
        $this->visualizationService->getTimeSeriesData($metricName, $timeRange);
        
        // Second call should use cache
        $this->visualizationService->getTimeSeriesData($metricName, $timeRange);

        $this->assertTrue(Cache::has("metric:{$metricName}:timeseries:{$timeRange}:d751713988987e9331980363e24189ce"));
    }

    public function test_calculates_correct_comparison()
    {
        $current = collect([
            ['value' => 200],
            ['value' => 300],
        ]);

        $previous = collect([
            ['value' => 100],
            ['value' => 200],
        ]);

        $comparison = $this->visualizationService->getComparisonData(
            'test_metric',
            '1h',
            '1d'
        );

        $this->assertEquals(100, $comparison['comparison']['percentage_change']);
        $this->assertEquals(100, $comparison['comparison']['absolute_change']);
    }
} 