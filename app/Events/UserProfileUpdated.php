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
 * @test-coverage tests/Feature/Events/UserProfileUpdatedTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status In Progress
 * @job-code EVT-006
 * @since 1.0.0
 * @author System
 * @package App\Events
 * @see \Tests\Feature\Events\UserProfileUpdatedTest
 * @see \App\Events\BaseEvent
 * 
 * Event fired when a user updates their profile.
 * Broadcasts profile update data to private channel.
 * 
 * @OpenAPI\Tag(name="User Events", description="User profile update event")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"user", "updated_at", "changes"},
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
 *             property="updated_at",
 *             type="string",
 *             format="date-time",
 *             description="When the profile was updated"
 *         ),
 *         @OpenAPI\Property(
 *             property="changes",
 *             type="object",
 *             description="Fields that were changed",
 *             additionalProperties={
 *                 type="object",
 *                 properties={
 *                     @OpenAPI\Property(property="old", type="string"),
 *                     @OpenAPI\Property(property="new", type="string")
 *                 }
 *             }
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
     * The timestamp when the profile was updated.
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
     * The fields that were changed.
     *
     * @var array<string, array{old: mixed, new: mixed}>
     * @OpenAPI\Property(
     *     type="object",
     *     description="Fields that were changed",
     *     additionalProperties={
 *         type="object",
 *         properties={
 *             @OpenAPI\Property(property="old", type="string"),
 *             @OpenAPI\Property(property="new", type="string")
 *         }
 *     }
     * )
     */
    public $changes;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return void
     * @throws \InvalidArgumentException If user is invalid or changes are invalid
     */
    public function __construct(User $user, array $changes)
    {
        if (!$user) {
            throw new \InvalidArgumentException('User cannot be null');
        }

        if (empty($changes)) {
            throw new \InvalidArgumentException('Changes cannot be empty');
        }

        foreach ($changes as $field => $change) {
            if (!isset($change['old']) || !isset($change['new'])) {
                throw new \InvalidArgumentException("Invalid change format for field: {$field}");
            }
        }

        $this->user = $user;
        $this->updatedAt = now();
        $this->changes = $changes;
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
     *     required={"user", "updated_at", "changes"},
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
     *             property="updated_at",
     *             type="string",
     *             format="date-time"
     *         ),
     *         @OpenAPI\Property(
     *             property="changes",
     *             type="object",
     *             additionalProperties={
     *                 type="object",
     *                 properties={
     *                     @OpenAPI\Property(property="old", type="string"),
     *                     @OpenAPI\Property(property="new", type="string")
     *                 }
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
            ],
            'updated_at' => $this->updatedAt->toIso8601String(),
            'changes' => $this->changes,
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