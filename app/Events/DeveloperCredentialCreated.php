<?php

namespace App\Events;

use App\Models\DeveloperCredential;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a developer credential is created.
 * 
 * This event can be used to:
 * - Send notifications to administrators
 * - Update audit logs
 * - Trigger security scans
 * - Update user permissions
 */
class DeveloperCredentialCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The developer credential instance.
     *
     * @var \App\Models\DeveloperCredential
     */
    public $credential;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\DeveloperCredential  $credential
     * @return void
     */
    public function __construct(DeveloperCredential $credential)
    {
        $this->credential = $credential;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->credential->user_id),
            new Channel('admin.credentials'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'credential_id' => $this->credential->id,
            'user_id' => $this->credential->user_id,
            'github_username' => $this->credential->github_username,
            'created_at' => $this->credential->created_at->toISOString(),
            'permissions' => $this->credential->permissions,
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'developer.credential.created';
    }
} 