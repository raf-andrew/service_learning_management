<?php

namespace Tests\Unit;

use Tests\BaseTestCase;
use App\Modules\E2ee\Services\EncryptionService;
use App\Modules\E2ee\Services\KeyManagementService;
use App\Modules\E2ee\Services\AuditService;
use App\Modules\E2ee\Models\EncryptionKey;
use App\Modules\E2ee\Models\EncryptionTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class E2EETest extends BaseTestCase
{
    use RefreshDatabase;

    protected array $testModules = ['e2ee'];

    protected EncryptionService $encryptionService;
    protected KeyManagementService $keyManagementService;
    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->encryptionService = app(EncryptionService::class);
        $this->keyManagementService = app(KeyManagementService::class);
        $this->auditService = app(AuditService::class);
    }

    /**
     * Test encryption service initialization
     */
    public function test_encryption_service_initialization(): void
    {
        $this->assertInstanceOf(EncryptionService::class, $this->encryptionService);
        $this->assertInstanceOf(KeyManagementService::class, $this->keyManagementService);
        $this->assertInstanceOf(AuditService::class, $this->auditService);
    }

    /**
     * Test basic encryption and decryption
     */
    public function test_basic_encryption_decryption(): void
    {
        $testData = 'This is a test message for encryption';
        $userId = 1;

        // Encrypt data
        $encrypted = $this->encryptionService->encrypt($testData, $userId);

        $this->assertIsArray($encrypted);
        $this->assertArrayHasKey('encrypted_data', $encrypted);
        $this->assertArrayHasKey('iv', $encrypted);
        $this->assertArrayHasKey('transaction_id', $encrypted);
        $this->assertArrayHasKey('algorithm', $encrypted);

        // Decrypt data
        $decrypted = $this->encryptionService->decrypt(
            $encrypted['encrypted_data'],
            $encrypted['iv'],
            $userId
        );

        $this->assertEquals($testData, $decrypted);
    }

    /**
     * Test encryption with metadata
     */
    public function test_encryption_with_metadata(): void
    {
        $testData = 'Test data with metadata';
        $userId = 1;
        $metadata = [
            'source' => 'test',
            'timestamp' => time(),
            'version' => '1.0'
        ];

        $encrypted = $this->encryptionService->encrypt($testData, $userId, $metadata);

        $this->assertArrayHasKey('transaction_id', $encrypted);

        // Verify transaction record was created with metadata
        $transaction = EncryptionTransaction::where('transaction_id', $encrypted['transaction_id'])->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($metadata, $transaction->metadata);
    }

    /**
     * Test batch encryption
     */
    public function test_batch_encryption(): void
    {
        $testItems = [
            'Item 1',
            'Item 2',
            'Item 3',
            'Item 4',
            'Item 5'
        ];
        $userId = 1;

        $results = $this->encryptionService->batchEncrypt($testItems, $userId);

        $this->assertCount(count($testItems), $results);
        
        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('encrypted_data', $result);
            $this->assertArrayHasKey('iv', $result);
        }
    }

    /**
     * Test key generation
     */
    public function test_key_generation(): void
    {
        $key = $this->encryptionService->generateKey(32);
        
        $this->assertIsString($key);
        $this->assertNotEmpty($key);
        $this->assertTrue($this->encryptionService->validateKey($key));
    }

    /**
     * Test key validation
     */
    public function test_key_validation(): void
    {
        // Valid key
        $validKey = base64_encode(random_bytes(32));
        $this->assertTrue($this->encryptionService->validateKey($validKey));

        // Invalid key (too short)
        $invalidKey = base64_encode(random_bytes(8));
        $this->assertFalse($this->encryptionService->validateKey($invalidKey));

        // Invalid key (not base64)
        $this->assertFalse($this->encryptionService->validateKey('invalid-key'));
    }

    /**
     * Test salt generation
     */
    public function test_salt_generation(): void
    {
        $salt = $this->encryptionService->generateSalt(16);
        
        $this->assertIsString($salt);
        $this->assertNotEmpty($salt);
        
        // Decode and check length
        $decoded = base64_decode($salt);
        $this->assertEquals(16, strlen($decoded));
    }

    /**
     * Test encryption cycle validation
     */
    public function test_encryption_cycle(): void
    {
        $result = $this->encryptionService->testEncryptionCycle();
        $this->assertTrue($result);
    }

    /**
     * Test supported algorithms
     */
    public function test_supported_algorithms(): void
    {
        $algorithms = $this->encryptionService->getSupportedAlgorithms();
        
        $this->assertIsArray($algorithms);
        $this->assertContains('AES-256-GCM', $algorithms);
        $this->assertContains('AES-256-CBC', $algorithms);
    }

    /**
     * Test parameter validation
     */
    public function test_parameter_validation(): void
    {
        // Valid parameters
        $this->assertTrue($this->encryptionService->validateParameters('AES-256-GCM', 32));

        // Invalid algorithm
        $this->assertFalse($this->encryptionService->validateParameters('INVALID-ALGORITHM', 32));

        // Invalid key length
        $this->assertFalse($this->encryptionService->validateParameters('AES-256-GCM', 8));
    }

    /**
     * Test encryption statistics
     */
    public function test_encryption_statistics(): void
    {
        $stats = $this->encryptionService->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_transactions', $stats);
        $this->assertArrayHasKey('encrypt_operations', $stats);
        $this->assertArrayHasKey('decrypt_operations', $stats);
        $this->assertArrayHasKey('algorithm', $stats);
        $this->assertArrayHasKey('key_length', $stats);
        $this->assertArrayHasKey('audit_enabled', $stats);
    }

    /**
     * Test error handling for invalid input
     */
    public function test_error_handling_invalid_input(): void
    {
        $this->expectException(\App\Modules\E2ee\Exceptions\E2eeException::class);

        // Test with empty data
        $this->encryptionService->encrypt('', 1);
    }

    /**
     * Test error handling for invalid user ID
     */
    public function test_error_handling_invalid_user_id(): void
    {
        $this->expectException(\App\Modules\E2ee\Exceptions\E2eeException::class);

        // Test with invalid user ID
        $this->encryptionService->encrypt('test data', 0);
    }

    /**
     * Test performance metrics
     */
    public function test_performance_metrics(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Perform encryption
        $this->encryptionService->encrypt('Performance test data', 1);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Assert performance thresholds
        $this->assertLessThan($this->performanceThresholds['response_time'], $executionTime);
        $this->assertLessThan($this->performanceThresholds['memory_usage'], $memoryUsed);
    }
} 