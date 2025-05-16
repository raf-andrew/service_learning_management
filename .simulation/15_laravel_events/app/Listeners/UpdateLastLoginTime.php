<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateLastLoginTime implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The user service instance.
     *
     * @var \App\Services\UserService
     */
    protected $userService;

    /**
     * Create the event listener.
     *
     * @param  \App\Services\UserService  $userService
     * @return void
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserLoggedIn  $event
     * @return void
     */
    public function handle(UserLoggedIn $event)
    {
        try {
            $this->userService->updateLastLoginTime($event->user, $event->loginTime);
        } catch (\Exception $e) {
            Log::error('Failed to update last login time', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\UserLoggedIn  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(UserLoggedIn $event, \Throwable $exception)
    {
        Log::error('Update last login time job failed', [
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
        return 'user-updates';
    }
} 