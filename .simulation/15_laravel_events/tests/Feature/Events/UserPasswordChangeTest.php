<?php

namespace Tests\Feature\Events;

use App\Events\UserPasswordChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Carbon\Carbon;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Events/UserPasswordChangeTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-005-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Events
 * @see \App\Events\UserPasswordChanged
 * 
 * Test suite for the UserPasswordChanged event.
 * Validates event dispatching, broadcasting, and security measures.
 * 
 * @OpenAPI\Tag(name="User Events Tests", description="User password change event tests")
 */
class UserPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Broadcast::fake();
    }

    /**
     * Test that the UserPasswordChanged event is dispatched correctly.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event dispatching",
     *     description="Verifies that the UserPasswordChanged event is dispatched with correct data"
     * )
     */
    public function it_dispatches_user_password_changed_event()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';

        event(new UserPasswordChanged($user, $ipAddress));

        Event::assertDispatched(UserPasswordChanged::class, function ($event) use ($user, $ipAddress) {
            return $event->user->id === $user->id
                && $event->ipAddress === $ipAddress;
        });
    }

    /**
     * Test that the event broadcasts on the correct private channel.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test private channel broadcasting",
     *     description="Verifies that the event broadcasts on the correct private channel"
     * )
     */
    public function it_broadcasts_to_private_channel()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';

        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertSentOn(
            new PrivateChannel('user.' . $user->id),
            UserPasswordChanged::class
        );
    }

    /**
     * Test that the event broadcasts on the presence channel.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test presence channel broadcasting",
     *     description="Verifies that the event broadcasts on the presence channel"
     * )
     */
    public function it_broadcasts_to_presence_channel()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';

        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertSentOn(
            new PresenceChannel('users.online'),
            UserPasswordChanged::class
        );
    }

    /**
     * Test that the broadcast data contains the correct user information.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test broadcast data",
     *     description="Verifies that the broadcast data contains the correct user information"
     * )
     */
    public function it_includes_correct_data_in_broadcast()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';

        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertSentOn(
            new PrivateChannel('user.' . $user->id),
            UserPasswordChanged::class,
            function ($event) use ($user, $ipAddress) {
                $broadcastData = $event->broadcastWith();
                return $broadcastData['user']['id'] === $user->id
                    && $broadcastData['user']['name'] === $user->name
                    && $broadcastData['user']['email'] === $user->email
                    && $broadcastData['ip_address'] === $ipAddress;
            }
        );
    }

    /**
     * Test that the event includes the correct change timestamp.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test change timestamp",
     *     description="Verifies that the event includes the correct change timestamp"
     * )
     */
    public function it_includes_correct_change_timestamp()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $now = Carbon::now();

        Carbon::setTestNow($now);

        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertSentOn(
            new PrivateChannel('user.' . $user->id),
            UserPasswordChanged::class,
            function ($event) use ($now) {
                $broadcastData = $event->broadcastWith();
                return $broadcastData['changed_at'] === $now->toIso8601String();
            }
        );
    }

    /**
     * Test that the event handles invalid IP address.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test invalid IP handling",
     *     description="Verifies that the event handles invalid IP address correctly"
     * )
     */
    public function it_handles_invalid_ip_address()
    {
        $user = User::factory()->create();
        $this->expectException(\InvalidArgumentException::class);

        event(new UserPasswordChanged($user, 'invalid-ip'));
    }

    /**
     * Test that the event handles invalid user data.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test invalid user handling",
     *     description="Verifies that the event handles invalid user data correctly"
     * )
     */
    public function it_handles_invalid_user_data()
    {
        $this->expectException(\InvalidArgumentException::class);

        event(new UserPasswordChanged(null, '192.168.1.1'));
    }

    /**
     * Test that the event logs password change attempts.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test password change logging",
     *     description="Verifies that the event logs password change attempts"
     * )
     */
    public function it_logs_password_change_attempt()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        Log::shouldReceive('info')->once();

        event(new UserPasswordChanged($user, $ipAddress));
    }

    /**
     * Test that the event updates user's last password change.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test last password change update",
     *     description="Verifies that the event updates the user's last password change"
     * )
     */
    public function it_updates_last_password_change()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';

        event(new UserPasswordChanged($user, $ipAddress));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'last_password_change_at' => now(),
        ]);
    }

    /**
     * Test that the event respects the user's broadcast setting.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test broadcast setting",
     *     description="Verifies that the event respects the user's broadcast setting"
     * )
     */
    public function it_respects_broadcast_setting()
    {
        $user = User::factory()->create(['should_broadcast' => false]);
        $ipAddress = '192.168.1.1';

        event(new UserPasswordChanged($user, $ipAddress));

        Broadcast::assertNotSent(UserPasswordChanged::class);
    }
} 