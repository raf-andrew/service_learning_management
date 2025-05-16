<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Events\BaseEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Tests\TestReporter;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Events/BaseEventTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-001-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Events
 * @see \App\Events\BaseEvent
 * 
 * Test suite for the BaseEvent class.
 * Ensures all core event functionality works as expected.
 * 
 * @OpenAPI\Tag(name="Event Tests", description="Base event system tests")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"test_class"},
 *     properties={
 *         @OpenAPI\Property(property="test_class", type="string", format="class"),
 *         @OpenAPI\Property(property="test_methods", type="array", items=@OpenAPI\Items(type="string"))
 *     }
 * )
 */
class BaseEventTest extends TestCase
{
    /**
     * Test event instance.
     *
     * @var BaseEvent
     */
    protected BaseEvent $event;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testReporter = new TestReporter();
        $this->event = new class extends BaseEvent {
            public function broadcastOn()
            {
                return [
                    new Channel('public-channel'),
                    new PrivateChannel('private-channel'),
                    new PresenceChannel('presence-channel')
                ];
            }
        };
    }

    /**
     * Test event can be dispatched.
     * 
     * @test
     * @OpenAPI\Tag(name="Event Dispatching")
     * @OpenAPI\Response(
     *     response=200,
     *     description="Event successfully dispatched"
     * )
     */
    public function test_event_can_be_dispatched()
    {
        Event::fake();

        event($this->event);

        Event::assertDispatched(get_class($this->event));
    }

    /**
     * Test event broadcasting configuration.
     * 
     * @test
     * @OpenAPI\Tag(name="Event Broadcasting")
     * @OpenAPI\Response(
     *     response=200,
     *     description="Broadcasting configuration verified"
     * )
     */
    public function test_event_broadcasting_configuration()
    {
        $this->assertEquals(
            class_basename($this->event),
            $this->event->broadcastAs()
        );
        $this->assertTrue($this->event->broadcastWhen());
        $this->assertEquals('events', $this->event->broadcastQueue());
    }

    /**
     * Test event data broadcasting.
     * 
     * @test
     * @OpenAPI\Tag(name="Event Data")
     * @OpenAPI\Response(
     *     response=200,
     *     description="Event data broadcasting verified"
     * )
     */
    public function test_event_data_broadcasting()
    {
        $data = $this->event->broadcastWith();
        $this->assertIsArray($data);
    }

    /**
     * Test different channel types.
     * 
     * @test
     * @OpenAPI\Tag(name="Channel Types")
     * @OpenAPI\Response(
     *     response=200,
     *     description="Channel type handling verified"
     * )
     */
    public function test_different_channel_types()
    {
        $channels = $this->event->broadcastOn();
        
        $this->assertCount(3, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertInstanceOf(PrivateChannel::class, $channels[1]);
        $this->assertInstanceOf(PresenceChannel::class, $channels[2]);
    }

    /**
     * Test event serialization.
     * 
     * @test
     * @OpenAPI\Tag(name="Event Serialization")
     * @OpenAPI\Response(
     *     response=200,
     *     description="Event serialization verified"
     * )
     */
    public function test_event_serialization()
    {
        $serialized = serialize($this->event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(get_class($this->event), $unserialized);
    }

    /**
     * Test that the event broadcasts with correct name.
     *
     * @return void
     */
    public function test_it_broadcasts_with_correct_name(): void
    {
        $this->assertEquals(
            class_basename($this->event),
            $this->event->broadcastAs()
        );
    }

    /**
     * Test that the event broadcasts on correct channels.
     *
     * @return void
     */
    public function test_it_broadcasts_on_correct_channels(): void
    {
        $channels = $this->event->broadcastOn();
        
        $this->assertCount(3, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertInstanceOf(PrivateChannel::class, $channels[1]);
        $this->assertInstanceOf(PresenceChannel::class, $channels[2]);
    }

    /**
     * Test that the event broadcasts with correct data.
     *
     * @return void
     */
    public function test_it_broadcasts_with_correct_data(): void
    {
        $data = $this->event->broadcastWith();
        $this->assertIsArray($data);
    }

    /**
     * Test that the event broadcasts when condition is met.
     *
     * @return void
     */
    public function test_it_broadcasts_when_condition_is_met(): void
    {
        $this->assertTrue($this->event->broadcastWhen());
    }

    /**
     * Test that the event broadcasts on correct queue.
     *
     * @return void
     */
    public function test_it_broadcasts_on_correct_queue(): void
    {
        $this->assertEquals('events', $this->event->broadcastQueue());
    }

    /**
     * Test that the event is broadcast correctly.
     *
     * @return void
     */
    public function test_it_broadcasts_event(): void
    {
        Broadcast::fake();
        
        event($this->event);
        
        Broadcast::assertBroadcasted(get_class($this->event));
    }

    /**
     * Test that the event handles empty broadcast data.
     *
     * @return void
     */
    public function test_it_handles_empty_broadcast_data(): void
    {
        $event = new class extends BaseEvent {
            public function toArray()
            {
                return [];
            }
        };
        
        $data = $event->broadcastWith();
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /**
     * Test that the event handles custom broadcast conditions.
     *
     * @return void
     */
    public function test_it_handles_custom_broadcast_conditions(): void
    {
        $event = new class extends BaseEvent {
            public function broadcastWhen()
            {
                return false;
            }
        };
        
        $this->assertFalse($event->broadcastWhen());
    }

    /**
     * Test that the event handles custom broadcast queue.
     *
     * @return void
     */
    public function test_it_handles_custom_broadcast_queue(): void
    {
        $event = new class extends BaseEvent {
            public function broadcastQueue()
            {
                return 'custom-queue';
            }
        };
        
        $this->assertEquals('custom-queue', $event->broadcastQueue());
    }

    /**
     * Test that the event handles custom broadcast channels.
     *
     * @return void
     */
    public function test_it_handles_custom_broadcast_channels(): void
    {
        $event = new class extends BaseEvent {
            public function broadcastOn()
            {
                return [new Channel('custom-channel')];
            }
        };
        
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
    }

    /**
     * Test that the event handles custom broadcast data.
     *
     * @return void
     */
    public function test_it_handles_custom_broadcast_data(): void
    {
        $event = new class extends BaseEvent {
            public function broadcastWith()
            {
                return ['custom' => 'data'];
            }
        };
        
        $data = $event->broadcastWith();
        $this->assertEquals(['custom' => 'data'], $data);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->testReporter->endTestSuite($this);
    }
} 