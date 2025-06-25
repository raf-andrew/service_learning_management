<?php

namespace Tests\Unit\Commands;

use Tests\Unit\UnitTestCase;
use App\Console\Commands\InfrastructureManagerCommand;
use App\Services\CodespaceInfrastructureManager;
use Mockery;

class InfrastructureManagerCommandStartStopTest extends UnitTestCase
{
    protected $command;
    protected $infrastructureManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->infrastructureManager = Mockery::mock(CodespaceInfrastructureManager::class);
        $this->command = new InfrastructureManagerCommand();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_start_calls_start_all_when_no_service_specified()
    {
        $this->infrastructureManager
            ->shouldReceive('startAll')
            ->once();
        $method = new \ReflectionMethod($this->command, 'executeStart');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, null);
        $this->assertTrue(true);
    }

    public function test_handle_start_calls_start_service_when_service_specified()
    {
        $service = 'docker';
        $this->infrastructureManager
            ->shouldReceive('startService')
            ->with($service)
            ->once();
        $method = new \ReflectionMethod($this->command, 'executeStart');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, $service);
        $this->assertTrue(true);
    }

    public function test_handle_stop_calls_stop_all_when_no_service_specified()
    {
        $this->infrastructureManager
            ->shouldReceive('stopAll')
            ->once();
        $method = new \ReflectionMethod($this->command, 'executeStop');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, null);
        $this->assertTrue(true);
    }

    public function test_handle_stop_calls_stop_service_when_service_specified()
    {
        $service = 'network';
        $this->infrastructureManager
            ->shouldReceive('stopService')
            ->with($service)
            ->once();
        $method = new \ReflectionMethod($this->command, 'executeStop');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, $service);
        $this->assertTrue(true);
    }

    public function test_handle_restart_calls_restart_all_when_no_service_specified()
    {
        $this->infrastructureManager
            ->shouldReceive('restartAll')
            ->once();
        $method = new \ReflectionMethod($this->command, 'executeRestart');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, null);
        $this->assertTrue(true);
    }

    public function test_handle_restart_calls_restart_service_when_service_specified()
    {
        $service = 'volume';
        $this->infrastructureManager
            ->shouldReceive('restartService')
            ->with($service)
            ->once();
        $method = new \ReflectionMethod($this->command, 'executeRestart');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->infrastructureManager, $service);
        $this->assertTrue(true);
    }
} 