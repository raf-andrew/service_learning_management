<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\Web3\Web3Service;
use Mockery;

class Web3ManagerStatusTest extends TestCase
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

    public function test_it_handles_status_action()
    {
        $statusData = [
            [
                'network' => 'ethereum',
                'connected' => true,
                'address' => '0x123...',
                'last_check' => '2023-01-01 12:00:00'
            ],
            [
                'network' => 'polygon',
                'connected' => false,
                'address' => null,
                'last_check' => '2023-01-01 12:00:00'
            ]
        ];

        $this->web3Service
            ->shouldReceive('getStatus')
            ->once()
            ->andReturn($statusData);

        $this->artisan('web3:manage', ['action' => 'status'])
            ->expectsOutput('Checking Web3 service status...')
            ->assertExitCode(0);
    }
} 