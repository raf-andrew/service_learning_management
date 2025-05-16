<?php

namespace Tests\Simulations\HealthMonitoring\Unit\Listeners;

use Tests\TestCase;
use App\Events\HealthAlertTriggered;
use App\Listeners\HandleHealthAlerts;
use App\Models\HealthAlert;
use App\Jobs\HealthAlertNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;

class HandleHealthAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new HandleHealthAlerts();
    }

    public function test_dispatches_notification_job()
    {
        Queue::fake();

        // Create test data
        $alert = HealthAlert::factory()->create([
            'service_name' => 'test_service',
            'level' => 'critical',
            'type' => 'cpu_usage',
            'message' => 'CPU usage is above threshold'
        ]);

        $event = new HealthAlertTriggered($alert);

        // Handle event
        $this->listener->handle($event);

        // Assert job was dispatched
        Queue::assertPushed(HealthAlertNotificationJob::class, function ($job) use ($alert) {
            return $job->alert->id === $alert->id;
        });
    }

    public function test_handles_exception()
    {
        Queue::fake();

        // Create test data
        $alert = HealthAlert::factory()->create();

        $event = new HealthAlertTriggered($alert);

        // Mock Queue facade to throw exception
        Queue::shouldReceive('push')
            ->andThrow(new \Exception('Test exception'));

        // Handle event
        $this->listener->handle($event);

        // No assertions needed as we're verifying the exception was handled
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 