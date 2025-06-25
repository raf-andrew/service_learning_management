<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\Web3\Web3Service;
use Mockery;

class Web3ManagerConnectTest extends TestCase
{
    protected $web3Service;

    protected function setUp(): void
    {
        parent::setUp();
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(true);
        $this->web3Service = Mockery::mock(Web3Service::class);
        $this->app->instance(Web3Service::class, $this->web3Service);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_handles_connect_action_for_specific_network()
    {
        $this->web3Service
            ->shouldReceive('connectToNetwork')
            ->with('ethereum')
            ->once()
            ->andReturn(true);

        $this->artisan('web3:manage', [
            'action' => 'connect',
            '--network' => 'ethereum'
        ])
            ->expectsOutput('Connecting to Web3 network...')
            ->expectsOutput('Connected to ethereum successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_connect_action_for_all_networks()
    {
        $this->web3Service
            ->shouldReceive('connectToAllNetworks')
            ->once()
            ->andReturn(true);

        $this->artisan('web3:manage', ['action' => 'connect'])
            ->expectsOutput('Connecting to Web3 network...')
            ->expectsOutput('Connected to all available networks successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_disconnect_action_for_specific_network()
    {
        $this->web3Service
            ->shouldReceive('disconnectFromNetwork')
            ->with('ethereum')
            ->once();

        $this->artisan('web3:manage', [
            'action' => 'disconnect',
            '--network' => 'ethereum'
        ])
            ->expectsOutput('Disconnecting from Web3 network...')
            ->expectsOutput('Disconnected from ethereum successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_disconnect_action_for_all_networks()
    {
        $this->web3Service
            ->shouldReceive('disconnectFromAllNetworks')
            ->once();

        $this->artisan('web3:manage', ['action' => 'disconnect'])
            ->expectsOutput('Disconnecting from Web3 network...')
            ->expectsOutput('Disconnected from all networks successfully.')
            ->assertExitCode(0);
    }
} 