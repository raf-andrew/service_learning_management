<?php

namespace Tests\Performance;

use App\Events\BaseEvent;
use App\Listeners\BaseListener;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class TestEvent extends BaseEvent
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}

class TestListener extends BaseListener
{
    public function handle($event)
    {
        return true;
    }
}

class EventPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    /** @test */
    public function it_handles_high_event_volume()
    {
        $startTime = microtime(true);
        $eventCount = 1000;

        for ($i = 0; $i < $eventCount; $i++) {
            Event::dispatch(new TestEvent(['test' => 'data']));
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(5.0, $executionTime, 'Event dispatch took too long');
        $this->assertEquals($eventCount, Event::dispatched(TestEvent::class)->count());
    }

    /** @test */
    public function it_optimizes_memory_usage()
    {
        $initialMemory = memory_get_usage();
        $eventCount = 1000;

        for ($i = 0; $i < $eventCount; $i++) {
            Event::dispatch(new TestEvent(['test' => 'data']));
        }

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryPerEvent = $memoryUsed / $eventCount;

        $this->assertLessThan(1024, $memoryPerEvent, 'Memory usage per event is too high');
    }

    /** @test */
    public function it_handles_concurrent_events()
    {
        $startTime = microtime(true);
        $concurrentEvents = 100;
        $promises = [];

        for ($i = 0; $i < $concurrentEvents; $i++) {
            $promises[] = async(function () {
                return Event::dispatch(new TestEvent(['test' => 'data']));
            });
        }

        // Wait for all events to complete
        foreach ($promises as $promise) {
            $promise->wait();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(2.0, $executionTime, 'Concurrent event handling took too long');
        $this->assertEquals($concurrentEvents, Event::dispatched(TestEvent::class)->count());
    }

    /** @test */
    public function it_optimizes_queue_processing()
    {
        $startTime = microtime(true);
        $jobCount = 100;

        for ($i = 0; $i < $jobCount; $i++) {
            Queue::push(new TestListener());
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime, 'Queue job creation took too long');
        $this->assertEquals($jobCount, Queue::pushed(TestListener::class)->count());
    }

    /** @test */
    public function it_handles_large_event_payloads()
    {
        $largeData = array_fill(0, 1000, 'test data');
        $startTime = microtime(true);

        Event::dispatch(new TestEvent($largeData));

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(0.5, $executionTime, 'Large payload handling took too long');
        $this->assertEquals(1, Event::dispatched(TestEvent::class)->count());
    }

    /** @test */
    public function it_optimizes_listener_execution()
    {
        $startTime = microtime(true);
        $listenerCount = 100;

        for ($i = 0; $i < $listenerCount; $i++) {
            $listener = new TestListener();
            $listener->handle(new TestEvent(['test' => 'data']));
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $timePerListener = $executionTime / $listenerCount;

        $this->assertLessThan(0.01, $timePerListener, 'Listener execution took too long');
    }

    /** @test */
    public function it_handles_event_chaining()
    {
        $startTime = microtime(true);
        $chainLength = 10;

        $event = new TestEvent(['test' => 'data']);
        for ($i = 0; $i < $chainLength; $i++) {
            Event::dispatch($event);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime, 'Event chaining took too long');
        $this->assertEquals($chainLength, Event::dispatched(TestEvent::class)->count());
    }

    /** @test */
    public function it_optimizes_broadcasting()
    {
        $startTime = microtime(true);
        $broadcastCount = 100;

        for ($i = 0; $i < $broadcastCount; $i++) {
            $event = new TestEvent(['test' => 'data']);
            $event->broadcastOn();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $timePerBroadcast = $executionTime / $broadcastCount;

        $this->assertLessThan(0.001, $timePerBroadcast, 'Broadcasting setup took too long');
    }
} 