<?php

namespace Tests\Simulations\HealthMonitoring\Unit\Listeners;

use Tests\TestCase;
use App\Events\HealthCheckCompleted;
use App\Listeners\ProcessHealthCheckResults;
use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Services\MetricService;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ProcessHealthCheckResultsTest extends TestCase
{
    use RefreshDatabase;

    protected $metricService;
    protected $alertService;
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricService = Mockery::mock(MetricService::class);
        $this->alertService = Mockery::mock(AlertService::class);
        $this->listener = new ProcessHealthCheckResults($this->metricService, $this->alertService);
    }

    public function test_handles_healthy_service()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service'
        ]);

        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $healthCheck->id,
            'status' => 'healthy'
        ]);

        $event = new HealthCheckCompleted($healthCheck, $result);

        // Mock service expectations
        $this->metricService
            ->shouldReceive('collectMetrics')
            ->with('test_service')
            ->once()
            ->andReturn(['cpu' => 45.5, 'memory' => 60.2]);

        $this->alertService
            ->shouldReceive('processMetrics')
            ->with('test_service', ['cpu' => 45.5, 'memory' => 60.2])
            ->once()
            ->andReturn([]);

        // Handle event
        $this->listener->handle($event);

        // No assertions needed as we're verifying the mock expectations
    }

    public function test_handles_unhealthy_service()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service'
        ]);

        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $healthCheck->id,
            'status' => 'unhealthy',
            'details' => json_encode(['error' => 'Service unavailable'])
        ]);

        $event = new HealthCheckCompleted($healthCheck, $result);

        // Mock service expectations
        $this->metricService
            ->shouldNotReceive('collectMetrics');

        $this->alertService
            ->shouldNotReceive('processMetrics');

        // Handle event
        $this->listener->handle($event);

        // No assertions needed as we're verifying the mock expectations
    }

    public function test_handles_exception()
    {
        // Create test data
        $healthCheck = HealthCheck::factory()->create([
            'name' => 'test_service'
        ]);

        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $healthCheck->id,
            'status' => 'healthy'
        ]);

        $event = new HealthCheckCompleted($healthCheck, $result);

        // Mock service to throw exception
        $this->metricService
            ->shouldReceive('collectMetrics')
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