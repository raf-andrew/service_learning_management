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
 * @test-coverage tests/Feature/Events/UserPasswordChangedTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status In Progress
 * @job-code EVT-005
 * @since 1.0.0
 * @author System
 * @package App\Events
 * @see \Tests\Feature\Events\UserPasswordChangedTest
 * @see \App\Events\BaseEvent
 * 
 * Event fired when a user changes their password.
 * Broadcasts password change notification to private channel.
 * 
 * @OpenAPI\Tag(name="User Events", description="User password change event")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"user", "changed_at", "ip_address"},
 *     properties={
 *         @OpenAPI\Property(
 *             property="user",
 *             type="object",
 *             required={"id", "name", "email"},
 *             properties={
 *                 @OpenAPI\Property(property="id", type="integer", format="int64"),
 *                 @OpenAPI\Property(property="name", type="string"),
 *                 @OpenAPI\Property(property="email", type="string", format="email")
 *             }
 *         ),
 *         @OpenAPI\Property(
 *             property="changed_at",
 *             type="string",
 *             format="date-time",
 *             description="When the password was changed"
 *         ),
 *         @OpenAPI\Property(
 *             property="ip_address",
 *             type="string",
 *             format="ipv4",
 *             description="IP address of the client that changed the password"
 *         )
 *     }
 * )
 */
class UserPasswordChanged extends BaseEvent implements ShouldBroadcast
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
     * The timestamp when the password was changed.
     *
     * @var \Carbon\Carbon
     * @OpenAPI\Property(
     *     type="string",
     *     format="date-time",
     *     description="When the password was changed"
     * )
     */
    public $changedAt;

    /**
     * The IP address of the client that changed the password.
     *
     * @var string
     * @OpenAPI\Property(
     *     type="string",
     *     format="ipv4",
     *     description="IP address of the client that changed the password"
     * )
     */
    public $ipAddress;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ipAddress
     * @return void
     * @throws \InvalidArgumentException If user is invalid or IP address is invalid
     */
    public function __construct(User $user, string $ipAddress)
    {
        if (!$user) {
            throw new \InvalidArgumentException('User cannot be null');
        }

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }

        $this->user = $user;
        $this->changedAt = now();
        $this->ipAddress = $ipAddress;
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
     *             @OpenAPI\Property(property="type", type="string", enum={"private"}),
     *             @OpenAPI\Property(property="name", type="string")
     *         }
     *     )
     * )
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     * @OpenAPI\Property(
     *     type="object",
     *     required={"user", "changed_at", "ip_address"},
     *     properties={
     *         @OpenAPI\Property(
     *             property="user",
     *             type="object",
     *             required={"id", "name", "email"},
     *             properties={
     *                 @OpenAPI\Property(property="id", type="integer", format="int64"),
     *                 @OpenAPI\Property(property="name", type="string"),
     *                 @OpenAPI\Property(property="email", type="string", format="email")
     *             }
     *         ),
     *         @OpenAPI\Property(
     *             property="changed_at",
     *             type="string",
     *             format="date-time"
     *         ),
     *         @OpenAPI\Property(
     *             property="ip_address",
     *             type="string",
     *             format="ipv4"
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
            ],
            'changed_at' => $this->changedAt->toIso8601String(),
            'ip_address' => $this->ipAddress,
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     * @OpenAPI\Property(
     *     type="string",
     *     description="Event broadcast name",
     *     example="user.password_changed"
     * )
     */
    public function broadcastAs(): string
    {
        return 'user.password_changed';
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