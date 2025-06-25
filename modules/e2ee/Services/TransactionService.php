<?php

namespace App\Modules\E2ee\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Modules\E2ee\Exceptions\TransactionException;
use App\Modules\E2ee\Models\EncryptionTransaction;
use App\Modules\Shared\Services\Core\AuditService;
use App\Modules\E2ee\Traits\E2eeConfigTrait;

class TransactionService extends BaseE2eeService
{
    use E2eeConfigTrait;

    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryptionService;

    /**
     * @var int
     */
    protected int $cacheTtl;

    /**
     * @var int
     */
    protected int $cleanupInterval;

    public function __construct(
        EncryptionService $encryptionService,
        AuditService $auditService
    ) {
        parent::__construct($auditService);
        $this->encryptionService = $encryptionService;
        $this->cacheTtl = $this->getCacheTtl();
        $this->cleanupInterval = $this->getCleanupInterval();
    }

    /**
     * Create a new encryption transaction
     */
    public function createTransaction(int $userId, array $metadata = []): string
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $metadata) {
                $this->validateUserId($userId);
                
                $transactionId = $this->generateTransactionId();
                
                $transaction = EncryptionTransaction::create([
                    'transaction_id' => $transactionId,
                    'user_id' => $userId,
                    'operation' => 'encrypt',
                    'status' => 'pending',
                    'metadata' => $metadata,
                    'timestamp' => now(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return $transactionId;
            },
            'create_transaction',
            ['user_id' => $userId, 'metadata' => $metadata]
        );
    }

    /**
     * Start a new E2EE transaction with enhanced state management
     */
    public function startTransaction(int $userId, array $metadata = []): EncryptionTransaction
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $metadata) {
                $this->validateUserId($userId);
                
                $transactionId = $this->generateTransactionId();
                
                $transaction = EncryptionTransaction::create([
                    'transaction_id' => $transactionId,
                    'user_id' => $userId,
                    'operation' => 'encrypt',
                    'status' => 'active',
                    'metadata' => array_merge($metadata, [
                        'e2ee_enabled' => true,
                        'started_at' => now()->toISOString()
                    ]),
                    'timestamp' => now(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Cache transaction for performance
                $this->cacheTransaction($transaction);

                return $transaction;
            },
            'start_transaction',
            ['user_id' => $userId, 'metadata' => $metadata]
        );
    }

    /**
     * Encrypt data within a transaction
     */
    public function encryptInTransaction(string $data, int $userId, string $transactionId, array $metadata = []): array
    {
        return $this->executeWithErrorHandling(
            function () use ($data, $userId, $transactionId, $metadata) {
                $this->validateUserId($userId);
                $this->validateInputData($data, 'encrypt_in_transaction');

                // Update transaction status
                $this->updateTransactionStatus($transactionId, 'encrypting');

                // Perform encryption
                $result = $this->encryptionService->encryptForTransaction($data, $userId, $transactionId, $metadata);

                // Update transaction status
                $this->updateTransactionStatus($transactionId, 'completed');

                return $result;
            },
            'encrypt_in_transaction',
            ['transaction_id' => $transactionId, 'user_id' => $userId, 'data_length' => strlen($data)]
        );
    }

    /**
     * Decrypt data within a transaction
     */
    public function decryptInTransaction(string $encryptedData, string $iv, int $userId, string $transactionId, string $tag = null): string
    {
        return $this->executeWithErrorHandling(
            function () use ($encryptedData, $iv, $userId, $transactionId, $tag) {
                $this->validateUserId($userId);
                $this->validateInputData($encryptedData, 'decrypt_in_transaction');

                // Update transaction status
                $this->updateTransactionStatus($transactionId, 'decrypting');

                // Perform decryption
                $result = $this->encryptionService->decryptForTransaction($encryptedData, $iv, $userId, $transactionId, $tag);

                // Update transaction status
                $this->updateTransactionStatus($transactionId, 'completed');

                return $result;
            },
            'decrypt_in_transaction',
            ['transaction_id' => $transactionId, 'user_id' => $userId]
        );
    }

    /**
     * Encrypt transaction data
     */
    public function encryptTransactionData(string $transactionId, array $data): array
    {
        return $this->executeWithErrorHandling(
            function () use ($transactionId, $data) {
                $transaction = $this->getTransaction($transactionId);
                
                if (!$transaction || $transaction->status !== 'active') {
                    throw new TransactionException('Invalid or inactive transaction', 'INVALID_TRANSACTION', []);
                }

                $userId = $transaction->user_id;
                $dataString = json_encode($data);

                return $this->encryptInTransaction($dataString, $userId, $transactionId, [
                    'data_type' => 'json',
                    'original_size' => strlen($dataString)
                ]);
            },
            'encrypt_transaction_data',
            ['transaction_id' => $transactionId, 'data_size' => count($data)]
        );
    }

    /**
     * Decrypt transaction data
     */
    public function decryptTransactionData(string $transactionId, array $encryptedData): array
    {
        return $this->executeWithErrorHandling(
            function () use ($transactionId, $encryptedData) {
                $transaction = $this->getTransaction($transactionId);
                
                if (!$transaction || $transaction->status !== 'active') {
                    throw new TransactionException('Invalid or inactive transaction', 'INVALID_TRANSACTION', []);
                }

                $userId = $transaction->user_id;
                
                if (!isset($encryptedData['encrypted_data'], $encryptedData['iv'])) {
                    throw new TransactionException('Invalid encrypted data format', 'INVALID_ENCRYPTED_DATA', []);
                }

                $decryptedString = $this->decryptInTransaction(
                    $encryptedData['encrypted_data'],
                    $encryptedData['iv'],
                    $userId,
                    $transactionId,
                    $encryptedData['tag'] ?? null
                );

                return json_decode($decryptedString, true) ?? [];
            },
            'decrypt_transaction_data',
            ['transaction_id' => $transactionId]
        );
    }

    /**
     * Enable/disable E2EE for a transaction (unique per-transaction control)
     */
    public function setTransactionE2ee(string $transactionId, bool $enabled): bool
    {
        return $this->executeWithErrorHandling(
            function () use ($transactionId, $enabled) {
                $transaction = $this->getTransaction($transactionId);
                
                if (!$transaction || $transaction->status !== 'active') {
                    throw new TransactionException('Invalid or inactive transaction', 'INVALID_TRANSACTION', []);
                }
                
                $metadata = $transaction->metadata ?? [];
                $metadata['e2ee_enabled'] = $enabled;
                
                $transaction->update([
                    'metadata' => $metadata,
                    'updated_at' => now(),
                ]);
                
                // Update cache
                $this->cacheTransaction($transaction);
                
                return true;
            },
            'set_transaction_e2ee',
            ['transaction_id' => $transactionId, 'enabled' => $enabled]
        );
    }

    /**
     * Complete a transaction
     */
    public function completeTransaction(string $transactionId, array $metadata = []): bool
    {
        return $this->executeWithErrorHandling(
            function () use ($transactionId, $metadata) {
                $transaction = $this->getTransaction($transactionId);
                
                if (!$transaction) {
                    throw new TransactionException('Transaction not found', 'TRANSACTION_NOT_FOUND', []);
                }
                
                if ($transaction->status === 'completed') {
                    return true; // Already completed
                }
                
                $updatedMetadata = array_merge($transaction->metadata ?? [], $metadata, [
                    'completed_at' => now()->toISOString()
                ]);
                
                $transaction->update([
                    'status' => 'completed',
                    'metadata' => $updatedMetadata,
                    'updated_at' => now(),
                ]);
                
                // Clear cache
                $this->clearTransactionCache($transactionId);
                
                return true;
            },
            'complete_transaction',
            ['transaction_id' => $transactionId, 'metadata' => $metadata]
        );
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction(string $transactionId): ?EncryptionTransaction
    {
        // Try cache first
        $cached = $this->getCachedTransaction($transactionId);
        if ($cached) {
            return $cached;
        }

        // Get from database
        $transaction = EncryptionTransaction::where('transaction_id', $transactionId)->first();
        
        if ($transaction) {
            $this->cacheTransaction($transaction);
        }

        return $transaction;
    }

    /**
     * Get user transactions
     */
    public function getUserTransactions(int $userId, int $limit = 50, int $offset = 0): array
    {
        $this->validateUserId($userId);

        return EncryptionTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    /**
     * Get user active transactions
     */
    public function getUserActiveTransactions(int $userId): array
    {
        $this->validateUserId($userId);

        return EncryptionTransaction::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStatistics(): array
    {
        $total = EncryptionTransaction::count();
        $active = EncryptionTransaction::where('status', 'active')->count();
        $completed = EncryptionTransaction::where('status', 'completed')->count();
        $failed = EncryptionTransaction::where('status', 'failed')->count();

        return [
            'total_transactions' => $total,
            'active_transactions' => $active,
            'completed_transactions' => $completed,
            'failed_transactions' => $failed,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Cleanup old transactions
     */
    public function cleanupOldTransactions(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $deleted = EncryptionTransaction::where('created_at', '<', $cutoffDate)
            ->where('status', 'completed')
            ->delete();

        $this->logOperation('cleanup_old_transactions', [
            'days_old' => $daysOld,
            'deleted_count' => $deleted
        ]);

        return $deleted;
    }

    /**
     * Benchmark transaction operations
     */
    public function benchmarkTransactions(int $iterations = 100): array
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $userId = 1; // Test user ID
            $transaction = $this->startTransaction($userId, ['benchmark' => true]);
            $this->completeTransaction($transaction->transaction_id);
        }

        $endTime = microtime(true);
        $memoryEnd = memory_get_usage();

        return [
            'iterations' => $iterations,
            'total_time' => round($endTime - $startTime, 4),
            'average_time' => round(($endTime - $startTime) / $iterations, 4),
            'memory_used' => round(($memoryEnd - $memoryStart) / 1024, 2), // KB
            'operations_per_second' => round($iterations / ($endTime - $startTime), 2),
        ];
    }

    /**
     * Validate transaction
     */
    public function validateTransaction(string $transactionId, int $userId): bool
    {
        try {
            $this->validateUserId($userId);
            
            $transaction = $this->getTransaction($transactionId);
            
            if (!$transaction) {
                return false;
            }
            
            if ($transaction->user_id !== $userId) {
                return false;
            }
            
            if ($transaction->status !== 'active') {
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate transaction ID
     */
    protected function generateTransactionId(): string
    {
        return uniqid('e2ee_txn_', true) . '_' . time();
    }

    /**
     * Update transaction status
     */
    protected function updateTransactionStatus(string $transactionId, string $status, array $metadata = []): void
    {
        $transaction = EncryptionTransaction::where('transaction_id', $transactionId)->first();
        
        if ($transaction) {
            $updatedMetadata = array_merge($transaction->metadata ?? [], $metadata);
            
            $transaction->update([
                'status' => $status,
                'metadata' => $updatedMetadata,
                'updated_at' => now(),
            ]);
            
            $this->cacheTransaction($transaction);
        }
    }

    /**
     * Cache transaction
     */
    protected function cacheTransaction(EncryptionTransaction $transaction): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        $cacheKey = "e2ee:transaction:{$transaction->transaction_id}";
        Cache::put($cacheKey, $transaction, $this->cacheTtl);
    }

    /**
     * Get cached transaction
     */
    protected function getCachedTransaction(string $transactionId): ?EncryptionTransaction
    {
        if (!$this->isCachingEnabled()) {
            return null;
        }

        $cacheKey = "e2ee:transaction:{$transactionId}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear transaction cache
     */
    protected function clearTransactionCache(string $transactionId): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        $cacheKey = "e2ee:transaction:{$transactionId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get service statistics
     */
    public function getStatistics(): array
    {
        return $this->getTransactionStatistics();
    }

    /**
     * Validate service parameters
     */
    public function validateParameters(): bool
    {
        return $this->cacheTtl > 0 && $this->cleanupInterval > 0;
    }
} 