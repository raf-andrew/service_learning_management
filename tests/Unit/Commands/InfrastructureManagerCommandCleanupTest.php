<?php

namespace Tests\Unit\Commands;

use Tests\Unit\UnitTestCase;
use App\Console\Commands\InfrastructureManagerCommand;
use App\Services\CodespaceInfrastructureManager;
use App\Services\DockerManager;
use App\Services\VolumeManager;
use Mockery;

class InfrastructureManagerCommandCleanupTest extends UnitTestCase
{
    protected $command;
    protected $infrastructureManager;
    protected $dockerManager;
    protected $volumeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->infrastructureManager = Mockery::mock(CodespaceInfrastructureManager::class);
        $this->dockerManager = Mockery::mock(DockerManager::class);
        $this->volumeManager = Mockery::mock(VolumeManager::class);
        $this->command = new InfrastructureManagerCommand();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_cleanup_calls_all_cleanup_methods()
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
        $method = new \ReflectionMethod($this->command, 'executeCleanup');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, $this->dockerManager, $this->volumeManager);

        // Add assertions to verify the methods were called
        $this->infrastructureManager->shouldHaveReceived('stopAll')->once();
        $this->dockerManager->shouldHaveReceived('cleanup')->once();
        $this->volumeManager->shouldHaveReceived('cleanup')->once();
        $this->assertTrue(true); // Ensure at least one assertion is counted
    }
} 