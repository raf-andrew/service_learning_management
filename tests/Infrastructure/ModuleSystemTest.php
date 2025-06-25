<?php

namespace Tests\Infrastructure;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Shared\ModuleDiscoveryService;
use App\Modules\E2ee\Services\EncryptionService;
use App\Modules\E2ee\Services\KeyManagementService;
use App\Modules\E2ee\Services\TransactionService;
use App\Modules\Shared\AuditService;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        \App\Models\User::create([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Test module discovery service
     */
    public function test_module_discovery_service()
    {
        $discoveryService = app(ModuleDiscoveryService::class);
        $modules = $discoveryService->discoverModules();

        $this->assertNotEmpty($modules);
        $moduleNames = $modules->pluck('name');
        fwrite(STDERR, 'Discovered modules: ' . implode(", ", $moduleNames->toArray()) . "\n");
        $this->assertTrue($moduleNames->contains('e2ee'));
        $this->assertTrue($moduleNames->contains('soc2'));
        // $this->assertTrue($moduleNames->contains('shared')); // shared is excluded by config
    }

    /**
     * Test E2EE encryption service
     */
    public function test_e2ee_encryption_service()
    {
        $encryptionService = app(EncryptionService::class);
        $keyManagementService = app(KeyManagementService::class);

        // Test key generation
        $userId = 1;
        $userKey = $keyManagementService->getUserKey($userId);
        
        $this->assertNotNull($userKey);
        $this->assertEquals($userId, $userKey->user_id);
        $this->assertEquals('active', $userKey->status);

        // Test encryption
        $testData = 'Hello, World!';
        $result = $encryptionService->encrypt($testData, $userId);

        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('encrypted_data', $result);
        $this->assertArrayHasKey('iv', $result);
        $this->assertArrayHasKey('algorithm', $result);

        // Test decryption
        $decryptedData = $encryptionService->decrypt(
            $result['encrypted_data'],
            $result['iv'],
            $userId,
            $result['transaction_id'],
            isset($result['tag']) ? $result['tag'] : null
        );

        $this->assertEquals($testData, $decryptedData);
    }

    /**
     * Test E2EE transaction service
     */
    public function test_e2ee_transaction_service()
    {
        $transactionService = app(TransactionService::class);
        $userId = 1;

        // Test transaction creation
        $transactionId = $transactionService->createTransaction($userId, [
            'test' => true,
            'description' => 'Test transaction'
        ]);

        $this->assertNotEmpty($transactionId);

        // Test transaction retrieval
        $transaction = $transactionService->getTransaction($transactionId);
        $this->assertNotNull($transaction);
        $this->assertEquals($userId, $transaction->user_id);
        $this->assertEquals('pending', $transaction->status);

        // Test transaction statistics
        $stats = $transactionService->getTransactionStatistics();
        $this->assertArrayHasKey('total_transactions', $stats);
        $this->assertArrayHasKey('pending_transactions', $stats);
        $this->assertArrayHasKey('completed_transactions', $stats);
    }

    /**
     * Test key management service
     */
    public function test_key_management_service()
    {
        $keyManagementService = app(KeyManagementService::class);
        $userId = 1;

        // Test key generation
        $userKeys = $keyManagementService->generateUserKeys($userId);
        $this->assertNotNull($userKeys);
        $this->assertArrayHasKey('key_id', $userKeys);
        $this->assertArrayHasKey('master_key', $userKeys);
        $this->assertArrayHasKey('user_key', $userKeys);

        // Test key statistics
        $stats = $keyManagementService->getKeyStatistics();
        $this->assertArrayHasKey('total_keys', $stats);
        $this->assertArrayHasKey('active_keys', $stats);
        $this->assertArrayHasKey('rotated_keys', $stats);

        // Test key rotation
        $newKeys = $keyManagementService->rotateUserKeys($userId);
        $this->assertNotNull($newKeys);
        $this->assertArrayHasKey('key_id', $newKeys);
        $this->assertArrayHasKey('master_key', $newKeys);
        $this->assertArrayHasKey('user_key', $newKeys);
    }

    /**
     * Test audit service
     */
    public function test_audit_service()
    {
        $auditService = app(AuditService::class);

        // Test audit logging
        $auditService->log('test', 'test_action', [
            'test_data' => 'test_value'
        ]);

        // Test audit statistics
        $stats = $auditService->getStatistics();
        $this->assertArrayHasKey('total_logs', $stats);
        $this->assertArrayHasKey('modules', $stats);
        $this->assertArrayHasKey('recent_activity', $stats);
    }

    /**
     * Test module configuration
     */
    public function test_module_configuration()
    {
        $this->assertTrue((bool) config('modules.enabled'));
        $this->assertTrue((bool) config('modules.modules.e2ee.enabled'));
        $this->assertTrue((bool) config('modules.modules.soc2.enabled'));
        $this->assertTrue((bool) config('modules.modules.mcp.enabled'));
    }

    /**
     * Test service provider registration
     */
    public function test_service_provider_registration()
    {
        // Test that services are properly registered
        $this->assertInstanceOf(EncryptionService::class, app(EncryptionService::class));
        $this->assertInstanceOf(KeyManagementService::class, app(KeyManagementService::class));
        $this->assertInstanceOf(TransactionService::class, app(TransactionService::class));
        $this->assertInstanceOf(AuditService::class, app(AuditService::class));
        $this->assertInstanceOf(ModuleDiscoveryService::class, app(ModuleDiscoveryService::class));
    }

    /**
     * Test module autoloading
     */
    public function test_module_autoloading()
    {
        // Test that module classes can be instantiated
        $this->assertTrue(class_exists('App\Modules\E2ee\Services\EncryptionService'));
        $this->assertTrue(class_exists('App\Modules\E2ee\Services\KeyManagementService'));
        $this->assertTrue(class_exists('App\Modules\E2ee\Services\TransactionService'));
        $this->assertTrue(class_exists('App\Modules\Shared\AuditService'));
        $this->assertTrue(class_exists('App\Modules\Shared\ModuleDiscoveryService'));
    }

    /**
     * Test error handling
     */
    public function test_error_handling()
    {
        $encryptionService = app(EncryptionService::class);

        // Test invalid user ID
        $this->expectException(\Exception::class);
        $encryptionService->encrypt('test', 0);
    }

    /**
     * Test performance
     */
    public function test_performance()
    {
        $encryptionService = app(EncryptionService::class);
        $userId = 1;

        $startTime = microtime(true);
        
        // Perform multiple encryption operations
        for ($i = 0; $i < 10; $i++) {
            $encryptionService->encrypt("Test data {$i}", $userId);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete within 5 seconds
        $this->assertLessThan(5.0, $duration);
    }
} 