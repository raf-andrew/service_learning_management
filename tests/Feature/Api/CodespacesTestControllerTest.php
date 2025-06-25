<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CodespacesTestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $healthService;
    protected $testReporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthService = Mockery::mock(CodespacesHealthService::class);
        $this->testReporter = Mockery::mock(CodespacesTestReporter::class);

        $this->app->instance(CodespacesHealthService::class, $this->healthService);
        $this->app->instance(CodespacesTestReporter::class, $this->testReporter);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_health_status()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => true, 'message' => 'Cache is healthy'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $response = $this->getJson('/api/codespaces/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'services' => [
                    'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                    'cache' => ['healthy' => true, 'message' => 'Cache is healthy'],
                    'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
                ]
            ]);
    }

    public function test_it_returns_error_when_services_are_unhealthy()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => false, 'message' => 'Cache connection failed'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $response = $this->getJson('/api/codespaces/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'message' => 'One or more services are unhealthy',
                'services' => [
                    'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                    'cache' => ['healthy' => false, 'message' => 'Cache connection failed'],
                    'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
                ]
            ]);
    }

    public function test_it_runs_tests_with_default_options()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => true, 'message' => 'Cache is healthy'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $this->testReporter->shouldReceive('startTestRun')
            ->once()
            ->andReturn('test-run-123');

        $this->testReporter->shouldReceive('recordTestResult')
            ->times(3)
            ->andReturn(true);

        $this->testReporter->shouldReceive('generateSummary')
            ->once()
            ->andReturn([
                'passed' => 2,
                'failed' => 0,
                'skipped' => 1,
                'duration' => 5.5,
                'memory' => '128MB',
                'timestamp' => '2024-01-01T00:00:00Z',
                'suite' => 'all',
                'details' => []
            ]);

        $response = $this->postJson('/api/codespaces/tests/run');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Tests completed successfully',
                'summary' => [
                    'passed' => 2,
                    'failed' => 0,
                    'skipped' => 1,
                    'duration' => 5.5,
                    'memory' => '128MB',
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'suite' => 'all',
                    'details' => []
                ]
            ]);
    }

    public function test_it_returns_error_when_tests_fail()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => true, 'message' => 'Cache is healthy'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $this->testReporter->shouldReceive('startTestRun')
            ->once()
            ->andReturn('test-run-123');

        $this->testReporter->shouldReceive('recordTestResult')
            ->times(3)
            ->andReturn(true);

        $this->testReporter->shouldReceive('generateSummary')
            ->once()
            ->andReturn([
                'passed' => 1,
                'failed' => 1,
                'skipped' => 1,
                'duration' => 5.5,
                'memory' => '128MB',
                'timestamp' => '2024-01-01T00:00:00Z',
                'suite' => 'all',
                'details' => [
                    [
                        'name' => 'Test Case 1',
                        'status' => 'failed',
                        'duration' => 0.5,
                        'memory' => '32MB',
                        'error' => 'Test failed: expected true but got false'
                    ]
                ]
            ]);

        $response = $this->postJson('/api/codespaces/tests/run');

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Tests completed with failures',
                'summary' => [
                    'passed' => 1,
                    'failed' => 1,
                    'skipped' => 1,
                    'duration' => 5.5,
                    'memory' => '128MB',
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'suite' => 'all',
                    'details' => [
                        [
                            'name' => 'Test Case 1',
                            'status' => 'failed',
                            'duration' => 0.5,
                            'memory' => '32MB',
                            'error' => 'Test failed: expected true but got false'
                        ]
                    ]
                ]
            ]);
    }

    public function test_it_runs_tests_with_specific_suite()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => true, 'message' => 'Cache is healthy'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $this->testReporter->shouldReceive('startTestRun')
            ->once()
            ->andReturn('test-run-123');

        $this->testReporter->shouldReceive('recordTestResult')
            ->times(2)
            ->andReturn(true);

        $this->testReporter->shouldReceive('generateSummary')
            ->once()
            ->andReturn([
                'passed' => 2,
                'failed' => 0,
                'skipped' => 0,
                'duration' => 3.5,
                'memory' => '96MB',
                'timestamp' => '2024-01-01T00:00:00Z',
                'suite' => 'unit',
                'details' => []
            ]);

        $response = $this->postJson('/api/codespaces/tests/run', [
            'suite' => 'unit'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Tests completed successfully',
                'summary' => [
                    'passed' => 2,
                    'failed' => 0,
                    'skipped' => 0,
                    'duration' => 3.5,
                    'memory' => '96MB',
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'suite' => 'unit',
                    'details' => []
                ]
            ]);
    }

    public function test_it_runs_tests_with_filter()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => true, 'message' => 'Cache is healthy'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $this->testReporter->shouldReceive('startTestRun')
            ->once()
            ->andReturn('test-run-123');

        $this->testReporter->shouldReceive('recordTestResult')
            ->times(1)
            ->andReturn(true);

        $this->testReporter->shouldReceive('generateSummary')
            ->once()
            ->andReturn([
                'passed' => 1,
                'failed' => 0,
                'skipped' => 0,
                'duration' => 1.5,
                'memory' => '64MB',
                'timestamp' => '2024-01-01T00:00:00Z',
                'suite' => 'all',
                'details' => []
            ]);

        $response = $this->postJson('/api/codespaces/tests/run', [
            'filter' => 'testMethod'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Tests completed successfully',
                'summary' => [
                    'passed' => 1,
                    'failed' => 0,
                    'skipped' => 0,
                    'duration' => 1.5,
                    'memory' => '64MB',
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'suite' => 'all',
                    'details' => []
                ]
            ]);
    }

    public function test_it_returns_error_when_services_are_unhealthy_before_running_tests()
    {
        $this->healthService->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                'cache' => ['healthy' => false, 'message' => 'Cache connection failed'],
                'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
            ]);

        $response = $this->postJson('/api/codespaces/tests/run');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot run tests: one or more services are unhealthy',
                'services' => [
                    'database' => ['healthy' => true, 'message' => 'Database is healthy'],
                    'cache' => ['healthy' => false, 'message' => 'Cache connection failed'],
                    'redis' => ['healthy' => true, 'message' => 'Redis is healthy']
                ]
            ]);
    }

    public function test_it_returns_test_results()
    {
        $this->testReporter->shouldReceive('getTestResults')
            ->once()
            ->with('test-run-123')
            ->andReturn([
                'passed' => 2,
                'failed' => 0,
                'skipped' => 1,
                'duration' => 5.5,
                'memory' => '128MB',
                'timestamp' => '2024-01-01T00:00:00Z',
                'suite' => 'all',
                'details' => [
                    [
                        'name' => 'Test Case 1',
                        'status' => 'passed',
                        'duration' => 0.5,
                        'memory' => '32MB'
                    ]
                ]
            ]);

        $response = $this->getJson('/api/codespaces/tests/results/test-run-123');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'results' => [
                    'passed' => 2,
                    'failed' => 0,
                    'skipped' => 1,
                    'duration' => 5.5,
                    'memory' => '128MB',
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'suite' => 'all',
                    'details' => [
                        [
                            'name' => 'Test Case 1',
                            'status' => 'passed',
                            'duration' => 0.5,
                            'memory' => '32MB'
                        ]
                    ]
                ]
            ]);
    }

    public function test_it_returns_404_when_no_test_results_exist()
    {
        $this->testReporter->shouldReceive('getTestResults')
            ->once()
            ->with('non-existent-run')
            ->andReturn(null);

        $response = $this->getJson('/api/codespaces/tests/results/non-existent-run');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Test results not found'
            ]);
    }
} 