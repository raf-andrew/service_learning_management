<?php

namespace Tests\Unit\Commands;

use Tests\Unit\UnitTestCase;
use App\Console\Commands\InfrastructureManagerCommand;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use App\Services\CodespaceInfrastructureManager;
use Mockery;

class InfrastructureManagerCommandStatusTest extends UnitTestCase
{
    protected $command;
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
        $this->command = new InfrastructureManagerCommand();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_status_calls_all_manager_status_methods()
    {
        $this->dockerManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn(['component' => 'Docker', 'status' => 'Running']);
        $this->networkManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn(['component' => 'Network', 'status' => 'Active']);
        $this->volumeManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn(['component' => 'Volumes', 'status' => 'Available']);
        $this->infrastructureManager
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn(['component' => 'Infrastructure', 'status' => 'Healthy']);
        $method = new \ReflectionMethod($this->command, 'getStatusData');
        $method->setAccessible(true);
        $status = $method->invoke($this->command, $this->infrastructureManager, $this->dockerManager, $this->networkManager, $this->volumeManager);
        $this->assertArrayHasKey('Docker', $status);
        $this->assertArrayHasKey('Network', $status);
        $this->assertArrayHasKey('Volumes', $status);
        $this->assertArrayHasKey('Infrastructure', $status);
    }
} 