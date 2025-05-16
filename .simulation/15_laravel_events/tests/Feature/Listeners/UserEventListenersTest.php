<?php

namespace Tests\Feature\Listeners;

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;
use App\Events\UserLoggedIn;
use App\Listeners\UpdateLastLoginTime;
use App\Services\UserService;
use App\Events\UserLoggedOut;
use App\Listeners\LogUserActivity;
use App\Services\ActivityLogService;
use App\Events\UserProfileUpdated;
use App\Listeners\NotifyProfileUpdate;
use App\Events\UserPasswordChanged;
use App\Listeners\LogPasswordChange;

class UserEventListenersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the SendWelcomeEmail listener is queued.
     */
    public function test_send_welcome_email_listener_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(SendWelcomeEmail::class)
            )
        );
    }

    /**
     * Test that the SendWelcomeEmail listener uses the correct queue.
     */
    public function test_send_welcome_email_listener_uses_correct_queue()
    {
        $listener = new SendWelcomeEmail(Mockery::mock(NotificationService::class));

        $this->assertEquals('welcome-emails', $listener->viaQueue());
    }

    /**
     * Test that the SendWelcomeEmail listener uses the correct connection.
     */
    public function test_send_welcome_email_listener_uses_correct_connection()
    {
        $listener = new SendWelcomeEmail(Mockery::mock(NotificationService::class));

        $this->assertEquals('emails', $listener->viaConnection());
    }

    /**
     * Test that the SendWelcomeEmail listener handles the event correctly.
     */
    public function test_send_welcome_email_listener_handles_event()
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->with(Mockery::type(User::class));

        $listener = new SendWelcomeEmail($notificationService);
        $user = User::factory()->create();
        $event = new UserRegistered($user);

        $listener->handle($event);
    }

    /**
     * Test that the SendWelcomeEmail listener handles failures correctly.
     */
    public function test_send_welcome_email_listener_handles_failures()
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->andThrow(new \Exception('Failed to send email'));

        $listener = new SendWelcomeEmail($notificationService);
        $user = User::factory()->create();
        $event = new UserRegistered($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to send email');

        $listener->handle($event);
    }

    /**
     * Test that the SendWelcomeEmail listener logs failures.
     */
    public function test_send_welcome_email_listener_logs_failures()
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->andThrow(new \Exception('Failed to send email'));

        $listener = new SendWelcomeEmail($notificationService);
        $user = User::factory()->create();
        $event = new UserRegistered($user);

        try {
            $listener->handle($event);
        } catch (\Exception $e) {
            $listener->failed($event, $e);
        }

        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'welcome-emails',
            'connection' => 'emails',
        ]);
    }

    /**
     * Test that the SendWelcomeEmail listener is registered for the event.
     */
    public function test_send_welcome_email_listener_is_registered()
    {
        Event::fake();

        $user = User::factory()->create();

        Event::assertListening(
            UserRegistered::class,
            SendWelcomeEmail::class
        );
    }

    /**
     * Test that the UpdateLastLoginTime listener is queued.
     */
    public function test_update_last_login_time_listener_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(UpdateLastLoginTime::class)
            )
        );
    }

    /**
     * Test that the UpdateLastLoginTime listener uses the correct queue.
     */
    public function test_update_last_login_time_listener_uses_correct_queue()
    {
        $listener = new UpdateLastLoginTime(Mockery::mock(UserService::class));

        $this->assertEquals('user-updates', $listener->viaQueue());
    }

    /**
     * Test that the UpdateLastLoginTime listener uses the correct connection.
     */
    public function test_update_last_login_time_listener_uses_correct_connection()
    {
        $listener = new UpdateLastLoginTime(Mockery::mock(UserService::class));

        $this->assertEquals('default', $listener->viaConnection());
    }

    /**
     * Test that the UpdateLastLoginTime listener handles the event correctly.
     */
    public function test_update_last_login_time_listener_handles_event()
    {
        $userService = Mockery::mock(UserService::class);
        $userService->shouldReceive('updateLastLoginTime')
            ->once()
            ->with(Mockery::type(User::class), Mockery::type(\Carbon\Carbon::class));

        $listener = new UpdateLastLoginTime($userService);
        $user = User::factory()->create();
        $event = new UserLoggedIn($user);

        $listener->handle($event);
    }

    /**
     * Test that the UpdateLastLoginTime listener handles failures correctly.
     */
    public function test_update_last_login_time_listener_handles_failures()
    {
        $userService = Mockery::mock(UserService::class);
        $userService->shouldReceive('updateLastLoginTime')
            ->once()
            ->andThrow(new \Exception('Failed to update last login time'));

        $listener = new UpdateLastLoginTime($userService);
        $user = User::factory()->create();
        $event = new UserLoggedIn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update last login time');

        $listener->handle($event);
    }

    /**
     * Test that the UpdateLastLoginTime listener logs failures.
     */
    public function test_update_last_login_time_listener_logs_failures()
    {
        $userService = Mockery::mock(UserService::class);
        $userService->shouldReceive('updateLastLoginTime')
            ->once()
            ->andThrow(new \Exception('Failed to update last login time'));

        $listener = new UpdateLastLoginTime($userService);
        $user = User::factory()->create();
        $event = new UserLoggedIn($user);

        try {
            $listener->handle($event);
        } catch (\Exception $e) {
            $listener->failed($event, $e);
        }

        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'user-updates',
            'connection' => 'default',
        ]);
    }

    /**
     * Test that the UpdateLastLoginTime listener is registered for the event.
     */
    public function test_update_last_login_time_listener_is_registered()
    {
        Event::fake();

        $user = User::factory()->create();
        event(new UserLoggedIn($user));

        Event::assertListening(
            UserLoggedIn::class,
            UpdateLastLoginTime::class
        );
    }

    /**
     * Test that the LogUserActivity listener is queued.
     */
    public function test_log_user_activity_listener_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(LogUserActivity::class)
            )
        );
    }

    /**
     * Test that the LogUserActivity listener uses the correct queue.
     */
    public function test_log_user_activity_listener_uses_correct_queue()
    {
        $listener = new LogUserActivity(Mockery::mock(ActivityLogService::class));

        $this->assertEquals('activity-logs', $listener->viaQueue());
    }

    /**
     * Test that the LogUserActivity listener uses the correct connection.
     */
    public function test_log_user_activity_listener_uses_correct_connection()
    {
        $listener = new LogUserActivity(Mockery::mock(ActivityLogService::class));

        $this->assertEquals('default', $listener->viaConnection());
    }

    /**
     * Test that the LogUserActivity listener handles the event correctly.
     */
    public function test_log_user_activity_listener_handles_event()
    {
        $activityLogService = Mockery::mock(ActivityLogService::class);
        $activityLogService->shouldReceive('logUserSession')
            ->once()
            ->with(
                Mockery::type(User::class),
                Mockery::type(\Carbon\Carbon::class),
                Mockery::type('int')
            );

        $listener = new LogUserActivity($activityLogService);
        $user = User::factory()->create();
        $sessionDuration = 3600;
        $event = new UserLoggedOut($user, $sessionDuration);

        $listener->handle($event);
    }

    /**
     * Test that the LogUserActivity listener handles failures correctly.
     */
    public function test_log_user_activity_listener_handles_failures()
    {
        $activityLogService = Mockery::mock(ActivityLogService::class);
        $activityLogService->shouldReceive('logUserSession')
            ->once()
            ->andThrow(new \Exception('Failed to log user activity'));

        $listener = new LogUserActivity($activityLogService);
        $user = User::factory()->create();
        $sessionDuration = 3600;
        $event = new UserLoggedOut($user, $sessionDuration);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to log user activity');

        $listener->handle($event);
    }

    /**
     * Test that the LogUserActivity listener logs failures.
     */
    public function test_log_user_activity_listener_logs_failures()
    {
        $activityLogService = Mockery::mock(ActivityLogService::class);
        $activityLogService->shouldReceive('logUserSession')
            ->once()
            ->andThrow(new \Exception('Failed to log user activity'));

        $listener = new LogUserActivity($activityLogService);
        $user = User::factory()->create();
        $sessionDuration = 3600;
        $event = new UserLoggedOut($user, $sessionDuration);

        try {
            $listener->handle($event);
        } catch (\Exception $e) {
            $listener->failed($event, $e);
        }

        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'activity-logs',
            'connection' => 'default',
        ]);
    }

    /**
     * Test that the LogUserActivity listener is registered for the event.
     */
    public function test_log_user_activity_listener_is_registered()
    {
        Event::fake();

        $user = User::factory()->create();
        $sessionDuration = 3600;
        event(new UserLoggedOut($user, $sessionDuration));

        Event::assertListening(
            UserLoggedOut::class,
            LogUserActivity::class
        );
    }

    /**
     * Test that the NotifyProfileUpdate listener is queued.
     */
    public function test_notify_profile_update_listener_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(NotifyProfileUpdate::class)
            )
        );
    }

    /**
     * Test that the NotifyProfileUpdate listener uses the correct queue.
     */
    public function test_notify_profile_update_listener_uses_correct_queue()
    {
        $listener = new NotifyProfileUpdate(Mockery::mock(NotificationService::class));

        $this->assertEquals('notifications', $listener->viaQueue());
    }

    /**
     * Test that the NotifyProfileUpdate listener uses the correct connection.
     */
    public function test_notify_profile_update_listener_uses_correct_connection()
    {
        $listener = new NotifyProfileUpdate(Mockery::mock(NotificationService::class));

        $this->assertEquals('default', $listener->viaConnection());
    }

    /**
     * Test that the NotifyProfileUpdate listener handles the event correctly.
     */
    public function test_notify_profile_update_listener_handles_event()
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyProfileUpdate')
            ->once()
            ->with(
                Mockery::type(User::class),
                Mockery::type('array'),
                Mockery::type(\Carbon\Carbon::class)
            );

        $listener = new NotifyProfileUpdate($notificationService);
        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        $event = new UserProfileUpdated($user, $profileData);

        $listener->handle($event);
    }

    /**
     * Test that the NotifyProfileUpdate listener handles failures correctly.
     */
    public function test_notify_profile_update_listener_handles_failures()
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyProfileUpdate')
            ->once()
            ->andThrow(new \Exception('Failed to notify profile update'));

        $listener = new NotifyProfileUpdate($notificationService);
        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        $event = new UserProfileUpdated($user, $profileData);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to notify profile update');

        $listener->handle($event);
    }

    /**
     * Test that the NotifyProfileUpdate listener logs failures.
     */
    public function test_notify_profile_update_listener_logs_failures()
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyProfileUpdate')
            ->once()
            ->andThrow(new \Exception('Failed to notify profile update'));

        $listener = new NotifyProfileUpdate($notificationService);
        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        $event = new UserProfileUpdated($user, $profileData);

        try {
            $listener->handle($event);
        } catch (\Exception $e) {
            $listener->failed($event, $e);
        }

        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'notifications',
            'connection' => 'default',
        ]);
    }

    /**
     * Test that the NotifyProfileUpdate listener is registered for the event.
     */
    public function test_notify_profile_update_listener_is_registered()
    {
        Event::fake();

        $user = User::factory()->create();
        $profileData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];
        event(new UserProfileUpdated($user, $profileData));

        Event::assertListening(
            UserProfileUpdated::class,
            NotifyProfileUpdate::class
        );
    }

    /**
     * Test that the LogPasswordChange listener is queued.
     */
    public function test_log_password_change_listener_is_queued()
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(LogPasswordChange::class)
            )
        );
    }

    /**
     * Test that the LogPasswordChange listener uses the correct queue.
     */
    public function test_log_password_change_listener_uses_correct_queue()
    {
        $listener = new LogPasswordChange(Mockery::mock(ActivityLogService::class));

        $this->assertEquals('security-logs', $listener->viaQueue());
    }

    /**
     * Test that the LogPasswordChange listener uses the correct connection.
     */
    public function test_log_password_change_listener_uses_correct_connection()
    {
        $listener = new LogPasswordChange(Mockery::mock(ActivityLogService::class));

        $this->assertEquals('default', $listener->viaConnection());
    }

    /**
     * Test that the LogPasswordChange listener handles the event correctly.
     */
    public function test_log_password_change_listener_handles_event()
    {
        $activityLogService = Mockery::mock(ActivityLogService::class);
        $activityLogService->shouldReceive('logPasswordChange')
            ->once()
            ->with(
                Mockery::type(User::class),
                Mockery::type(\Carbon\Carbon::class),
                Mockery::type('string')
            );

        $listener = new LogPasswordChange($activityLogService);
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $event = new UserPasswordChanged($user, $ipAddress);

        $listener->handle($event);
    }

    /**
     * Test that the LogPasswordChange listener handles failures correctly.
     */
    public function test_log_password_change_listener_handles_failures()
    {
        $activityLogService = Mockery::mock(ActivityLogService::class);
        $activityLogService->shouldReceive('logPasswordChange')
            ->once()
            ->andThrow(new \Exception('Failed to log password change'));

        $listener = new LogPasswordChange($activityLogService);
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $event = new UserPasswordChanged($user, $ipAddress);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to log password change');

        $listener->handle($event);
    }

    /**
     * Test that the LogPasswordChange listener logs failures.
     */
    public function test_log_password_change_listener_logs_failures()
    {
        $activityLogService = Mockery::mock(ActivityLogService::class);
        $activityLogService->shouldReceive('logPasswordChange')
            ->once()
            ->andThrow(new \Exception('Failed to log password change'));

        $listener = new LogPasswordChange($activityLogService);
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $event = new UserPasswordChanged($user, $ipAddress);

        try {
            $listener->handle($event);
        } catch (\Exception $e) {
            $listener->failed($event, $e);
        }

        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'security-logs',
            'connection' => 'default',
        ]);
    }

    /**
     * Test that the LogPasswordChange listener is registered for the event.
     */
    public function test_log_password_change_listener_is_registered()
    {
        Event::fake();

        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        event(new UserPasswordChanged($user, $ipAddress));

        Event::assertListening(
            UserPasswordChanged::class,
            LogPasswordChange::class
        );
    }
} 