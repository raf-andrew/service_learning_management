<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\CodespacesTestCommand;
use App\Services\CodespacesTestReporter;
use App\Services\CodespacesHealthService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class CodespacesTestCommandTest extends TestCase
{
    protected $command;
    protected $healthService;
    protected $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->healthService = Mockery::mock(CodespacesHealthService::class);
        $this->reporter = Mockery::mock(CodespacesTestReporter::class);
        
        $this->command = new CodespacesTestCommand(
            $this->healthService,
            $this->reporter
        );
    }

    public function test_command_fails_when_codespaces_not_enabled(): void
    {
        Config::set('codespaces.enabled', false);
        $result = $this->artisan('codespaces:test');
        $result->assertExitCode(1);
    }

    public function test_command_fails_when_services_unhealthy(): void
    {
        Config::set('codespaces.enabled', true);
        $mock = Mockery::mock(\App\Services\CodespacesHealthService::class);
        $mock->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => false, 'message' => 'Connection failed'],
                'cache' => ['healthy' => true, 'message' => 'OK']
            ]);
        $this->app->instance(\App\Services\CodespacesHealthService::class, $mock);
        $result = $this->artisan('codespaces:test');
        $result->assertExitCode(1);
    }

    public function test_command_runs_tests_when_services_healthy(): void
    {
        Config::set('codespaces.enabled', true);
        $mock = Mockery::mock(\App\Services\CodespacesHealthService::class);
        $mock->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'OK'],
                'cache' => ['healthy' => true, 'message' => 'OK']
            ]);
        $this->app->instance(\App\Services\CodespacesHealthService::class, $mock);
        $result = $this->artisan('codespaces:test');
        $result->assertExitCode(0);
    }

    public function test_command_runs_specific_test_suite(): void
    {
        Config::set('codespaces.enabled', true);
        $mock = Mockery::mock(\App\Services\CodespacesHealthService::class);
        $mock->shouldReceive('checkAllServices')
            ->once()
            ->andReturn([
                'database' => ['healthy' => true, 'message' => 'OK'],
                'cache' => ['healthy' => true, 'message' => 'OK']
            ]);
        $this->app->instance(\App\Services\CodespacesHealthService::class, $mock);
        $result = $this->artisan('codespaces:test --suite=unit');
        $result->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 