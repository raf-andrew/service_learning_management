<?php

namespace Tests\Simulations\HealthMonitoring\Unit\Events;

use Tests\TestCase;
use App\Events\HealthCheckCompleted;
use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HealthCheckCompletedTest extends TestCase
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

        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $healthCheck->id,
            'status' => 'healthy',
            'response_time' => 150,
            'details' => json_encode(['message' => 'Service is healthy'])
        ]);

        // Create event
        $event = new HealthCheckCompleted($healthCheck, $result);

        // Assert event contains correct data
        $this->assertSame($healthCheck, $event->healthCheck);
        $this->assertSame($result, $event->result);
        $this->assertEquals('test_service', $event->healthCheck->name);
        $this->assertEquals('healthy', $event->result->status);
    }

    public function test_event_serializes_correctly()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create();
        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $healthCheck->id
        ]);

        // Create event
        $event = new HealthCheckCompleted($healthCheck, $result);

        // Serialize and unserialize
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert data is preserved
        $this->assertEquals($healthCheck->id, $unserialized->healthCheck->id);
        $this->assertEquals($result->id, $unserialized->result->id);
    }
} 