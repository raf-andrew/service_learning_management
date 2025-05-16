<?php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogUserActivity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The activity log service instance.
     *
     * @var \App\Services\ActivityLogService
     */
    protected $activityLogService;

    /**
     * Create the event listener.
     *
     * @param  \App\Services\ActivityLogService  $activityLogService
     * @return void
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserLoggedOut  $event
     * @return void
     */
    public function handle(UserLoggedOut $event)
    {
        try {
            $this->activityLogService->logUserSession(
                $event->user,
                $event->logoutTime,
                $event->sessionDuration
            );
        } catch (\Exception $e) {
            Log::error('Failed to log user activity', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\UserLoggedOut  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(UserLoggedOut $event, \Throwable $exception)
    {
        Log::error('Log user activity job failed', [
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
        return 'activity-logs';
    }
} 