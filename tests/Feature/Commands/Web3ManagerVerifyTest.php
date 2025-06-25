<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\Web3\Web3Service;
use Mockery;

class Web3ManagerVerifyTest extends TestCase
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

    public function test_it_handles_verify_action_with_address_and_network()
    {
        $verificationData = [
            'valid' => true,
            'balance' => '1.5 ETH',
            'transactions' => 10,
            'contract_interactions' => 5
        ];

        $this->web3Service
            ->shouldReceive('verifyAddressOnNetwork')
            ->with('0x123...', 'ethereum')
            ->once()
            ->andReturn($verificationData);

        $this->artisan('web3:manage', [
            'action' => 'verify',
            '--address' => '0x123...',
            '--network' => 'ethereum'
        ])
            ->expectsOutput('Verifying Web3 address...')
            ->assertExitCode(0);
    }

    public function test_it_handles_verify_action_with_address_only()
    {
        $verificationData = [
            'valid' => true,
            'balance' => '1.5 ETH',
            'transactions' => 10,
            'contract_interactions' => 5
        ];

        $this->web3Service
            ->shouldReceive('verifyAddress')
            ->with('0x123...')
            ->once()
            ->andReturn($verificationData);

        $this->artisan('web3:manage', [
            'action' => 'verify',
            '--address' => '0x123...'
        ])
            ->expectsOutput('Verifying Web3 address...')
            ->assertExitCode(0);
    }

    public function test_it_handles_boolean_values_in_verification_table()
    {
        $verificationData = [
            'valid' => true,
            'has_balance' => false,
            'is_contract' => true,
            'is_verified' => false
        ];

        $this->web3Service
            ->shouldReceive('verifyAddress')
            ->with('0x123...')
            ->once()
            ->andReturn($verificationData);

        $this->artisan('web3:manage', [
            'action' => 'verify',
            '--address' => '0x123...'
        ])
            ->expectsOutput('Verifying Web3 address...')
            ->assertExitCode(0);
    }

    public function test_it_requires_address_for_verify_action()
    {
        $this->artisan('web3:manage', ['action' => 'verify'])
            ->expectsOutput('Address is required for verification')
            ->assertExitCode(1);
    }
} 