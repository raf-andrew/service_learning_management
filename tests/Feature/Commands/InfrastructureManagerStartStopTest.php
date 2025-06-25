<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\CodespaceInfrastructureManager;
use Mockery;

class InfrastructureManagerStartStopTest extends TestCase
{
    protected $infrastructureManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->infrastructureManager = Mockery::mock(CodespaceInfrastructureManager::class);
        $this->app->instance(CodespaceInfrastructureManager::class, $this->infrastructureManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_handles_start_action_for_all_services()
    {
        $this->infrastructureManager
            ->shouldReceive('startAll')
            ->once();

        $this->artisan('infrastructure:manage', ['action' => 'start', '--force' => true])
            ->expectsOutput('Starting infrastructure...')
            ->expectsOutput('All infrastructure services started successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_start_action_for_specific_service()
    {
        $this->infrastructureManager
            ->shouldReceive('startService')
            ->with('docker')
            ->once();

        $this->artisan('infrastructure:manage', [
                'action' => 'start',
                '--service' => 'docker',
                '--force' => true
            ])
            ->expectsOutput('Starting infrastructure...')
            ->expectsOutput('Service docker started successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_stop_action_for_all_services()
    {
        $this->infrastructureManager
            ->shouldReceive('stopAll')
            ->once();

        $this->artisan('infrastructure:manage', ['action' => 'stop', '--force' => true])
            ->expectsOutput('Stopping infrastructure...')
            ->expectsOutput('All infrastructure services stopped successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_stop_action_for_specific_service()
    {
        $this->infrastructureManager
            ->shouldReceive('stopService')
            ->with('network')
            ->once();

        $this->artisan('infrastructure:manage', [
                'action' => 'stop',
                '--service' => 'network',
                '--force' => true
            ])
            ->expectsOutput('Stopping infrastructure...')
            ->expectsOutput('Service network stopped successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_restart_action_for_all_services()
    {
        $this->infrastructureManager
            ->shouldReceive('restartAll')
            ->once();

        $this->artisan('infrastructure:manage', ['action' => 'restart', '--force' => true])
            ->expectsOutput('Restarting infrastructure...')
            ->expectsOutput('All infrastructure services restarted successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_restart_action_for_specific_service()
    {
        $this->infrastructureManager
            ->shouldReceive('restartService')
            ->with('volume')
            ->once();

        $this->artisan('infrastructure:manage', [
                'action' => 'restart',
                '--service' => 'volume',
                '--force' => true
            ])
            ->expectsOutput('Restarting infrastructure...')
            ->expectsOutput('Service volume restarted successfully.')
            ->assertExitCode(0);
    }
} 