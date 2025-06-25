<?php

namespace App\Modules\E2ee\Services;

use App\Modules\E2ee\Exceptions\E2eeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Modules\E2ee\Exceptions\EncryptionException;
use App\Modules\E2ee\Models\EncryptionKey;
use App\Modules\E2ee\Models\EncryptionTransaction;
use App\Modules\Shared\AuditService;
use Illuminate\Support\Facades\Event;
use App\Modules\E2ee\Events\DataEncrypted;
use App\Modules\E2ee\Events\DataDecrypted;

class EncryptionService
{
    /**
     * @var KeyManagementService
     */
    protected KeyManagementService $keyManager;

    /**
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * @var string
     */
    protected string $algorithm;

    /**
     * @var int
     */
    protected int $keyLength;

    /**
     * @var bool
     */
    protected bool $auditEnabled;

    public function __construct(
        KeyManagementService $keyManager,
        AuditService $auditService
    ) {
        $this->keyManager = $keyManager;
        $this->auditService = $auditService;
        $this->algorithm = config('modules.modules.e2ee.encryption_algorithm', 'AES-256-GCM');
        $this->keyLength = config('modules.security.encryption.key_length', 32);
        $this->auditEnabled = config('modules.modules.e2ee.audit_enabled', true);
    }

    /**
     * Default encryption algorithm
     */
    private const DEFAULT_ALGORITHM = 'AES-256-GCM';

    /**
     * Default key derivation algorithm
     */
    private const DEFAULT_KEY_DERIVATION = 'PBKDF2';

    /**
     * Default iterations for key derivation
     */
    private const DEFAULT_ITERATIONS = 100000;

    /**
     * Default salt length
     */
    private const DEFAULT_SALT_LENGTH = 32;

    /**
     * Default tag length for GCM
     */
    private const DEFAULT_TAG_LENGTH = 16;

    /**
     * Encrypt data with AES-256-GCM
     */
    public function encrypt(string $data, int $userId, array $metadata = []): array
    {
        try {
            $this->logOperation('encrypt_start', [
                'user_id' => $userId,
                'data_length' => strlen($data),
                'algorithm' => $this->algorithm
            ]);

            // Get or generate user's encryption key
            $userKey = $this->keyManager->getUserKey($userId);
            
            // Generate a unique transaction ID
            $transactionId = $this->generateTransactionId();
            
            // Generate a random IV for this encryption
            $iv = random_bytes(openssl_cipher_iv_length($this->algorithm));
            
            // Initialize tag for GCM mode
            $tag = '';
            $options = OPENSSL_RAW_DATA;
            
            // For GCM mode, we need to handle the tag properly
            if (strpos($this->algorithm, 'GCM') !== false) {
                $tag = str_repeat("\0", 16); // Initialize 16-byte tag
                $options = OPENSSL_RAW_DATA;
            }
            
            // Encrypt the data
            $encryptedData = openssl_encrypt(
                $data,
                $this->algorithm,
                $userKey->key,
                $options,
                $iv,
                $tag
            );

            if ($encryptedData === false) {
                throw new EncryptionException('Failed to encrypt data', 'ENCRYPTION_FAILED', []);
            }

            // Store transaction record
            $transaction = $this->createTransactionRecord($transactionId, $userId, 'encrypt', $metadata);

            $result = [
                'transaction_id' => $transactionId,
                'encrypted_data' => base64_encode($encryptedData),
                'iv' => base64_encode($iv),
                'algorithm' => $this->algorithm,
                'key_id' => $userKey->id,
                'timestamp' => now()->toISOString(),
            ];

            if ($tag) {
                $result['tag'] = base64_encode($tag);
            }

            $this->logOperation('encrypt_success', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'key_id' => $userKey->id
            ]);

            // Log encryption event
            $this->logEncryption($data, $result['encrypted_data'], $userKey->id);

            // Fire encryption event
            Event::dispatch(new DataEncrypted($data, $result['encrypted_data'], $userKey->id));

            return $result;

        } catch (\Exception $e) {
            $this->logError('encrypt', $e->getMessage());
            throw new EncryptionException('Encryption failed: ' . $e->getMessage(), 'ENCRYPTION_ERROR', [], 0, $e);
        }
    }

    /**
     * Decrypt data with AES-256-GCM
     */
    public function decrypt(string $encryptedData, string $iv, int $userId, string $transactionId = null, string $tag = null): string
    {
        try {
            $this->logOperation('decrypt_start', [
                'user_id' => $userId,
                'transaction_id' => $transactionId,
                'algorithm' => $this->algorithm
            ]);

            // Get user's encryption key
            $userKey = $this->keyManager->getUserKey($userId);
            
            // Decode base64 data
            $encryptedBytes = base64_decode($encryptedData);
            $ivBytes = base64_decode($iv);
            
            // Handle tag for GCM mode
            $tagBytes = '';
            if (strpos($this->algorithm, 'GCM') !== false && $tag) {
                $tagBytes = base64_decode($tag);
            }

            // Decrypt the data
            $decryptedData = openssl_decrypt(
                $encryptedBytes,
                $this->algorithm,
                $userKey->key,
                OPENSSL_RAW_DATA,
                $ivBytes,
                $tagBytes
            );

            if ($decryptedData === false) {
                throw new EncryptionException('Failed to decrypt data - invalid key or corrupted data', 'DECRYPTION_FAILED', []);
            }

            // Create transaction record if not provided and not already existing
            if (!$transactionId) {
                $transactionId = $this->generateTransactionId();
            }
            if (!EncryptionTransaction::where('transaction_id', $transactionId)->exists()) {
                $this->createTransactionRecord($transactionId, $userId, 'decrypt', [
                    'key_id' => $userKey->id,
                    'algorithm' => $this->algorithm
                ]);
            }

            $this->logOperation('decrypt_success', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'key_id' => $userKey->id
            ]);

            // Log decryption event
            $this->logDecryption($encryptedData, $decryptedData, $userKey->id);

            // Fire decryption event
            Event::dispatch(new DataDecrypted($encryptedData, $decryptedData, $userKey->id));

            return $decryptedData;

        } catch (\Exception $e) {
            $this->logError('decrypt', $e->getMessage());
            throw new EncryptionException('Decryption failed: ' . $e->getMessage(), 'DECRYPTION_ERROR', [], 0, $e);
        }
    }

    /**
     * Encrypt data for specific transaction
     */
    public function encryptForTransaction(string $data, int $userId, string $transactionId, array $metadata = []): array
    {
        $result = $this->encrypt($data, $userId, $metadata);
        $result['transaction_id'] = $transactionId;
        
        // Update transaction record
        $this->updateTransactionRecord($transactionId, $userId, 'encrypt', $metadata);
        
        return $result;
    }

    /**
     * Decrypt data for specific transaction
     */
    public function decryptForTransaction(string $encryptedData, string $iv, int $userId, string $transactionId, string $tag = null): string
    {
        $result = $this->decrypt($encryptedData, $iv, $userId, $transactionId, $tag);
        
        // Update transaction record
        $this->updateTransactionRecord($transactionId, $userId, 'decrypt', [
            'key_id' => $this->keyManager->getUserKey($userId)->id
        ]);
        
        return $result;
    }

    /**
     * Generate a unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return uniqid('e2ee_', true) . '_' . time();
    }

    /**
     * Create a transaction record
     */
    protected function createTransactionRecord(string $transactionId, int $userId, string $operation, array $metadata = []): EncryptionTransaction
    {
        return EncryptionTransaction::create([
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'operation' => $operation,
            'algorithm' => $this->algorithm,
            'metadata' => $metadata,
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Update a transaction record
     */
    protected function updateTransactionRecord(string $transactionId, int $userId, string $operation, array $metadata = []): void
    {
        EncryptionTransaction::where('transaction_id', $transactionId)
            ->update([
                'operation' => $operation,
                'metadata' => array_merge(
                    EncryptionTransaction::where('transaction_id', $transactionId)->first()->metadata ?? [],
                    $metadata
                ),
                'updated_at' => now(),
            ]);
    }

    /**
     * Log operation for audit purposes
     */
    protected function logOperation(string $action, array $context = []): void
    {
        if ($this->auditEnabled) {
            $this->auditService->log('e2ee', $action, $context);
        }
        
        Log::info("E2EE: {$action}", $context);
    }

    /**
     * Get encryption statistics
     */
    public function getStatistics(): array
    {
        $totalTransactions = EncryptionTransaction::count();
        $encryptCount = EncryptionTransaction::where('operation', 'encrypt')->count();
        $decryptCount = EncryptionTransaction::where('operation', 'decrypt')->count();
        
        return [
            'total_transactions' => $totalTransactions,
            'encrypt_operations' => $encryptCount,
            'decrypt_operations' => $decryptCount,
            'algorithm' => $this->algorithm,
            'key_length' => $this->keyLength,
            'audit_enabled' => $this->auditEnabled,
        ];
    }

    /**
     * Validate encryption parameters
     */
    public function validateParameters(string $algorithm = null, int $keyLength = null): bool
    {
        $algorithm = $algorithm ?? $this->algorithm;
        $keyLength = $keyLength ?? $this->keyLength;

        // Check if algorithm is supported
        $supportedAlgorithms = openssl_get_cipher_methods();
        if (!in_array($algorithm, $supportedAlgorithms)) {
            return false;
        }

        // Check key length
        if ($keyLength < 16 || $keyLength > 64) {
            return false;
        }

        return true;
    }

    /**
     * Generate a random encryption key
     */
    public function generateKey(int $length = 32): string
    {
        try {
            if ($length < 16 || $length > 64) {
                throw new E2eeException('Key length must be between 16 and 64 bytes', 'INVALID_KEY_LENGTH');
            }

            $key = random_bytes($length);

            $this->logOperation('generate_key', [
                'length' => $length,
                'key_hash' => hash('sha256', $key),
            ]);

            return base64_encode($key);

        } catch (\Exception $e) {
            $this->logError('generate_key', $e->getMessage());
            throw new E2eeException('Key generation failed: ' . $e->getMessage(), 'KEY_GENERATION_ERROR');
        }
    }

    /**
     * Derive a key from a password using PBKDF2
     */
    public function deriveKey(string $password, string $salt = null, int $iterations = null): array
    {
        try {
            $iterations = $iterations ?? config('e2ee.key_derivation.iterations', self::DEFAULT_ITERATIONS);
            $saltLength = config('e2ee.key_derivation.salt_length', self::DEFAULT_SALT_LENGTH);
            $salt = $salt ?? random_bytes($saltLength);

            if (strlen($salt) !== $saltLength) {
                throw new E2eeException('Invalid salt length', 'INVALID_SALT');
            }

            // Derive key using PBKDF2
            $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

            $result = [
                'key' => base64_encode($key),
                'salt' => base64_encode($salt),
                'iterations' => $iterations,
            ];

            $this->logOperation('derive_key', [
                'iterations' => $iterations,
                'salt_length' => strlen($salt),
                'key_hash' => hash('sha256', $key),
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logError('derive_key', $e->getMessage());
            throw new E2eeException('Key derivation failed: ' . $e->getMessage(), 'KEY_DERIVATION_ERROR');
        }
    }

    /**
     * Validate encryption key
     */
    public function validateKey(string $key): bool
    {
        try {
            $decoded = base64_decode($key);
            if ($decoded === false) {
                return false;
            }

            $length = strlen($decoded);
            return $length >= 16 && $length <= 64;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a secure random salt
     */
    public function generateSalt(int $length = null): string
    {
        $length = $length ?? config('e2ee.key_derivation.salt_length', self::DEFAULT_SALT_LENGTH);
        return base64_encode(random_bytes($length));
    }

    /**
     * Encrypt data with caching
     */
    public function encryptWithCache(string $data, string $key, string $cacheKey, int $ttl = 3600): string
    {
        $cacheEnabled = config('e2ee.performance.cache_enabled', true);
        
        if (!$cacheEnabled) {
            return $this->encrypt($data, $key);
        }

        $cacheKey = "e2ee:encrypt:" . hash('sha256', $cacheKey);
        
        return Cache::remember($cacheKey, $ttl, function () use ($data, $key) {
            return $this->encrypt($data, $key);
        });
    }

    /**
     * Decrypt data with caching
     */
    public function decryptWithCache(string $encryptedData, string $key, string $cacheKey, int $ttl = 3600): string
    {
        $cacheEnabled = config('e2ee.performance.cache_enabled', true);
        
        if (!$cacheEnabled) {
            return $this->decrypt($encryptedData, $key);
        }

        $cacheKey = "e2ee:decrypt:" . hash('sha256', $cacheKey);
        
        return Cache::remember($cacheKey, $ttl, function () use ($encryptedData, $key) {
            return $this->decrypt($encryptedData, $key);
        });
    }

    /**
     * Batch encrypt multiple items
     */
    public function batchEncrypt(array $items, string $key): array
    {
        $results = [];
        $batchSize = config('e2ee.performance.batch_size', 100);

        foreach (array_chunk($items, $batchSize) as $batch) {
            foreach ($batch as $index => $item) {
                try {
                    $results[$index] = $this->encrypt($item, $key);
                } catch (\Exception $e) {
                    $results[$index] = null;
                    $this->logError('batch_encrypt', "Failed to encrypt item {$index}: " . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * Batch decrypt multiple items
     */
    public function batchDecrypt(array $items, string $key): array
    {
        $results = [];
        $batchSize = config('e2ee.performance.batch_size', 100);

        foreach (array_chunk($items, $batchSize) as $batch) {
            foreach ($batch as $index => $item) {
                try {
                    $results[$index] = $this->decrypt($item, $key);
                } catch (\Exception $e) {
                    $results[$index] = null;
                    $this->logError('batch_decrypt', "Failed to decrypt item {$index}: " . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * Validate input parameters
     */
    private function validateInput(string $data, string $key): void
    {
        if (empty($data)) {
            throw new E2eeException('Data cannot be empty', 'EMPTY_DATA');
        }

        if (empty($key)) {
            throw new E2eeException('Key cannot be empty', 'EMPTY_KEY');
        }

        if (!$this->validateKey($key)) {
            throw new E2eeException('Invalid key format', 'INVALID_KEY');
        }
    }

    /**
     * Log error for debugging
     */
    private function logError(string $operation, string $error): void
    {
        Log::error("E2EE {$operation} error", [
            'operation' => $operation,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get supported algorithms
     */
    public function getSupportedAlgorithms(): array
    {
        return [
            'AES-256-GCM',
            'AES-256-CBC',
            'ChaCha20-Poly1305',
        ];
    }

    /**
     * Test encryption/decryption cycle
     */
    public function testEncryptionCycle(string $testData = null): bool
    {
        try {
            $testData = $testData ?? Str::random(100);
            $key = $this->generateKey();
            
            $encrypted = $this->encrypt($testData, $key);
            $decrypted = $this->decrypt($encrypted, $key);
            
            return $testData === $decrypted;
        } catch (\Exception $e) {
            $this->logError('test_cycle', $e->getMessage());
            return false;
        }
    }

    /**
     * Log encryption event
     */
    private function logEncryption(string $originalData, string $encryptedData, ?string $keyId): void
    {
        $this->auditService->log('encryption', [
            'action' => 'encrypt',
            'algorithm' => $this->algorithm,
            'key_id' => $keyId,
            'data_length' => strlen($originalData),
            'encrypted_length' => strlen($encryptedData),
            'timestamp' => now(),
        ]);

        Log::info('Data encrypted', [
            'algorithm' => $this->algorithm,
            'key_id' => $keyId,
            'data_length' => strlen($originalData),
            'encrypted_length' => strlen($encryptedData),
        ]);
    }

    /**
     * Log decryption event
     */
    private function logDecryption(string $encryptedData, string $decryptedData, ?string $keyId): void
    {
        $this->auditService->log('decryption', [
            'action' => 'decrypt',
            'algorithm' => $this->algorithm,
            'key_id' => $keyId,
            'encrypted_length' => strlen($encryptedData),
            'decrypted_length' => strlen($decryptedData),
            'timestamp' => now(),
        ]);

        Log::info('Data decrypted', [
            'algorithm' => $this->algorithm,
            'key_id' => $keyId,
            'encrypted_length' => strlen($encryptedData),
            'decrypted_length' => strlen($decryptedData),
        ]);
    }
} 