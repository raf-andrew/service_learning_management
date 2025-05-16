<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Mockery;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;
    protected $firebaseMock;
    protected $user;
    protected $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'fcm_token' => 'test_fcm_token'
        ]);

        // Create test notification template
        $this->template = NotificationTemplate::factory()->create([
            'type' => 'test_notification',
            'channel' => 'email',
            'subject' => 'Test Subject {name}',
            'content' => 'Test Content {name}'
        ]);

        // Create in-app template
        NotificationTemplate::factory()->create([
            'type' => 'test_notification',
            'channel' => 'in_app',
            'subject' => 'Test In-App Subject {name}',
            'content' => 'Test In-App Content {name}'
        ]);

        // Create push template
        NotificationTemplate::factory()->create([
            'type' => 'test_notification',
            'channel' => 'push',
            'subject' => 'Test Push Subject {name}',
            'content' => 'Test Push Content {name}'
        ]);

        // Mock Firebase
        $this->firebaseMock = Mockery::mock('firebase.messaging');
        $this->app->instance('firebase.messaging', $this->firebaseMock);

        // Create NotificationService instance
        $this->notificationService = new NotificationService(
            new Notification(),
            new NotificationTemplate()
        );
    }

    public function test_sends_email_notification_successfully()
    {
        Mail::fake();

        $data = ['name' => 'John Doe'];

        $result = $this->notificationService->sendEmailNotification(
            $this->user,
            'test_notification',
            $data
        );

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) {
            return $mail->hasTo($this->user->email) &&
                   $mail->subject === 'Test Subject John Doe';
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'test_notification',
            'channel' => 'email',
            'subject' => 'Test Subject John Doe',
            'content' => 'Test Content John Doe',
            'status' => 'sent'
        ]);
    }

    public function test_sends_in_app_notification_successfully()
    {
        Event::fake();

        $data = ['name' => 'John Doe'];

        $notification = $this->notificationService->sendInAppNotification(
            $this->user,
            'test_notification',
            $data
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals('Test In-App Subject John Doe', $notification->subject);
        $this->assertEquals('Test In-App Content John Doe', $notification->content);
        $this->assertEquals('unread', $notification->status);

        Event::assertDispatched(\App\Events\NewNotification::class);
    }

    public function test_sends_push_notification_successfully()
    {
        $data = ['name' => 'John Doe'];

        $this->firebaseMock->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $notification = $this->notificationService->sendPushNotification(
            $this->user,
            'test_notification',
            $data
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals('Test Push Subject John Doe', $notification->subject);
        $this->assertEquals('Test Push Content John Doe', $notification->content);
        $this->assertEquals('sent', $notification->status);
    }

    public function test_marks_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread'
        ]);

        $result = $this->notificationService->markAsRead($notification);

        $this->assertEquals('read', $result->status);
    }

    public function test_marks_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'unread'
        ]);

        $result = $this->notificationService->markAllAsRead($this->user);

        $this->assertTrue($result);
        $this->assertEquals(0, Notification::where('user_id', $this->user->id)
            ->where('status', 'unread')
            ->count());
    }

    public function test_gets_user_notifications_with_filters()
    {
        // Create notifications with different types and statuses
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'type1',
            'status' => 'unread'
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'type2',
            'status' => 'read'
        ]);

        // Test filtering by type
        $notifications = $this->notificationService->getUserNotifications(
            $this->user,
            ['type' => 'type1']
        );
        $this->assertCount(1, $notifications);
        $this->assertEquals('type1', $notifications->first()->type);

        // Test filtering by status
        $notifications = $this->notificationService->getUserNotifications(
            $this->user,
            ['status' => 'read']
        );
        $this->assertCount(1, $notifications);
        $this->assertEquals('read', $notifications->first()->status);
    }

    public function test_throws_exception_for_missing_template()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email template not found for type: non_existent_type');

        $this->notificationService->sendEmailNotification(
            $this->user,
            'non_existent_type',
            ['name' => 'John Doe']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 