<?php

namespace App\Events;

use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HealthCheckCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $healthCheck;
    public $result;

    public function __construct(HealthCheck $healthCheck, HealthCheckResult $result)
    {
        $this->healthCheck = $healthCheck;
        $this->result = $result;
    }
} 