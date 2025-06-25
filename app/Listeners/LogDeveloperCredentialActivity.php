<?php

namespace App\Listeners;

use App\Events\DeveloperCredentialCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for logging developer credential activities.
 * 
 * This listener logs all developer credential activities for:
 * - Audit purposes
 * - Security monitoring
 * - Compliance reporting
 * - Activity tracking
 */
class LogDeveloperCredentialActivity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\DeveloperCredentialCreated  $event
     * @return void
     */
    public function handle(DeveloperCredentialCreated $event): void
    {
        $credential = $event->credential;

        Log::info('Developer credential created', [
            'credential_id' => $credential->id,
            'user_id' => $credential->user_id,
            'github_username' => $credential->github_username,
            'permissions' => $credential->permissions,
            'created_at' => $credential->created_at->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Additional audit logging for compliance
        if (config('app.env') === 'production') {
            Log::channel('audit')->info('Developer credential activity', [
                'action' => 'credential_created',
                'credential_id' => $credential->id,
                'user_id' => $credential->user_id,
                'timestamp' => now()->toISOString(),
                'session_id' => session()->getId(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\DeveloperCredentialCreated  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(DeveloperCredentialCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to log developer credential activity', [
            'credential_id' => $event->credential->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
} 