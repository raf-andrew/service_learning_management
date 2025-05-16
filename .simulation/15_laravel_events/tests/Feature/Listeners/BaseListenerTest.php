<?php

namespace Tests\Feature\Listeners;

use App\Events\BaseEvent;
use App\Listeners\BaseListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Exception;

class TestListener extends BaseListener
{
    public function handle($event)
    {
        return true;
    }
}

class BaseListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    /** @test */
    public function it_can_handle_events()
    {
        $listener = new TestListener();
        $event = new class extends BaseEvent {};

        $result = $listener->handle($event);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_logs_failures()
    {
        Log::shouldReceive('error')->once();
        
        $listener = new TestListener();
        $event = new class extends BaseEvent {};
        $exception = new Exception('Test exception');

        $listener->failed($event, $exception);
    }

    /** @test */
    public function it_has_correct_queue_configuration()
    {
        $listener = new TestListener();
        
        $this->assertEquals('events', $listener->queue);
        $this->assertEquals(3, $listener->tries);
        $this->assertEquals(60, $listener->timeout);
    }

    /** @test */
    public function it_has_correct_retry_configuration()
    {
        $listener = new TestListener();
        
        $retryUntil = $listener->retryUntil();
        $this->assertInstanceOf(\DateTime::class, $retryUntil);
        
        $backoff = $listener->backoff();
        $this->assertEquals([1, 5, 10], $backoff);
    }

    /** @test */
    public function it_can_be_serialized()
    {
        $listener = new TestListener();
        $serialized = serialize($listener);
        $unserialized = unserialize($serialized);
        
        $this->assertInstanceOf(TestListener::class, $unserialized);
        $this->assertEquals($listener->queue, $unserialized->queue);
        $this->assertEquals($listener->tries, $unserialized->tries);
        $this->assertEquals($listener->timeout, $unserialized->timeout);
    }
} 