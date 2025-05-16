<?php

namespace App\Listeners;

use App\Events\HealthAlertTriggered;
use App\Jobs\HealthAlertNotificationJob;
use Illuminate\Support\Facades\Log;

class HandleHealthAlerts
{
    public function handle(HealthAlertTriggered $event): void
    {
        try {
            $alert = $event->alert;

            // Dispatch notification job
            HealthAlertNotificationJob::dispatch($alert);

            Log::info('Health alert handled', [
                'alert_id' => $alert->id,
                'service' => $alert->service_name,
                'level' => $alert->level,
                'type' => $alert->type
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle health alert', [
                'alert_id' => $event->alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 