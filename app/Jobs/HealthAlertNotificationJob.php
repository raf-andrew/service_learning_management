<?php

namespace App\Jobs;

use App\Models\HealthAlert;
use App\Notifications\HealthAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HealthAlertNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $alert;
    protected $retryCount = 0;
    protected $maxRetries = 3;

    public function __construct(HealthAlert $alert)
    {
        $this->alert = $alert;
    }

    public function handle(): void
    {
        try {
            // Get notification recipients based on alert level and type
            $recipients = $this->getNotificationRecipients();

            // Send notifications
            Notification::send($recipients, new HealthAlertNotification($this->alert));

            Log::info('Alert notification sent', [
                'alert_id' => $this->alert->id,
                'service' => $this->alert->service_name,
                'level' => $this->alert->level,
                'recipients' => count($recipients)
            ]);
        } catch (\Exception $e) {
            Log::error('Alert notification failed', [
                'alert_id' => $this->alert->id,
                'error' => $e->getMessage(),
                'retries' => $this->retryCount
            ]);

            if ($this->retryCount < $this->maxRetries) {
                $this->retryCount++;
                $this->release(30); // Retry after 30 seconds
                return;
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Alert notification job failed', [
            'alert_id' => $this->alert->id,
            'error' => $exception->getMessage(),
            'retries' => $this->retryCount
        ]);
    }

    protected function getNotificationRecipients()
    {
        // This is a placeholder. In a real application, you would:
        // 1. Get recipients from a configuration or database
        // 2. Filter based on alert level and type
        // 3. Consider notification preferences
        return collect([
            // Example recipients
            // new User(['email' => 'admin@example.com']),
            // new User(['email' => 'oncall@example.com']),
        ]);
    }

    public function tags(): array
    {
        return [
            'alert',
            'notification',
            $this->alert->service_name,
            $this->alert->level
        ];
    }
} 