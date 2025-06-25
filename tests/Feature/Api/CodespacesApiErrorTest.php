<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CodespacesApiErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('codespaces.enabled', true);
    }

    /** @test */
    public function it_handles_health_check_timeout()
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andThrow(new \RuntimeException('Health check timed out'));
        });

        $response = $this->getJson('/api/codespaces/health');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Health check failed',
                'message' => 'Health check timed out'
            ]);
    }

    /** @test */
    public function it_handles_test_execution_timeout()
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK'],
                    'redis' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('startTest')
                ->once()
                ->andThrow(new \RuntimeException('Test execution timed out'));
        });

        $response = $this->postJson('/api/codespaces/tests', [
            'suite' => 'unit',
            'filter' => 'testMethod'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Test execution failed',
                'message' => 'Test execution timed out'
            ]);
    }

    /** @test */
    public function it_handles_invalid_test_suite()
    {
        $response = $this->postJson('/api/codespaces/tests', [
            'suite' => 'invalid_suite'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid test suite',
                'message' => 'The specified test suite does not exist'
            ]);
    }

    /** @test */
    public function it_handles_invalid_test_filter()
    {
        $response = $this->postJson('/api/codespaces/tests', [
            'filter' => 'invalid::filter'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid test filter',
                'message' => 'The specified test filter is invalid'
            ]);
    }

    /** @test */
    public function it_handles_report_generation_failure()
    {
        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('generateReport')
                ->once()
                ->andThrow(new \RuntimeException('Failed to generate report'));
        });

        $response = $this->postJson('/api/codespaces/reports/generate', [
            'passed' => 10,
            'failed' => 2,
            'skipped' => 1,
            'duration' => 5.5,
            'memory' => '128MB',
            'timestamp' => now()->toIso8601String(),
            'suite' => 'unit',
            'details' => []
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Report generation failed',
                'message' => 'Failed to generate report'
            ]);
    }

    /** @test */
    public function it_handles_report_save_failure()
    {
        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('saveReport')
                ->once()
                ->andThrow(new \RuntimeException('Failed to save report'));
        });

        $response = $this->postJson('/api/codespaces/reports/save', [
            'passed' => 10,
            'failed' => 2,
            'skipped' => 1,
            'duration' => 5.5,
            'memory' => '128MB',
            'timestamp' => now()->toIso8601String(),
            'suite' => 'unit',
            'details' => []
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Report save failed',
                'message' => 'Failed to save report'
            ]);
    }

    /** @test */
    public function it_handles_concurrent_test_execution()
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->times(2)
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK'],
                    'redis' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('startTest')
                ->once()
                ->andThrow(new \RuntimeException('A test is already running'));
        });

        // First request
        $response1 = $this->postJson('/api/codespaces/tests', [
            'suite' => 'unit'
        ]);

        // Second request while first is still running
        $response2 = $this->postJson('/api/codespaces/tests', [
            'suite' => 'feature'
        ]);

        $response2->assertStatus(409)
            ->assertJson([
                'error' => 'Test execution conflict',
                'message' => 'A test is already running'
            ]);
    }

    /** @test */
    public function it_handles_memory_limit_exceeded()
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK'],
                    'redis' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('startTest')
                ->once()
                ->andThrow(new \RuntimeException('Allowed memory size of 134217728 bytes exhausted'));
        });

        $response = $this->postJson('/api/codespaces/tests', [
            'suite' => 'unit'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Memory limit exceeded',
                'message' => 'Test execution exceeded memory limit'
            ]);
    }

    /** @test */
    public function it_handles_database_connection_failure()
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => false, 'message' => 'Connection refused'],
                    'cache' => ['healthy' => true, 'message' => 'OK'],
                    'redis' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $response = $this->getJson('/api/codespaces/health');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Service health check failed',
                'unhealthy_services' => [
                    'database' => ['healthy' => false, 'message' => 'Connection refused']
                ]
            ]);
    }
} 