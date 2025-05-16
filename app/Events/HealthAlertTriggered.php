<?php

namespace App\Events;

use App\Models\HealthAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HealthAlertTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $alert;

    public function __construct(HealthAlert $alert)
    {
        $this->alert = $alert;
    }
} 