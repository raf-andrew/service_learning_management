<?php

namespace Tests\Simulations\HealthMonitoring\Unit\Listeners;

use Tests\TestCase;
use App\Events\ServiceStatusChanged;
use App\Listeners\UpdateServiceStatus;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class UpdateServiceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new UpdateServiceStatus();
    }

    public function test_updates_service_status()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service',
            'type' => 'http',
            'target' => 'http://example.com',
            'status' => 'healthy'
        ]);

        $oldStatus = 'healthy';
        $newStatus = 'unhealthy';

        $event = new ServiceStatusChanged($healthCheck, $oldStatus, $newStatus);

        // Handle event
        $this->listener->handle($event);

        // Refresh model from database
        $healthCheck->refresh();

        // Assert status was updated
        $this->assertEquals($newStatus, $healthCheck->status);
    }

    public function test_logs_status_change()
    {
        Log::spy();

        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service',
            'status' => 'healthy'
        ]);

        $oldStatus = 'healthy';
        $newStatus = 'unhealthy';

        $event = new ServiceStatusChanged($healthCheck, $oldStatus, $newStatus);

        // Handle event
        $this->listener->handle($event);

        // Assert log was created
        Log::shouldHaveReceived('info')
            ->with('Service status updated', [
                'service' => 'test_service',
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
    }

    public function test_handles_exception()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service',
            'status' => 'healthy'
        ]);

        $oldStatus = 'healthy';
        $newStatus = 'unhealthy';

        $event = new ServiceStatusChanged($healthCheck, $oldStatus, $newStatus);

        // Mock save to throw exception
        $healthCheck->shouldReceive('save')
            ->andThrow(new \Exception('Test exception'));

        // Handle event
        $this->listener->handle($event);

        // No assertions needed as we're verifying the exception was handled
    }
} 