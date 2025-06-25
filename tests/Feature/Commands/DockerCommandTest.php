<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use Mockery;

class DockerCommandTest extends TestCase
{
    protected $dockerManager;
    protected $networkManager;
    protected $volumeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dockerManager = Mockery::mock(DockerManager::class);
        $this->networkManager = Mockery::mock(NetworkManager::class);
        $this->volumeManager = Mockery::mock(VolumeManager::class);
        $this->app->instance(DockerManager::class, $this->dockerManager);
        $this->app->instance(NetworkManager::class, $this->networkManager);
        $this->app->instance(VolumeManager::class, $this->volumeManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_handles_start_action()
    {
        $this->dockerManager->shouldReceive('startServices')->once();
        $this->artisan('docker', ['action' => 'start'])->assertExitCode(0);
    }

    public function test_it_handles_stop_action()
    {
        $this->dockerManager->shouldReceive('stopServices')->once();
        $this->artisan('docker', ['action' => 'stop'])->assertExitCode(0);
    }

    public function test_it_handles_restart_action()
    {
        $this->dockerManager->shouldReceive('stopServices')->once();
        $this->dockerManager->shouldReceive('startServices')->once();
        $this->artisan('docker', ['action' => 'restart'])->assertExitCode(0);
    }

    public function test_it_handles_status_action()
    {
        $this->dockerManager->shouldReceive('getServiceStatus')->once()->andReturn(['app' => 'running']);
        $this->artisan('docker', ['action' => 'status'])->assertExitCode(0);
    }

    public function test_it_handles_logs_action()
    {
        $this->dockerManager->shouldReceive('getServiceLogs')->once()->andReturn('log output');
        $this->artisan('docker', ['action' => 'logs', 'service' => 'app'])->assertExitCode(0);
    }

    public function test_it_handles_rebuild_action()
    {
        $this->dockerManager->shouldReceive('rebuildService')->twice();
        $this->artisan('docker', ['action' => 'rebuild'])->assertExitCode(0);
    }

    public function test_it_handles_prune_action()
    {
        $this->networkManager->shouldReceive('pruneNetworks')->once();
        $this->volumeManager->shouldReceive('pruneVolumes')->once();
        $this->artisan('docker', ['action' => 'prune'])->assertExitCode(0);
    }

    public function test_it_handles_unknown_action()
    {
        $this->artisan('docker', ['action' => 'unknown'])
            ->expectsOutput('Unknown action: unknown')
            ->assertExitCode(1);
    }
} 