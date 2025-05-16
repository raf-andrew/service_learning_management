<?php

namespace Tests\Feature\Monitoring;

use App\Events\BaseEvent;
use App\Monitoring\EventMonitor;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Exception;

class TestEvent extends BaseEvent
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}

class EventMonitorTest extends TestCase
{
    protected $monitor;
    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new EventMonitor();
        $this->redis = Redis::connection();
        Event::fake();
    }

    protected function tearDown(): void
    {
        $this->monitor->clearOldMetrics();
        parent::tearDown();
    }

    /** @test */
    public function it_records_event_metrics()
    {
        $event = new TestEvent(['test' => 'data']);
        $this->monitor->startMonitoring($event);

        Event::dispatch($event);

        $metrics = $this->monitor->getMetrics();
        $this->assertEquals(1, $metrics['total_events']);
        $this->assertGreaterThan(0, $metrics['processing_time']);
        $this->assertGreaterThan(0, $metrics['memory_usage']);
    }

    /** @test */
    public function it_stores_metrics_in_redis()
    {
        $event = new TestEvent(['test' => 'data']);
        $this->monitor->startMonitoring($event);

        Event::dispatch($event);

        $storedMetrics = $this->monitor->getEventMetrics(TestEvent::class);
        $this->assertNotEmpty($storedMetrics);
        $this->assertArrayHasKey('processing_time', reset($storedMetrics));
        $this->assertArrayHasKey('memory_usage', reset($storedMetrics));
    }

    /** @test */
    public function it_records_failed_events()
    {
        $event = new TestEvent(['test' => 'data']);
        $exception = new Exception('Test exception');

        $this->monitor->recordFailure($event, $exception);

        $metrics = $this->monitor->getMetrics();
        $this->assertEquals(1, $metrics['failed_events']);

        $failures = $this->monitor->getEventFailures(TestEvent::class);
        $this->assertNotEmpty($failures);
        $this->assertStringContainsString('Test exception', reset($failures)['message']);
    }

    /** @test */
    public function it_calculates_average_metrics()
    {
        $event = new TestEvent(['test' => 'data']);
        
        // Dispatch multiple events
        for ($i = 0; $i < 3; $i++) {
            $this->monitor->startMonitoring($event);
            Event::dispatch($event);
        }

        $this->assertGreaterThan(0, $this->monitor->getAverageProcessingTime());
        $this->assertGreaterThan(0, $this->monitor->getAverageMemoryUsage());
    }

    /** @test */
    public function it_calculates_failure_rate()
    {
        $event = new TestEvent(['test' => 'data']);
        
        // Dispatch successful events
        for ($i = 0; $i < 8; $i++) {
            $this->monitor->startMonitoring($event);
            Event::dispatch($event);
        }

        // Record failed events
        for ($i = 0; $i < 2; $i++) {
            $this->monitor->recordFailure($event, new Exception('Test failure'));
        }

        $this->assertEquals(20, $this->monitor->getFailureRate());
    }

    /** @test */
    public function it_clears_old_metrics()
    {
        $event = new TestEvent(['test' => 'data']);
        $this->monitor->startMonitoring($event);
        Event::dispatch($event);

        $this->monitor->clearOldMetrics();

        $this->assertEmpty($this->monitor->getEventMetrics(TestEvent::class));
        $this->assertEmpty($this->monitor->getEventFailures(TestEvent::class));
    }

    /** @test */
    public function it_handles_multiple_event_types()
    {
        $event1 = new TestEvent(['test' => 'data1']);
        $event2 = new TestEvent(['test' => 'data2']);

        $this->monitor->startMonitoring($event1);
        $this->monitor->startMonitoring($event2);

        Event::dispatch($event1);
        Event::dispatch($event2);

        $metrics = $this->monitor->getMetrics();
        $this->assertEquals(2, $metrics['total_events']);
    }

    /** @test */
    public function it_preserves_metrics_after_failures()
    {
        $event = new TestEvent(['test' => 'data']);
        $this->monitor->startMonitoring($event);
        Event::dispatch($event);

        $initialMetrics = $this->monitor->getMetrics();

        $this->monitor->recordFailure($event, new Exception('Test failure'));

        $finalMetrics = $this->monitor->getMetrics();
        $this->assertEquals($initialMetrics['total_events'], $finalMetrics['total_events']);
        $this->assertEquals($initialMetrics['processing_time'], $finalMetrics['processing_time']);
        $this->assertEquals($initialMetrics['memory_usage'], $finalMetrics['memory_usage']);
    }
} 