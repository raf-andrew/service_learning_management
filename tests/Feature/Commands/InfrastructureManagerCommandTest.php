<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use App\Services\CodespaceInfrastructureManager;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class InfrastructureManagerCommandTest extends TestCase
{
    protected $infrastructureManager;
    protected $dockerManager;
    protected $networkManager;
    protected $volumeManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock CodespacesHealthService to always return healthy
        $this->mock(\App\Services\CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')->andReturn([
                'database' => ['healthy' => true, 'message' => 'OK'],
                'cache' => ['healthy' => true, 'message' => 'OK'],
            ]);
        });
        
        $this->infrastructureManager = Mockery::mock(CodespaceInfrastructureManager::class);
        $this->dockerManager = Mockery::mock(DockerManager::class);
        $this->networkManager = Mockery::mock(NetworkManager::class);
        $this->volumeManager = Mockery::mock(VolumeManager::class);
        
        $this->app->instance(CodespaceInfrastructureManager::class, $this->infrastructureManager);
        $this->app->instance(DockerManager::class, $this->dockerManager);
        $this->app->instance(NetworkManager::class, $this->networkManager);
        $this->app->instance(VolumeManager::class, $this->volumeManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_error_for_invalid_action()
    {
        $this->artisan('infrastructure:manage', ['action' => 'invalid'])
            ->expectsOutput('Invalid action: invalid')
            ->assertExitCode(1);
    }

    public function test_it_handles_status_action()
    {
        $this->dockerManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn([
                'component' => 'Docker',
                'status' => 'Running',
                'details' => 'All containers healthy'
            ]);

        $this->networkManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn([
                'component' => 'Network',
                'status' => 'Active',
                'details' => 'Network bridge configured'
            ]);

        $this->volumeManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn([
                'component' => 'Volumes',
                'status' => 'Available',
                'details' => 'All volumes mounted'
            ]);

        $this->infrastructureManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn([
                'component' => 'Infrastructure',
                'status' => 'Healthy',
                'details' => 'All systems operational'
            ]);

        $this->artisan('infrastructure:manage', ['action' => 'status', '--force' => true])
            ->expectsOutput('Checking infrastructure status...')
            ->assertExitCode(0);
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

    public function test_it_handles_cleanup_action()
    {
        $this->infrastructureManager
            ->shouldReceive('stopAll')
            ->once();

        $this->dockerManager
            ->shouldReceive('cleanup')
            ->once();

        $this->volumeManager
            ->shouldReceive('cleanup')
            ->once();

        $this->artisan('infrastructure:manage', ['action' => 'cleanup', '--force' => true])
            ->expectsOutput('Cleaning up infrastructure...')
            ->expectsOutput('Infrastructure cleanup completed successfully.')
            ->assertExitCode(0);
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