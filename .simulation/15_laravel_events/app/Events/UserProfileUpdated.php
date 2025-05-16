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
 * @test-coverage tests/Feature/Events/UserProfileUpdateTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-006
 * @since 1.0.0
 * @author System
 * @package App\Events
 * @see \Tests\Feature\Events\UserProfileUpdateTest
 * @see \App\Events\BaseEvent
 * 
 * Event fired when a user updates their profile.
 * Broadcasts profile update data to private and presence channels.
 * 
 * @OpenAPI\Tag(name="User Events", description="User profile update event")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"user", "profile_data"},
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
 *             property="profile_data",
 *             type="object",
 *             description="Updated profile data",
 *             additionalProperties=true
 *         ),
 *         @OpenAPI\Property(
 *             property="updated_at",
 *             type="string",
 *             format="date-time",
 *             description="When the profile was updated"
 *         )
 *     }
 * )
 */
class UserProfileUpdated extends BaseEvent implements ShouldBroadcast
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
     * The updated profile data.
     *
     * @var array
     * @OpenAPI\Property(
     *     type="object",
     *     description="Updated profile data",
     *     additionalProperties=true
     * )
     */
    public $profileData;

    /**
     * The update timestamp.
     *
     * @var \Carbon\Carbon
     * @OpenAPI\Property(
     *     type="string",
     *     format="date-time",
     *     description="When the profile was updated"
     * )
     */
    public $updatedAt;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @param  array  $profileData
     * @return void
     * @throws \InvalidArgumentException If user is invalid or profile data is empty
     */
    public function __construct(User $user, array $profileData)
    {
        if (!$user) {
            throw new \InvalidArgumentException('User cannot be null');
        }

        if (empty($profileData)) {
            throw new \InvalidArgumentException('Profile data cannot be empty');
        }

        $this->user = $user;
        $this->profileData = $profileData;
        $this->updatedAt = now();
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
     *     required={"user", "profile_data", "updated_at"},
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
     *             property="profile_data",
     *             type="object",
     *             additionalProperties=true
     *         ),
     *         @OpenAPI\Property(
     *             property="updated_at",
     *             type="string",
     *             format="date-time"
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
            'profile_data' => $this->profileData,
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     * @OpenAPI\Property(
     *     type="string",
     *     description="Event broadcast name",
     *     example="user.profile_updated"
     * )
     */
    public function broadcastAs(): string
    {
        return 'user.profile_updated';
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