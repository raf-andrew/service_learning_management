<?php

namespace App\Listeners;

use App\Events\ServiceStatusChanged;
use Illuminate\Support\Facades\Log;

class UpdateServiceStatus
{
    public function handle(ServiceStatusChanged $event): void
    {
        $service = $event->healthCheck;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;

        // Update the service status in the database or cache as needed
        $service->status = $newStatus;
        $service->save();

        Log::info('Service status updated', [
            'service' => $service->name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }
} 