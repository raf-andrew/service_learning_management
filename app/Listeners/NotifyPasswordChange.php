<?php

namespace App\Listeners;

use App\Events\UserPasswordChanged;
use App\Services\NotificationService;
use App\Services\SecurityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * @laravel-simulation
 * @component-type Listener
 * @test-coverage tests/Feature/Listeners/NotifyPasswordChangeTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status In Progress
 * @job-code EVT-005-LISTENER
 * @since 1.0.0
 * @author System
 * @package App\Listeners
 * @see \App\Events\UserPasswordChanged
 * 
 * Listener for the UserPasswordChanged event.
 * Handles security notifications and alerts when a user changes their password.
 */
class NotifyPasswordChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * The security service instance.
     *
     * @var \App\Services\SecurityService
     */
    protected $securityService;

    /**
     * Create the event listener.
     *
     * @param  \App\Services\NotificationService  $notificationService
     * @param  \App\Services\SecurityService  $securityService
     * @return void
     */
    public function __construct(
        NotificationService $notificationService,
        SecurityService $securityService
    ) {
        $this->notificationService = $notificationService;
        $this->securityService = $securityService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserPasswordChanged  $event
     * @return void
     */
    public function handle(UserPasswordChanged $event)
    {
        try {
            // Notify the user about the password change
            $this->notificationService->notifyPasswordChange(
                $event->user,
                $event->changedAt,
                $event->ipAddress
            );

            // Log the security event
            $this->securityService->logPasswordChange(
                $event->user,
                $event->changedAt,
                $event->ipAddress
            );

            // Check for suspicious activity
            if ($this->securityService->isSuspiciousActivity($event->ipAddress)) {
                $this->securityService->flagSuspiciousActivity(
                    $event->user,
                    'password_change',
                    $event->ipAddress
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle password change notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\UserPasswordChanged  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(UserPasswordChanged $event, \Throwable $exception)
    {
        Log::error('Password change notification job failed', [
            'user_id' => $event->user->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the queue connection that should handle the job.
     *
     * @return string|null
     */
    public function viaConnection()
    {
        return 'default';
    }

    /**
     * Get the queue that should handle the job.
     *
     * @return string|null
     */
    public function viaQueue()
    {
        return 'notifications';
    }
} 