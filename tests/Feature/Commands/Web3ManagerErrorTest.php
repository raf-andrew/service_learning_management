<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\Web3\Web3Service;
use Mockery;

class Web3ManagerErrorTest extends TestCase
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

    public function test_it_handles_exception_during_operation()
    {
        $this->web3Service
            ->shouldReceive('connectToAllNetworks')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $this->artisan('web3:manage', ['action' => 'connect'])
            ->expectsOutput('Connecting to Web3 network...')
            ->expectsOutput('Error: Connection failed')
            ->assertExitCode(1);
    }

    public function test_it_accepts_all_valid_actions()
    {
        $validActions = ['status', 'connect', 'disconnect', 'verify'];
        foreach ($validActions as $action) {
            Mockery::close();
            $this->web3Service = Mockery::mock(Web3Service::class);
            $this->app->instance(Web3Service::class, $this->web3Service);
            if ($action === 'status') {
                $this->web3Service->shouldReceive('getStatus')->andReturn([]);
            } elseif ($action === 'connect') {
                $this->web3Service->shouldReceive('connectToAllNetworks')->andReturn(true);
            } elseif ($action === 'disconnect') {
                $this->web3Service->shouldReceive('disconnectFromAllNetworks');
            } elseif ($action === 'verify') {
                $this->web3Service->shouldReceive('verifyAddress')->with('0x123...')->andReturn([]);
            }
            if ($action === 'verify') {
                $this->artisan('web3:manage', [
                    'action' => $action,
                    '--address' => '0x123...'
                ])->assertExitCode(0);
            } else {
                $this->artisan('web3:manage', ['action' => $action])
                    ->assertExitCode(0);
            }
        }
    }

    public function test_it_rejects_invalid_action()
    {
        $this->artisan('web3:manage', ['action' => 'invalid'])
            ->expectsOutput('Invalid action: invalid')
            ->assertExitCode(1);
    }
} 