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

class UserPasswordChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * The change timestamp.
     *
     * @var \Carbon\Carbon
     */
    public $changedAt;

    /**
     * The IP address from which the change was made.
     *
     * @var string
     */
    public $ipAddress;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ipAddress
     * @return void
     */
    public function __construct(User $user, string $ipAddress)
    {
        $this->user = $user;
        $this->changedAt = now();
        $this->ipAddress = $ipAddress;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
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
     */
    public function broadcastAs(): string
    {
        return 'user.password_changed';
    }
} 