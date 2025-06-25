<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\CodespaceInfrastructureManager;
use Mockery;

class InfrastructureManagerErrorTest extends TestCase
{
    protected $infrastructureManager;
    protected $dockerManager;
    protected $networkManager;
    protected $volumeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->infrastructureManager = Mockery::mock(CodespaceInfrastructureManager::class);
        $this->dockerManager = Mockery::mock(\App\Services\DockerManager::class);
        $this->networkManager = Mockery::mock(\App\Services\NetworkManager::class);
        $this->volumeManager = Mockery::mock(\App\Services\VolumeManager::class);
        $this->app->instance(CodespaceInfrastructureManager::class, $this->infrastructureManager);
        $this->app->instance(\App\Services\DockerManager::class, $this->dockerManager);
        $this->app->instance(\App\Services\NetworkManager::class, $this->networkManager);
        $this->app->instance(\App\Services\VolumeManager::class, $this->volumeManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_handles_exception_during_operation()
    {
        $this->infrastructureManager
            ->shouldReceive('startAll')
            ->once()
            ->andThrow(new \Exception('Service unavailable'));
        $this->artisan('infrastructure:manage', ['action' => 'start', '--force' => true])
            ->expectsOutput('Starting infrastructure...')
            ->expectsOutput('Error: Service unavailable')
            ->assertExitCode(1);
    }

    public function test_it_cancels_operation_when_confirmation_denied()
    {
        $this->artisan('infrastructure:manage', ['action' => 'start'])
            ->expectsConfirmation('Are you sure you want to start the infrastructure?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);
    }

    public function test_it_proceeds_when_confirmation_accepted()
    {
        $this->infrastructureManager
            ->shouldReceive('startAll')
            ->once();
        $this->artisan('infrastructure:manage', ['action' => 'start'])
            ->expectsConfirmation('Are you sure you want to start the infrastructure?', 'yes')
            ->expectsOutput('Starting infrastructure...')
            ->expectsOutput('All infrastructure services started successfully.')
            ->assertExitCode(0);
    }

    public function test_it_validates_action_parameter()
    {
        $this->artisan('infrastructure:manage', ['action' => 'invalid'])
            ->expectsOutput('Invalid action: invalid')
            ->assertExitCode(1);
    }

    public function test_it_accepts_all_valid_actions()
    {
        $validActions = ['status', 'start', 'stop', 'restart', 'cleanup'];
        foreach ($validActions as $action) {
            if ($action === 'status') {
                $this->dockerManager->shouldReceive('getStatus')->once()->andReturn(['component' => 'Docker', 'status' => 'Running']);
                $this->networkManager->shouldReceive('getStatus')->once()->andReturn(['component' => 'Network', 'status' => 'Active']);
                $this->volumeManager->shouldReceive('getStatus')->once()->andReturn(['component' => 'Volumes', 'status' => 'Available']);
                $this->infrastructureManager->shouldReceive('getStatus')->once()->andReturn(['component' => 'Infrastructure', 'status' => 'Healthy']);
            } elseif ($action === 'cleanup') {
                $this->infrastructureManager->shouldReceive('stopAll')->once();
                $this->dockerManager->shouldReceive('cleanup')->once();
                $this->volumeManager->shouldReceive('cleanup')->once();
            } else {
                $this->infrastructureManager->shouldReceive($action . 'All')->once();
            }
            $this->artisan('infrastructure:manage', ['action' => $action, '--force' => true])
                ->assertExitCode(0);
        }
    }
} 