<?php

namespace App\Modules\E2ee\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Modules\E2ee\Exceptions\TransactionException;
use App\Modules\E2ee\Models\EncryptionTransaction;
use App\Modules\Shared\AuditService;

class TransactionService
{
    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryptionService;

    /**
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * @var bool
     */
    protected bool $auditEnabled;

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
        $this->encryptionService = $encryptionService;
        $this->auditService = $auditService;
        $this->auditEnabled = config('modules.modules.e2ee.audit_enabled', true);
        $this->cacheTtl = config('modules.modules.e2ee.cache_ttl', 3600);
        $this->cleanupInterval = config('modules.modules.e2ee.cleanup_interval', 30);
    }

    /**
     * Create a new encryption transaction
     */
    public function createTransaction(int $userId, array $metadata = []): string
    {
        try {
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

            $this->logOperation('transaction_created', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'metadata' => $metadata
            ]);

            return $transactionId;

        } catch (\Exception $e) {
            $this->logOperation('transaction_creation_error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw new TransactionException('Failed to create transaction: ' . $e->getMessage(), 'TRANSACTION_CREATION_ERROR', [], 0, $e);
        }
    }

    /**
     * Start a new E2EE transaction with enhanced state management
     */
    public function startTransaction(int $userId, array $metadata = []): EncryptionTransaction
    {
        try {
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

            $this->logOperation('transaction_started', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'metadata' => $metadata
            ]);

            return $transaction;

        } catch (\Exception $e) {
            $this->logOperation('transaction_start_error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw new TransactionException('Failed to start transaction: ' . $e->getMessage(), 'TRANSACTION_START_ERROR', [], 0, $e);
        }
    }

    /**
     * Encrypt data within a transaction
     */
    public function encryptInTransaction(string $data, int $userId, string $transactionId, array $metadata = []): array
    {
        try {
            $this->logOperation('transaction_encrypt_start', [
                'transaction_id' => $transactionId,
                'user_id' => $userId
            ]);

            // Update transaction status
            $this->updateTransactionStatus($transactionId, 'encrypting');

            // Perform encryption
            $result = $this->encryptionService->encryptForTransaction($data, $userId, $transactionId, $metadata);

            // Update transaction status
            $this->updateTransactionStatus($transactionId, 'completed');

            $this->logOperation('transaction_encrypt_success', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'data_length' => strlen($data)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->updateTransactionStatus($transactionId, 'failed');
            
            $this->logOperation('transaction_encrypt_error', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw new TransactionException('Transaction encryption failed: ' . $e->getMessage(), 'TRANSACTION_ENCRYPT_ERROR', [], 0, $e);
        }
    }

    /**
     * Decrypt data within a transaction
     */
    public function decryptInTransaction(string $encryptedData, string $iv, int $userId, string $transactionId, string $tag = null): string
    {
        try {
            $this->logOperation('transaction_decrypt_start', [
                'transaction_id' => $transactionId,
                'user_id' => $userId
            ]);

            // Update transaction status
            $this->updateTransactionStatus($transactionId, 'decrypting');

            // Perform decryption
            $result = $this->encryptionService->decryptForTransaction($encryptedData, $iv, $userId, $transactionId, $tag);

            // Update transaction status
            $this->updateTransactionStatus($transactionId, 'completed');

            $this->logOperation('transaction_decrypt_success', [
                'transaction_id' => $transactionId,
                'user_id' => $userId
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->updateTransactionStatus($transactionId, 'failed');
            
            $this->logOperation('transaction_decrypt_error', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw new TransactionException('Transaction decryption failed: ' . $e->getMessage(), 'TRANSACTION_DECRYPT_ERROR', [], 0, $e);
        }
    }

    /**
     * Enable/disable E2EE for a transaction (unique per-transaction control)
     */
    public function setTransactionE2ee(string $transactionId, bool $enabled): bool
    {
        try {
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
            
            $this->logOperation('e2ee_toggled', [
                'transaction_id' => $transactionId,
                'user_id' => $transaction->user_id,
                'enabled' => $enabled
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logOperation('e2ee_toggle_error', [
                'transaction_id' => $transactionId,
                'enabled' => $enabled,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Complete a transaction
     */
    public function completeTransaction(string $transactionId, array $metadata = []): bool
    {
        try {
            $transaction = $this->getTransaction($transactionId);
            
            if (!$transaction || $transaction->status !== 'active') {
                throw new TransactionException('Invalid or inactive transaction', 'INVALID_TRANSACTION', []);
            }
            
            $existingMetadata = $transaction->metadata ?? [];
            $updatedMetadata = array_merge($existingMetadata, $metadata, [
                'completed_at' => now()->toISOString()
            ]);
            
            $transaction->update([
                'status' => 'completed',
                'metadata' => $updatedMetadata,
                'updated_at' => now(),
            ]);
            
            // Clear cache
            $this->clearTransactionCache($transactionId);
            
            $this->logOperation('transaction_completed', [
                'transaction_id' => $transactionId,
                'user_id' => $transaction->user_id,
                'metadata' => $metadata
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logOperation('transaction_completion_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $transactionId): ?EncryptionTransaction
    {
        // Check cache first
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
     * Get user's transactions
     */
    public function getUserTransactions(int $userId, int $limit = 50, int $offset = 0): array
    {
        $transactions = EncryptionTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return [
            'transactions' => $transactions,
            'total' => EncryptionTransaction::where('user_id', $userId)->count(),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get active transactions for a user
     */
    public function getUserActiveTransactions(int $userId): array
    {
        return EncryptionTransaction::where('user_id', $userId)
            ->where('status', 'active')
            ->get()
            ->toArray();
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStatistics(): array
    {
        $totalTransactions = EncryptionTransaction::count();
        $pendingTransactions = EncryptionTransaction::where('status', 'pending')->count();
        $completedTransactions = EncryptionTransaction::where('status', 'completed')->count();
        $failedTransactions = EncryptionTransaction::where('status', 'failed')->count();
        
        $encryptTransactions = EncryptionTransaction::where('operation', 'encrypt')->count();
        $decryptTransactions = EncryptionTransaction::where('operation', 'decrypt')->count();

        // Count E2EE enabled transactions
        $e2eeEnabledTransactions = EncryptionTransaction::whereRaw("JSON_EXTRACT(metadata, '$.e2ee_enabled') = true")->count();

        return [
            'total_transactions' => $totalTransactions,
            'pending_transactions' => $pendingTransactions,
            'completed_transactions' => $completedTransactions,
            'failed_transactions' => $failedTransactions,
            'encrypt_transactions' => $encryptTransactions,
            'decrypt_transactions' => $decryptTransactions,
            'e2ee_enabled_transactions' => $e2eeEnabledTransactions,
            'success_rate' => $totalTransactions > 0 ? ($completedTransactions / $totalTransactions) * 100 : 0,
            'cache_ttl' => $this->cacheTtl,
            'cleanup_interval' => $this->cleanupInterval,
        ];
    }

    /**
     * Clean up old transactions
     */
    public function cleanupOldTransactions(int $daysOld = 90): int
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            
            $deletedCount = EncryptionTransaction::where('created_at', '<', $cutoffDate)
                ->where('status', 'completed')
                ->delete();

            $this->logOperation('cleanup_old_transactions', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toISOString()
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            $this->logOperation('cleanup_error', [
                'error' => $e->getMessage()
            ]);
            throw new TransactionException('Failed to cleanup old transactions: ' . $e->getMessage(), 'CLEANUP_ERROR', [], 0, $e);
        }
    }

    /**
     * Benchmark transaction performance
     */
    public function benchmarkTransactions(int $iterations = 100): array
    {
        $results = [
            'transaction_creation' => [],
            'encryption' => [],
            'decryption' => [],
            'e2ee_toggle' => [],
            'completion' => []
        ];
        
        for ($i = 0; $i < $iterations; $i++) {
            // Benchmark transaction creation
            $start = microtime(true);
            $transaction = $this->startTransaction(1, ['benchmark' => true]);
            $results['transaction_creation'][] = (microtime(true) - $start) * 1000;
            
            // Benchmark encryption
            $testData = random_bytes(1024);
            $start = microtime(true);
            $encrypted = $this->encryptInTransaction($testData, 1, $transaction->transaction_id);
            $results['encryption'][] = (microtime(true) - $start) * 1000;
            
            // Benchmark decryption
            $start = microtime(true);
            $this->decryptInTransaction($encrypted['encrypted_data'], $encrypted['iv'], 1, $transaction->transaction_id);
            $results['decryption'][] = (microtime(true) - $start) * 1000;
            
            // Benchmark E2EE toggle
            $start = microtime(true);
            $this->setTransactionE2ee($transaction->transaction_id, false);
            $this->setTransactionE2ee($transaction->transaction_id, true);
            $results['e2ee_toggle'][] = (microtime(true) - $start) * 1000;
            
            // Benchmark completion
            $start = microtime(true);
            $this->completeTransaction($transaction->transaction_id);
            $results['completion'][] = (microtime(true) - $start) * 1000;
        }
        
        // Calculate averages
        foreach ($results as $operation => $times) {
            $results[$operation . '_average'] = array_sum($times) / count($times);
        }
        
        return $results;
    }

    /**
     * Validate transaction
     */
    public function validateTransaction(string $transactionId, int $userId): bool
    {
        $transaction = $this->getTransaction($transactionId);
        
        if (!$transaction) {
            return false;
        }

        // Check if transaction belongs to user
        if ($transaction->user_id !== $userId) {
            return false;
        }

        // Check if transaction is not too old (e.g., 24 hours)
        $maxAge = now()->subHours(24);
        if ($transaction->created_at < $maxAge) {
            return false;
        }

        return true;
    }

    /**
     * Generate a unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return uniqid('e2ee_tx_', true) . '_' . time();
    }

    /**
     * Update transaction status
     */
    protected function updateTransactionStatus(string $transactionId, string $status, array $metadata = []): void
    {
        $transaction = EncryptionTransaction::where('transaction_id', $transactionId)->first();
        
        if ($transaction) {
            $existingMetadata = $transaction->metadata ?? [];
            $updatedMetadata = array_merge($existingMetadata, $metadata);
            
            $transaction->update([
                'status' => $status,
                'metadata' => $updatedMetadata,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Cache transaction for performance
     */
    protected function cacheTransaction(EncryptionTransaction $transaction): void
    {
        $cacheKey = "e2ee_transaction_{$transaction->transaction_id}";
        Cache::put($cacheKey, $transaction, $this->cacheTtl);
    }

    /**
     * Get cached transaction
     */
    protected function getCachedTransaction(string $transactionId): ?EncryptionTransaction
    {
        $cacheKey = "e2ee_transaction_{$transactionId}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear transaction cache
     */
    protected function clearTransactionCache(string $transactionId): void
    {
        $cacheKey = "e2ee_transaction_{$transactionId}";
        Cache::forget($cacheKey);
    }

    /**
     * Log operation for audit purposes
     */
    protected function logOperation(string $action, array $context = []): void
    {
        if ($this->auditEnabled) {
            $this->auditService->log('e2ee_transaction', $action, $context);
        }
        
        Log::info("E2EE Transaction: {$action}", $context);
    }
} 