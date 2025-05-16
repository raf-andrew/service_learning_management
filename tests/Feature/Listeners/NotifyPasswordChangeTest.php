<?php

namespace Tests\Feature\Listeners;

use App\Events\UserPasswordChanged;
use App\Listeners\NotifyPasswordChange;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\TestReporter;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Listeners/NotifyPasswordChangeTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status In Progress
 * @job-code EVT-005-LISTENER-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Listeners
 * @see \App\Listeners\NotifyPasswordChange
 * 
 * Test suite for the NotifyPasswordChange listener.
 * Validates notification and security handling for password changes.
 * 
 * @OpenAPI\Tag(name="User Events Tests", description="Password change notification tests")
 */
class NotifyPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $event;
    protected $listener;
    protected $notificationService;
    protected $securityService;
    protected $reporter;
    protected $ipAddress = '192.168.1.1';

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->event = new UserPasswordChanged($this->user, $this->ipAddress);
        
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->securityService = Mockery::mock(SecurityService::class);
        
        $this->listener = new NotifyPasswordChange(
            $this->notificationService,
            $this->securityService
        );
        
        $this->reporter = new TestReporter('NotifyPasswordChangeTest');
    }

    /**
     * Test that the listener handles the event correctly.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event handling",
     *     description="Verifies that the listener handles the event correctly"
     * )
     */
    public function test_listener_handles_event()
    {
        $this->reporter->startTest('test_listener_handles_event');
        
        try {
            $this->notificationService->shouldReceive('notifyPasswordChange')
                ->once()
                ->with(
                    $this->user,
                    $this->event->changedAt,
                    $this->ipAddress
                );
            
            $this->securityService->shouldReceive('logPasswordChange')
                ->once()
                ->with(
                    $this->user,
                    $this->event->changedAt,
                    $this->ipAddress
                );
            
            $this->securityService->shouldReceive('isSuspiciousActivity')
                ->once()
                ->with($this->ipAddress)
                ->andReturn(false);
            
            $this->listener->handle($this->event);
            
            $this->reporter->addTestResult('test_listener_handles_event', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_listener_handles_event', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test that the listener handles suspicious activity.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test suspicious activity handling",
     *     description="Verifies that the listener handles suspicious activity correctly"
     * )
     */
    public function test_listener_handles_suspicious_activity()
    {
        $this->reporter->startTest('test_listener_handles_suspicious_activity');
        
        try {
            $this->notificationService->shouldReceive('notifyPasswordChange')
                ->once()
                ->with(
                    $this->user,
                    $this->event->changedAt,
                    $this->ipAddress
                );
            
            $this->securityService->shouldReceive('logPasswordChange')
                ->once()
                ->with(
                    $this->user,
                    $this->event->changedAt,
                    $this->ipAddress
                );
            
            $this->securityService->shouldReceive('isSuspiciousActivity')
                ->once()
                ->with($this->ipAddress)
                ->andReturn(true);
            
            $this->securityService->shouldReceive('flagSuspiciousActivity')
                ->once()
                ->with(
                    $this->user,
                    'password_change',
                    $this->ipAddress
                );
            
            $this->listener->handle($this->event);
            
            $this->reporter->addTestResult('test_listener_handles_suspicious_activity', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_listener_handles_suspicious_activity', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test that the listener handles failures correctly.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test failure handling",
     *     description="Verifies that the listener handles failures correctly"
     * )
     */
    public function test_listener_handles_failures()
    {
        $this->reporter->startTest('test_listener_handles_failures');
        
        try {
            $this->notificationService->shouldReceive('notifyPasswordChange')
                ->once()
                ->andThrow(new \Exception('Failed to send notification'));
            
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Failed to send notification');
            
            $this->listener->handle($this->event);
            
            $this->reporter->addTestResult('test_listener_handles_failures', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_listener_handles_failures', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test that the listener logs failures.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test failure logging",
     *     description="Verifies that the listener logs failures correctly"
     * )
     */
    public function test_listener_logs_failures()
    {
        $this->reporter->startTest('test_listener_logs_failures');
        
        try {
            $exception = new \Exception('Failed to send notification');
            
            $this->notificationService->shouldReceive('notifyPasswordChange')
                ->once()
                ->andThrow($exception);
            
            try {
                $this->listener->handle($this->event);
            } catch (\Exception $e) {
                $this->listener->failed($this->event, $e);
            }
            
            $this->assertDatabaseHas('failed_jobs', [
                'queue' => 'notifications',
                'connection' => 'default',
            ]);
            
            $this->reporter->addTestResult('test_listener_logs_failures', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_listener_logs_failures', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test that the listener is registered for the event.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event registration",
     *     description="Verifies that the listener is registered for the event"
     * )
     */
    public function test_listener_is_registered()
    {
        $this->reporter->startTest('test_listener_is_registered');
        
        try {
            Event::fake();
            
            event(new UserPasswordChanged($this->user, $this->ipAddress));
            
            Event::assertListening(
                UserPasswordChanged::class,
                NotifyPasswordChange::class
            );
            
            $this->reporter->addTestResult('test_listener_is_registered', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_listener_is_registered', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test that the listener is queued.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test queue configuration",
     *     description="Verifies that the listener is properly queued"
     * )
     */
    public function test_listener_is_queued()
    {
        $this->reporter->startTest('test_listener_is_queued');
        
        try {
            $this->assertTrue(
                in_array(
                    \Illuminate\Contracts\Queue\ShouldQueue::class,
                    class_implements(NotifyPasswordChange::class)
                )
            );
            
            $this->assertEquals('notifications', $this->listener->viaQueue());
            $this->assertEquals('default', $this->listener->viaConnection());
            
            $this->reporter->addTestResult('test_listener_is_queued', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_listener_is_queued', false, $e->getMessage());
            throw $e;
        }
    }
} 