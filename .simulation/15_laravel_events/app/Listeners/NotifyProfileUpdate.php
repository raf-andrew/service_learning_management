<?php

namespace App\Listeners;

use App\Events\UserProfileUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyProfileUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param  \App\Services\NotificationService  $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserProfileUpdated  $event
     * @return void
     */
    public function handle(UserProfileUpdated $event)
    {
        try {
            $this->notificationService->notifyProfileUpdate(
                $event->user,
                $event->profileData,
                $event->updatedAt
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify profile update', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\UserProfileUpdated  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(UserProfileUpdated $event, \Throwable $exception)
    {
        Log::error('Notify profile update job failed', [
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