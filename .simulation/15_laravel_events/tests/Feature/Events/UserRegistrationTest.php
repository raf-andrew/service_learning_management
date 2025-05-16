<?php

namespace Tests\Feature\Events;

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Mail\WelcomeEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Events/UserRegistrationTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-002-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Events
 * @see \App\Events\UserRegistered
 * @see \App\Mail\WelcomeEmail
 * 
 * Test suite for the UserRegistered event.
 * Validates event dispatching, broadcasting, and email sending functionality.
 * 
 * @OpenAPI\Tag(name="User Events Tests", description="User registration event tests")
 */
class UserRegistrationTest extends TestCase
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
        Mail::fake();
        Broadcast::fake();
    }

    /**
     * Test that the UserRegistered event is dispatched correctly.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event dispatching",
     *     description="Verifies that the UserRegistered event is dispatched with correct data"
     * )
     */
    public function it_dispatches_user_registered_event()
    {
        $user = User::factory()->create();

        event(new UserRegistered($user));

        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Test that a welcome email is sent to the registered user.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test welcome email",
     *     description="Verifies that a welcome email is sent to the registered user"
     * )
     */
    public function it_sends_welcome_email()
    {
        $user = User::factory()->create();

        event(new UserRegistered($user));

        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
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

        event(new UserRegistered($user));

        Broadcast::assertSentOn(
            new PrivateChannel('user.' . $user->id),
            UserRegistered::class
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

        event(new UserRegistered($user));

        Broadcast::assertSentOn(
            new PrivateChannel('user.' . $user->id),
            UserRegistered::class,
            function ($event) use ($user) {
                $broadcastData = $event->broadcastWith();
                return $broadcastData['user']['id'] === $user->id
                    && $broadcastData['user']['name'] === $user->name
                    && $broadcastData['user']['email'] === $user->email;
            }
        );
    }

    /**
     * Test handling of welcome email failures.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test email failure handling",
     *     description="Verifies that email failures are handled correctly"
     * )
     */
    public function it_handles_welcome_email_failure()
    {
        $user = User::factory()->create();
        Log::shouldReceive('error')->once();

        Mail::fake();
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Failed to send email'));

        event(new UserRegistered($user));
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

        event(new UserRegistered($user));

        Broadcast::assertNotSent(UserRegistered::class);
    }

    /**
     * Test that the listener retries sending the welcome email on failure.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test email retry",
     *     description="Verifies that the listener retries sending the welcome email on failure"
     * )
     */
    public function it_retries_failed_welcome_email()
    {
        $user = User::factory()->create();

        Mail::fake();
        Mail::shouldReceive('to')
            ->times(3)
            ->andThrow(new \Exception('Failed to send email'));

        event(new UserRegistered($user));

        Mail::assertQueued(WelcomeEmail::class, 3);
    }

    /**
     * Test that failures in sending the welcome email are logged.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test failure logging",
     *     description="Verifies that failures in sending the welcome email are logged"
     * )
     */
    public function it_logs_failed_welcome_email()
    {
        $user = User::factory()->create();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return str_contains($message, 'Failed to send welcome email')
                    && $context['user_id'] === $user->id
                    && $context['email'] === $user->email;
            });

        Mail::fake();
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Failed to send email'));

        event(new UserRegistered($user));
    }
} 