<?php

namespace App\Events;

use App\Models\HealthCheck;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $healthCheck;
    public $oldStatus;
    public $newStatus;

    public function __construct(HealthCheck $healthCheck, string $oldStatus, string $newStatus)
    {
        $this->healthCheck = $healthCheck;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
} 