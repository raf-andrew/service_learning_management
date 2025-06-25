<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use App\Services\CodespaceInfrastructureManager;
use Mockery;

class InfrastructureManagerStatusTest extends TestCase
{
    protected $infrastructureManager;
    protected $dockerManager;
    protected $networkManager;
    protected $volumeManager;

    protected function setUp(): void
    {
        parent::setUp();
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
} 