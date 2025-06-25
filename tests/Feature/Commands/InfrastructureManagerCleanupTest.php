<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\CodespaceInfrastructureManager;
use App\Services\DockerManager;
use App\Services\VolumeManager;
use Mockery;

class InfrastructureManagerCleanupTest extends TestCase
{
    protected $infrastructureManager;
    protected $dockerManager;
    protected $volumeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->infrastructureManager = Mockery::mock(CodespaceInfrastructureManager::class);
        $this->dockerManager = Mockery::mock(DockerManager::class);
        $this->volumeManager = Mockery::mock(VolumeManager::class);
        $this->app->instance(CodespaceInfrastructureManager::class, $this->infrastructureManager);
        $this->app->instance(DockerManager::class, $this->dockerManager);
        $this->app->instance(VolumeManager::class, $this->volumeManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
} 