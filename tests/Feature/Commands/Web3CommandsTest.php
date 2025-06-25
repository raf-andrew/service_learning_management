<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class Web3CommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $web3Dir = base_path('.web3');
        if (!File::exists($web3Dir)) {
            File::makeDirectory($web3Dir, 0755, true);
        }
        
        $contractsDir = $web3Dir . '/contracts';
        if (!File::exists($contractsDir)) {
            File::makeDirectory($contractsDir, 0755, true);
        }
        
        $artifactsDir = $web3Dir . '/artifacts';
        if (!File::exists($artifactsDir)) {
            File::makeDirectory($artifactsDir, 0755, true);
        }
    }

    public function test_web3_contracts_compile_command()
    {
        $this->artisan('web3:contracts', ['action' => 'compile'])
            ->expectsOutput('Compiling smart contracts...')
            ->assertExitCode(0);
    }

    public function test_web3_contracts_clean_command()
    {
        $this->artisan('web3:contracts', ['action' => 'clean'])
            ->expectsOutput('Cleaning contract artifacts...')
            ->expectsOutput('Artifacts cleaned successfully!')
            ->assertExitCode(0);
    }

    public function test_web3_contracts_verify_command_without_deployment_info()
    {
        $this->artisan('web3:contracts', [
            'action' => 'verify',
            '--network' => 'hardhat'
        ])
            ->expectsOutput('Verifying contracts on hardhat network...')
            ->assertExitCode(1);
    }

    public function test_web3_contracts_invalid_action()
    {
        $this->artisan('web3:contracts', ['action' => 'invalid'])
            ->expectsOutput('Unknown action: invalid')
            ->assertExitCode(1);
    }

    public function test_web3_contracts_command_with_contract_option()
    {
        $this->artisan('web3:contracts', [
            'action' => 'compile',
            '--contract' => 'TestContract'
        ])
            ->expectsOutput('Compiling smart contracts...')
            ->assertExitCode(0);
    }

    public function test_web3_contracts_command_with_network_option()
    {
        $this->artisan('web3:contracts', [
            'action' => 'verify',
            '--network' => 'testnet'
        ])
            ->expectsOutput('Verifying contracts on testnet network...')
            ->assertExitCode(1);
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $web3Dir = base_path('.web3');
        if (File::exists($web3Dir)) {
            File::deleteDirectory($web3Dir);
        }
        
        parent::tearDown();
    }
} 