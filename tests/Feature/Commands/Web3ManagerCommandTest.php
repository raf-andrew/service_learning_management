<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\Web3\Web3Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

/**
 * @group commands
 * @group web3
 * @checklistItem WEB3-001
 */
class Web3ManagerCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Web3Service $web3Service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->web3Service = $this->createMock(Web3Service::class);
        $this->app->instance(Web3Service::class, $this->web3Service);
    }

    /**
     * @test
     * @checklistItem WEB3-001
     * @coverage 100%
     * @description Test Web3 manager command status action
     */
    public function test_web3_manager_status_action()
    {
        $status = [
            [
                'network' => 'ethereum',
                'connected' => true,
                'address' => '0x1234567890abcdef',
                'last_check' => '2024-01-01 12:00:00'
            ],
            [
                'network' => 'polygon',
                'connected' => false,
                'address' => null,
                'last_check' => '2024-01-01 12:00:00'
            ]
        ];

        $this->web3Service
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $exitCode = Artisan::call('web3:manage', ['action' => 'status']);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-002
     * @coverage 100%
     * @description Test Web3 manager command connect action with specific network
     */
    public function test_web3_manager_connect_action_with_network()
    {
        $this->web3Service
            ->expects($this->once())
            ->method('connectToNetwork')
            ->with('ethereum')
            ->willReturn(true);

        $exitCode = Artisan::call('web3:manage', [
            'action' => 'connect',
            '--network' => 'ethereum'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-003
     * @coverage 100%
     * @description Test Web3 manager command connect action without network
     */
    public function test_web3_manager_connect_action_without_network()
    {
        $this->web3Service
            ->expects($this->once())
            ->method('connectToAllNetworks')
            ->willReturn(true);

        $exitCode = Artisan::call('web3:manage', ['action' => 'connect']);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-004
     * @coverage 100%
     * @description Test Web3 manager command disconnect action with specific network
     */
    public function test_web3_manager_disconnect_action_with_network()
    {
        $this->web3Service
            ->expects($this->once())
            ->method('disconnectFromNetwork')
            ->with('ethereum');

        $exitCode = Artisan::call('web3:manage', [
            'action' => 'disconnect',
            '--network' => 'ethereum'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-005
     * @coverage 100%
     * @description Test Web3 manager command disconnect action without network
     */
    public function test_web3_manager_disconnect_action_without_network()
    {
        $this->web3Service
            ->expects($this->once())
            ->method('disconnectFromAllNetworks');

        $exitCode = Artisan::call('web3:manage', ['action' => 'disconnect']);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-006
     * @coverage 100%
     * @description Test Web3 manager command verify action with address and network
     */
    public function test_web3_manager_verify_action_with_address_and_network()
    {
        $verification = [
            'valid' => true,
            'checksum' => true,
            'network' => 'ethereum',
            'balance' => '1.5 ETH'
        ];

        $this->web3Service
            ->expects($this->once())
            ->method('verifyAddressOnNetwork')
            ->with('0x1234567890abcdef', 'ethereum')
            ->willReturn($verification);

        $exitCode = Artisan::call('web3:manage', [
            'action' => 'verify',
            '--address' => '0x1234567890abcdef',
            '--network' => 'ethereum'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-007
     * @coverage 100%
     * @description Test Web3 manager command verify action with address without network
     */
    public function test_web3_manager_verify_action_with_address_without_network()
    {
        $verification = [
            'valid' => true,
            'checksum' => true,
            'network' => 'ethereum',
            'balance' => '1.5 ETH'
        ];

        $this->web3Service
            ->expects($this->once())
            ->method('verifyAddress')
            ->with('0x1234567890abcdef')
            ->willReturn($verification);

        $exitCode = Artisan::call('web3:manage', [
            'action' => 'verify',
            '--address' => '0x1234567890abcdef'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-008
     * @coverage 100%
     * @description Test Web3 manager command verify action without address
     */
    public function test_web3_manager_verify_action_without_address()
    {
        $exitCode = Artisan::call('web3:manage', ['action' => 'verify']);

        $this->assertEquals(1, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-009
     * @coverage 100%
     * @description Test Web3 manager command with invalid action
     */
    public function test_web3_manager_invalid_action()
    {
        $exitCode = Artisan::call('web3:manage', ['action' => 'invalid']);

        $this->assertEquals(1, $exitCode);
    }

    /**
     * @test
     * @checklistItem WEB3-010
     * @coverage 100%
     * @description Test Web3 manager command with exception
     */
    public function test_web3_manager_with_exception()
    {
        $this->web3Service
            ->expects($this->once())
            ->method('getStatus')
            ->willThrowException(new \Exception('Web3 service unavailable'));

        $exitCode = Artisan::call('web3:manage', ['action' => 'status']);

        $this->assertEquals(1, $exitCode);
    }
} 