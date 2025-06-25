<?php

namespace Tests\Feature\Console\Commands;

use Tests\Feature\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;

class CodespacesTestCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('codespaces.enabled', true);
    }

    public function test_command_requires_codespaces_enabled(): void
    {
        Config::set('codespaces.enabled', false);
        
        $this->artisan('codespaces:test')
            ->expectsOutput('Codespaces is not enabled')
            ->assertExitCode(1);
    }

    public function test_command_checks_health_before_running_tests(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => false, 'message' => 'Connection failed'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });
        
        $this->artisan('codespaces:test')
            ->expectsOutput('Some services are unhealthy. Please fix them before running tests:')
            ->expectsOutput('❌ database: Connection failed')
            ->assertExitCode(1);
    }

    public function test_command_runs_tests_when_healthy(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });
        
        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('generateReport')
                ->once()
                ->andReturn('Test Report');
        });
        
        Artisan::shouldReceive('call')
            ->once()
            ->with('test')
            ->andReturn(0);
        
        $this->artisan('codespaces:test')
            ->expectsOutput('All services are healthy. Proceeding with tests...')
            ->expectsOutput('Running tests with command: test')
            ->expectsOutput('✅ All tests passed')
            ->assertExitCode(0);
    }

    public function test_command_runs_specific_test_suite(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });
        
        Artisan::shouldReceive('call')
            ->once()
            ->with('test --testsuite=unit')
            ->andReturn(0);
        
        $this->artisan('codespaces:test --suite=unit')
            ->expectsOutput('All services are healthy. Proceeding with tests...')
            ->expectsOutput('Running tests with command: test --testsuite=unit')
            ->expectsOutput('✅ All tests passed')
            ->assertExitCode(0);
    }

    public function test_command_runs_filtered_tests(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });
        
        Artisan::shouldReceive('call')
            ->once()
            ->with('test --filter=testMethod')
            ->andReturn(0);
        
        $this->artisan('codespaces:test --filter=testMethod')
            ->expectsOutput('All services are healthy. Proceeding with tests...')
            ->expectsOutput('Running tests with command: test --filter=testMethod')
            ->expectsOutput('✅ All tests passed')
            ->assertExitCode(0);
    }

    public function test_command_handles_test_failures(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });
        
        Artisan::shouldReceive('call')
            ->once()
            ->with('test')
            ->andReturn(1);
        
        $this->artisan('codespaces:test')
            ->expectsOutput('All services are healthy. Proceeding with tests...')
            ->expectsOutput('Running tests with command: test')
            ->expectsOutput('❌ Some tests failed')
            ->assertExitCode(1);
    }
} 