<?php

namespace Tests\Simulations\HealthMonitoring\Unit\Events;

use Tests\TestCase;
use App\Events\HealthAlertTriggered;
use App\Models\HealthAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HealthAlertTriggeredTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_contains_correct_data()
    {
        // Create test data
        $alert = HealthAlert::factory()->create([
            'service_name' => 'test_service',
            'level' => 'critical',
            'type' => 'cpu_usage',
            'message' => 'CPU usage is above threshold'
        ]);

        // Create event
        $event = new HealthAlertTriggered($alert);

        // Assert event contains correct data
        $this->assertSame($alert, $event->alert);
        $this->assertEquals('test_service', $event->alert->service_name);
        $this->assertEquals('critical', $event->alert->level);
        $this->assertEquals('cpu_usage', $event->alert->type);
    }

    public function test_event_serializes_correctly()
    {
        // Create test data
        $alert = HealthAlert::factory()->create();

        // Create event
        $event = new HealthAlertTriggered($alert);

        // Serialize and unserialize
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert data is preserved
        $this->assertEquals($alert->id, $unserialized->alert->id);
        $this->assertEquals($alert->service_name, $unserialized->alert->service_name);
    }
} 