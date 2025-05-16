<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @laravel-simulation
 * @component-type Event
 * @test-coverage tests/Feature/Events/UserRegistrationTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-002
 * @since 1.0.0
 * @author System
 * @package App\Events
 * @see \Tests\Feature\Events\UserRegistrationTest
 * @see \Tests\Feature\Events\UserEventsTest
 * @see \App\Events\BaseEvent
 * 
 * Event fired when a new user registers in the system.
 * Broadcasts user registration data to private and public channels.
 * 
 * @OpenAPI\Tag(name="User Events", description="User registration event")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"user"},
 *     properties={
 *         @OpenAPI\Property(
 *             property="user",
 *             type="object",
 *             required={"id", "name", "email", "registered_at"},
 *             properties={
 *                 @OpenAPI\Property(property="id", type="integer", format="int64"),
 *                 @OpenAPI\Property(property="name", type="string"),
 *                 @OpenAPI\Property(property="email", type="string", format="email"),
 *                 @OpenAPI\Property(property="registered_at", type="string", format="date-time")
 *             }
 *         )
 *     }
 * )
 */
class UserRegistered extends BaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     * @OpenAPI\Property(
     *     type="object",
     *     ref="#/components/schemas/User"
     * )
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @return void
     * @throws \InvalidArgumentException If user is invalid
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     * @OpenAPI\Property(
     *     type="array",
     *     description="Channels to broadcast on",
     *     @OpenAPI\Items(
     *         type="object",
     *         properties={
     *             @OpenAPI\Property(property="type", type="string", enum={"private", "public"}),
     *             @OpenAPI\Property(property="name", type="string")
     *         }
     *     )
     * )
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
            new Channel('users'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     * @OpenAPI\Property(
     *     type="object",
     *     required={"user"},
     *     properties={
     *         @OpenAPI\Property(
     *             property="user",
     *             type="object",
     *             required={"id", "name", "email", "registered_at"},
     *             properties={
     *                 @OpenAPI\Property(property="id", type="integer", format="int64"),
     *                 @OpenAPI\Property(property="name", type="string"),
     *                 @OpenAPI\Property(property="email", type="string", format="email"),
     *                 @OpenAPI\Property(property="registered_at", type="string", format="date-time")
     *             }
     *         )
     *     }
     * )
     */
    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'registered_at' => $this->user->created_at,
            ],
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     * @OpenAPI\Property(
     *     type="string",
     *     description="Event broadcast name",
     *     example="user.registered"
     * )
     */
    public function broadcastAs(): string
    {
        return 'user.registered';
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     * @OpenAPI\Property(
     *     type="boolean",
     *     description="Whether the event should be broadcast",
     *     default=true
     * )
     */
    public function broadcastWhen()
    {
        return $this->user->should_broadcast ?? true;
    }
} 