<?php

namespace Tests\Simulations\HealthMonitoring\Unit\Events;

use Tests\TestCase;
use App\Events\ServiceStatusChanged;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_contains_correct_data()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service',
            'type' => 'http',
            'target' => 'http://example.com'
        ]);

        $oldStatus = 'healthy';
        $newStatus = 'unhealthy';

        // Create event
        $event = new ServiceStatusChanged($healthCheck, $oldStatus, $newStatus);

        // Assert event contains correct data
        $this->assertSame($healthCheck, $event->healthCheck);
        $this->assertEquals($oldStatus, $event->oldStatus);
        $this->assertEquals($newStatus, $event->newStatus);
        $this->assertEquals('test_service', $event->healthCheck->name);
    }

    public function test_event_serializes_correctly()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create();
        $oldStatus = 'healthy';
        $newStatus = 'unhealthy';

        // Create event
        $event = new ServiceStatusChanged($healthCheck, $oldStatus, $newStatus);

        // Serialize and unserialize
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert data is preserved
        $this->assertEquals($healthCheck->id, $unserialized->healthCheck->id);
        $this->assertEquals($oldStatus, $unserialized->oldStatus);
        $this->assertEquals($newStatus, $unserialized->newStatus);
    }
} 