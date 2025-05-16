<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * @laravel-simulation
 * @component-type Event
 * @test-coverage tests/Feature/Events/BaseEventTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-001
 * @since 1.0.0
 * @author System
 * @package App\Events
 * @see \Tests\Feature\Events\BaseEventTest
 * @see \Tests\Feature\Events\UserEventsTest
 * @see \Tests\Feature\Events\UserRegistrationTest
 * 
 * Base event class that all other events should extend.
 * Provides common functionality for event handling and broadcasting.
 * 
 * @OpenAPI\Tag(name="Events", description="Base event system")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"broadcastAs", "broadcastOn", "broadcastWith", "broadcastWhen", "broadcastQueue"},
 *     properties={
 *         @OpenAPI\Property(property="broadcastAs", type="string", description="Event broadcast name"),
 *         @OpenAPI\Property(property="broadcastOn", type="array", description="Broadcast channels"),
 *         @OpenAPI\Property(property="broadcastWith", type="object", description="Broadcast data"),
 *         @OpenAPI\Property(property="broadcastWhen", type="boolean", description="Broadcast condition"),
 *         @OpenAPI\Property(property="broadcastQueue", type="string", description="Queue name")
 *     }
 * )
 */
abstract class BaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The event's broadcast name.
     * 
     * @OpenAPI\Property(
     *     type="string",
     *     description="The name used for broadcasting this event",
     *     example="UserRegistered"
     * )
     * 
     * @return string
     */
    public function broadcastAs()
    {
        return class_basename($this);
    }

    /**
     * Get the channels the event should broadcast on.
     * 
     * @OpenAPI\Property(
     *     type="array",
     *     description="List of channels to broadcast on",
     *     @OpenAPI\Items(
     *         type="object",
     *         properties={
     *             @OpenAPI\Property(property="type", type="string", enum={"public", "private", "presence"}),
     *             @OpenAPI\Property(property="name", type="string")
     *         }
     *     )
     * )
     * 
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    /**
     * Get the data to broadcast.
     * 
     * @OpenAPI\Property(
     *     type="object",
     *     description="Data to be broadcast with the event",
     *     additionalProperties=true
     * )
     * 
     * @return array
     */
    public function broadcastWith()
    {
        return $this->toArray();
    }

    /**
     * Determine if this event should broadcast.
     * 
     * @OpenAPI\Property(
     *     type="boolean",
     *     description="Whether the event should be broadcast",
     *     default=true
     * )
     * 
     * @return bool
     */
    public function broadcastWhen()
    {
        return true;
    }

    /**
     * Get the event's broadcast queue.
     * 
     * @OpenAPI\Property(
     *     type="string",
     *     description="The queue name for broadcasting",
     *     default="events"
     * )
     * 
     * @return string
     */
    public function broadcastQueue()
    {
        return 'events';
    }
} 