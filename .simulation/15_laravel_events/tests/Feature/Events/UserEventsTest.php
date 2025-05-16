<?php

namespace Tests\Feature\Events;

use App\Events\UserRegistered;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Events\UserProfileUpdated;
use App\Events\UserPasswordChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class UserEventsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the UserRegistered event is dispatched when a user is created.
     */
    public function test_user_registered_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();

        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Test that the UserRegistered event broadcasts on the correct channels.
     */
    public function test_user_registered_event_broadcasts_on_correct_channels()
    {
        Broadcast::fake();

        $user = User::factory()->create();

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $user->id),
            UserRegistered::class
        );

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\Channel('users'),
            UserRegistered::class
        );
    }

    /**
     * Test that the UserRegistered event broadcasts the correct data.
     */
    public function test_user_registered_event_broadcasts_correct_data()
    {
        Broadcast::fake();

        $user = User::factory()->create();

        Broadcast::assertBroadcastWith(
            UserRegistered::class,
            function ($data) use ($user) {
                return $data['user']['id'] === $user->id &&
                    $data['user']['name'] === $user->name &&
                    $data['user']['email'] === $user->email;
            }
        );
    }

    /**
     * Test that the UserRegistered event uses the correct broadcast name.
     */
    public function test_user_registered_event_uses_correct_broadcast_name()
    {
        Broadcast::fake();

        $user = User::factory()->create();

        Broadcast::assertBroadcastAs(
            UserRegistered::class,
            'user.registered'
        );
    }

    /**
     * Test that the UserRegistered event is queued.
     */
    public function test_user_registered_event_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(UserRegistered::class)
            )
        );
    }

    /**
     * Test that the UserRegistered event serializes the user model correctly.
     */
    public function test_user_registered_event_serializes_user_model()
    {
        Event::fake();

        $user = User::factory()->create();
        $event = new UserRegistered($user);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    /**
     * Test that the UserLoggedIn event is dispatched when a user logs in.
     */
    public function test_user_logged_in_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();
        event(new UserLoggedIn($user));

        Event::assertDispatched(UserLoggedIn::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Test that the UserLoggedIn event broadcasts on the correct channels.
     */
    public function test_user_logged_in_event_broadcasts_on_correct_channels()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        event(new UserLoggedIn($user));

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $user->id),
            UserLoggedIn::class
        );

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PresenceChannel('users.online'),
            UserLoggedIn::class
        );
    }

    /**
     * Test that the UserLoggedIn event broadcasts the correct data.
     */
    public function test_user_logged_in_event_broadcasts_correct_data()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        event(new UserLoggedIn($user));

        Broadcast::assertBroadcastWith(
            UserLoggedIn::class,
            function ($data) use ($user) {
                return $data['user']['id'] === $user->id &&
                    $data['user']['name'] === $user->name &&
                    $data['user']['email'] === $user->email &&
                    isset($data['login_time']);
            }
        );
    }

    /**
     * Test that the UserLoggedIn event uses the correct broadcast name.
     */
    public function test_user_logged_in_event_uses_correct_broadcast_name()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        event(new UserLoggedIn($user));

        Broadcast::assertBroadcastAs(
            UserLoggedIn::class,
            'user.logged_in'
        );
    }

    /**
     * Test that the UserLoggedIn event is queued.
     */
    public function test_user_logged_in_event_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(UserLoggedIn::class)
            )
        );
    }

    /**
     * Test that the UserLoggedIn event serializes the user model correctly.
     */
    public function test_user_logged_in_event_serializes_user_model()
    {
        Event::fake();

        $user = User::factory()->create();
        $event = new UserLoggedIn($user);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    /**
     * Test that the UserLoggedOut event is dispatched when a user logs out.
     */
    public function test_user_logged_out_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();
        $sessionDuration = 3600; // 1 hour
        event(new UserLoggedOut($user, $sessionDuration));

        Event::assertDispatched(UserLoggedOut::class, function ($event) use ($user, $sessionDuration) {
            return $event->user->id === $user->id &&
                   $event->sessionDuration === $sessionDuration;
        });
    }

    /**
     * Test that the UserLoggedOut event broadcasts on the correct channels.
     */
    public function test_user_logged_out_event_broadcasts_on_correct_channels()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $sessionDuration = 3600;
        event(new UserLoggedOut($user, $sessionDuration));

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $user->id),
            UserLoggedOut::class
        );

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PresenceChannel('users.online'),
            UserLoggedOut::class
        );
    }

    /**
     * Test that the UserLoggedOut event broadcasts the correct data.
     */
    public function test_user_logged_out_event_broadcasts_correct_data()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $sessionDuration = 3600;
        event(new UserLoggedOut($user, $sessionDuration));

        Broadcast::assertBroadcastWith(
            UserLoggedOut::class,
            function ($data) use ($user, $sessionDuration) {
                return $data['user']['id'] === $user->id &&
                    $data['user']['name'] === $user->name &&
                    $data['user']['email'] === $user->email &&
                    $data['session_duration'] === $sessionDuration &&
                    isset($data['logout_time']);
            }
        );
    }

    /**
     * Test that the UserLoggedOut event uses the correct broadcast name.
     */
    public function test_user_logged_out_event_uses_correct_broadcast_name()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $sessionDuration = 3600;
        event(new UserLoggedOut($user, $sessionDuration));

        Broadcast::assertBroadcastAs(
            UserLoggedOut::class,
            'user.logged_out'
        );
    }

    /**
     * Test that the UserLoggedOut event is queued.
     */
    public function test_user_logged_out_event_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(UserLoggedOut::class)
            )
        );
    }

    /**
     * Test that the UserLoggedOut event serializes the user model correctly.
     */
    public function test_user_logged_out_event_serializes_user_model()
    {
        Event::fake();

        $user = User::factory()->create();
        $sessionDuration = 3600;
        $event = new UserLoggedOut($user, $sessionDuration);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals($sessionDuration, $event->sessionDuration);
    }

    /**
     * Test that the UserProfileUpdated event is dispatched when a user's profile is updated.
     */
    public function test_user_profile_updated_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        event(new UserProfileUpdated($user, $profileData));

        Event::assertDispatched(UserProfileUpdated::class, function ($event) use ($user, $profileData) {
            return $event->user->id === $user->id &&
                   $event->profileData === $profileData;
        });
    }

    /**
     * Test that the UserProfileUpdated event broadcasts on the correct channels.
     */
    public function test_user_profile_updated_event_broadcasts_on_correct_channels()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        event(new UserProfileUpdated($user, $profileData));

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $user->id),
            UserProfileUpdated::class
        );

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PresenceChannel('users.online'),
            UserProfileUpdated::class
        );
    }

    /**
     * Test that the UserProfileUpdated event broadcasts the correct data.
     */
    public function test_user_profile_updated_event_broadcasts_correct_data()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        event(new UserProfileUpdated($user, $profileData));

        Broadcast::assertBroadcastWith(
            UserProfileUpdated::class,
            function ($data) use ($user, $profileData) {
                return $data['user']['id'] === $user->id &&
                    $data['user']['name'] === $user->name &&
                    $data['user']['email'] === $user->email &&
                    $data['profile_data'] === $profileData &&
                    isset($data['updated_at']);
            }
        );
    }

    /**
     * Test that the UserProfileUpdated event uses the correct broadcast name.
     */
    public function test_user_profile_updated_event_uses_correct_broadcast_name()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        event(new UserProfileUpdated($user, $profileData));

        Broadcast::assertBroadcastAs(
            UserProfileUpdated::class,
            'user.profile_updated'
        );
    }

    /**
     * Test that the UserProfileUpdated event is queued.
     */
    public function test_user_profile_updated_event_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(UserProfileUpdated::class)
            )
        );
    }

    /**
     * Test that the UserProfileUpdated event serializes the user model correctly.
     */
    public function test_user_profile_updated_event_serializes_user_model()
    {
        Event::fake();

        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        $event = new UserProfileUpdated($user, $profileData);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals($profileData, $event->profileData);
    }

    /**
     * Test that the UserPasswordChanged event is dispatched when a user's password is changed.
     */
    public function test_user_password_changed_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        event(new UserPasswordChanged($user, $ipAddress));

        Event::assertDispatched(UserPasswordChanged::class, function ($event) use ($user, $ipAddress) {
            return $event->user->id === $user->id &&
                   $event->ipAddress === $ipAddress;
        });
    }

    /**
     * Test that the UserPasswordChanged event broadcasts on the correct channels.
     */
    public function test_user_password_changed_event_broadcasts_on_correct_channels()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $user->id),
            UserPasswordChanged::class
        );

        Broadcast::assertBroadcastOn(
            new \Illuminate\Broadcasting\PresenceChannel('users.online'),
            UserPasswordChanged::class
        );
    }

    /**
     * Test that the UserPasswordChanged event broadcasts the correct data.
     */
    public function test_user_password_changed_event_broadcasts_correct_data()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertBroadcastWith(
            UserPasswordChanged::class,
            function ($data) use ($user, $ipAddress) {
                return $data['user']['id'] === $user->id &&
                    $data['user']['name'] === $user->name &&
                    $data['user']['email'] === $user->email &&
                    $data['ip_address'] === $ipAddress &&
                    isset($data['changed_at']);
            }
        );
    }

    /**
     * Test that the UserPasswordChanged event uses the correct broadcast name.
     */
    public function test_user_password_changed_event_uses_correct_broadcast_name()
    {
        Broadcast::fake();

        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertBroadcastAs(
            UserPasswordChanged::class,
            'user.password_changed'
        );
    }

    /**
     * Test that the UserPasswordChanged event is queued.
     */
    public function test_user_password_changed_event_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(UserPasswordChanged::class)
            )
        );
    }

    /**
     * Test that the UserPasswordChanged event serializes the user model correctly.
     */
    public function test_user_password_changed_event_serializes_user_model()
    {
        Event::fake();

        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $event = new UserPasswordChanged($user, $ipAddress);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals($ipAddress, $event->ipAddress);
    }
} 