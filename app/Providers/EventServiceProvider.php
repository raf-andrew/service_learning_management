<?php

namespace App\Providers;

use App\Events\UserProfileUpdated;
use App\Events\UserPasswordChanged;
use App\Events\ServiceStatusChanged;
use App\Events\HealthAlertTriggered;
use App\Events\HealthCheckCompleted;
use App\Events\DeveloperCredentialCreated;
use App\Listeners\NotifyPasswordChange;
use App\Listeners\UpdateServiceStatus;
use App\Listeners\HandleHealthAlerts;
use App\Listeners\ProcessHealthCheckResults;
use App\Listeners\LogDeveloperCredentialActivity;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserProfileUpdated::class => [
            // Add listeners for user profile updates
        ],
        UserPasswordChanged::class => [
            NotifyPasswordChange::class,
        ],
        ServiceStatusChanged::class => [
            UpdateServiceStatus::class,
        ],
        HealthAlertTriggered::class => [
            HandleHealthAlerts::class,
        ],
        HealthCheckCompleted::class => [
            ProcessHealthCheckResults::class,
        ],
        DeveloperCredentialCreated::class => [
            LogDeveloperCredentialActivity::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Register global event listeners
        $this->registerGlobalListeners();
    }

    /**
     * Register global event listeners that apply to all events.
     *
     * @return void
     */
    protected function registerGlobalListeners()
    {
        // Log all events for audit purposes
        Event::listen('*', function ($eventName, array $data) {
            if (config('app.debug')) {
                \Log::info("Event fired: {$eventName}", [
                    'event' => $eventName,
                    'data' => $data,
                    'timestamp' => now()->toISOString(),
                ]);
            }
        });

        // Monitor event performance
        Event::listen('*', function ($eventName, array $data) {
            $startTime = microtime(true);
            
            return function () use ($eventName, $startTime) {
                $duration = (microtime(true) - $startTime) * 1000;
                
                if ($duration > 100) { // Log slow events (>100ms)
                    \Log::warning("Slow event detected: {$eventName}", [
                        'event' => $eventName,
                        'duration_ms' => round($duration, 2),
                        'timestamp' => now()->toISOString(),
                    ]);
                }
            };
        });
    }
}
