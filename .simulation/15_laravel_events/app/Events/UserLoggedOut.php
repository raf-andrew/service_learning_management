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
 * @test-coverage tests/Feature/Events/UserLogoutTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-004
 * @since 1.0.0
 * @author System
 * @package App\Events
 * @see \Tests\Feature\Events\UserLogoutTest
 * @see \App\Events\BaseEvent
 * 
 * Event fired when a user logs out of the system.
 * Broadcasts user logout data to private and presence channels.
 * 
 * @OpenAPI\Tag(name="User Events", description="User logout event")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"user", "session_duration"},
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
 *             property="logout_time",
 *             type="string",
 *             format="date-time",
 *             description="When the user logged out"
 *         ),
 *         @OpenAPI\Property(
 *             property="session_duration",
 *             type="integer",
 *             description="Session duration in seconds"
 *         )
 *     }
 * )
 */
class UserLoggedOut extends BaseEvent implements ShouldBroadcast
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
     * The logout timestamp.
     *
     * @var \Carbon\Carbon
     * @OpenAPI\Property(
     *     type="string",
     *     format="date-time",
     *     description="When the user logged out"
     * )
     */
    public $logoutTime;

    /**
     * The session duration in seconds.
     *
     * @var int
     * @OpenAPI\Property(
     *     type="integer",
     *     description="Session duration in seconds",
     *     minimum=0
     * )
     */
    public $sessionDuration;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @param  int  $sessionDuration
     * @return void
     * @throws \InvalidArgumentException If user is invalid or session duration is negative
     */
    public function __construct(User $user, int $sessionDuration)
    {
        if (!$user) {
            throw new \InvalidArgumentException('User cannot be null');
        }

        if ($sessionDuration < 0) {
            throw new \InvalidArgumentException('Session duration cannot be negative');
        }

        $this->user = $user;
        $this->logoutTime = now();
        $this->sessionDuration = $sessionDuration;
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
     *             @OpenAPI\Property(property="type", type="string", enum={"private", "presence"}),
     *             @OpenAPI\Property(property="name", type="string")
     *         }
     *     )
     * )
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
            new PresenceChannel('users.online'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     * @OpenAPI\Property(
     *     type="object",
     *     required={"user", "logout_time", "session_duration"},
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
     *             property="logout_time",
     *             type="string",
     *             format="date-time"
     *         ),
     *         @OpenAPI\Property(
     *             property="session_duration",
     *             type="integer"
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
            'logout_time' => $this->logoutTime->toIso8601String(),
            'session_duration' => $this->sessionDuration,
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     * @OpenAPI\Property(
     *     type="string",
     *     description="Event broadcast name",
     *     example="user.logged_out"
     * )
     */
    public function broadcastAs(): string
    {
        return 'user.logged_out';
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