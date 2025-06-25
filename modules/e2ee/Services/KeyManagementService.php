<?php

namespace App\Modules\E2ee\Services;

use App\Modules\E2ee\Exceptions\E2eeException;
use App\Modules\E2ee\Models\EncryptionKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Modules\E2ee\Exceptions\KeyManagementException;
use App\Modules\Shared\Services\Core\AuditService;
use App\Modules\E2ee\Traits\E2eeConfigTrait;

class KeyManagementService extends BaseE2eeService
{
    use E2eeConfigTrait;

    /**
     * @var int
     */
    protected int $keyLength;

    /**
     * @var int
     */
    protected int $rotationDays;

    /**
     * @var bool
     */
    protected bool $auditEnabled;

    public function __construct(AuditService $auditService)
    {
        parent::__construct($auditService);
        $this->keyLength = $this->getKeySize();
        $this->rotationDays = $this->getKeyRotationDays();
        $this->auditEnabled = $this->isAuditEnabled();
    }

    /**
     * Default key rotation interval (days)
     */
    private const DEFAULT_ROTATION_INTERVAL = 90;

    /**
     * Default backup retention (days)
     */
    private const DEFAULT_BACKUP_RETENTION = 365;

    /**
     * Generate user keys for a user
     */
    public function generateUserKeys(int $userId, ?string $password = null): array
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $password) {
                $this->validateUserId($userId);

                // Generate master key
                $masterKey = $this->generateMasterKey();

                // Generate user-specific key
                $userKey = random_bytes($this->keyLength);
                $userKeyRecord = EncryptionKey::create([
                    'user_id' => $userId,
                    'key' => $userKey,
                    'algorithm' => $this->getEncryptionAlgorithm(),
                    'key_length' => $this->keyLength,
                    'status' => 'active',
                    'created_at' => now(),
                    'expires_at' => now()->addDays($this->rotationDays),
                    'metadata' => [],
                ]);

                // If password provided, derive key from password
                if ($password) {
                    $derivedKey = $this->deriveKeyFromPassword($password);
                    $encryptedUserKey = $this->encryptUserKey($userKey, $derivedKey['key']);
                    $result = [
                        'user_id' => $userId,
                        'master_key' => base64_encode($masterKey),
                        'user_key' => base64_encode($userKey),
                        'encrypted_user_key' => $encryptedUserKey,
                        'key_derivation' => $derivedKey,
                        'key_id' => $userKeyRecord->id,
                    ];
                } else {
                    $result = [
                        'user_id' => $userId,
                        'master_key' => base64_encode($masterKey),
                        'user_key' => base64_encode($userKey),
                        'key_id' => $userKeyRecord->id,
                    ];
                }

                // Cache the keys
                $this->cacheUserKeys($userId, $masterKey, $userKey);

                return $result;
            },
            'generate_user_keys',
            ['user_id' => $userId]
        );
    }

    /**
     * Get user keys
     */
    public function getUserKeys(int $userId, ?string $password = null): array
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $password) {
                $this->validateUserId($userId);

                // Try to get from cache first
                $cachedKeys = $this->getCachedUserKeys($userId);
                if ($cachedKeys) {
                    return $cachedKeys;
                }

                // Get from database
                $keyRecord = EncryptionKey::where('user_id', $userId)
                    ->where('status', 'active')
                    ->first();

                if (!$keyRecord) {
                    throw new E2eeException('No active keys found for user', 'NO_KEYS_FOUND');
                }

                // Check if keys are expired
                if ($keyRecord->expires_at && $keyRecord->expires_at->isPast()) {
                    throw new E2eeException('User keys have expired', 'KEYS_EXPIRED');
                }

                // Decrypt user key
                $masterKey = $this->getMasterKey($keyRecord, $password);
                $userKey = $this->decryptUserKey($keyRecord->user_key_encrypted, $masterKey);

                // Cache the keys
                $this->cacheUserKeys($userId, $masterKey, $userKey);

                return [
                    'key_id' => $keyRecord->id,
                    'master_key' => base64_encode($masterKey),
                    'user_key' => base64_encode($userKey),
                    'expires_at' => $keyRecord->expires_at,
                ];
            },
            'get_user_keys',
            ['user_id' => $userId]
        );
    }

    /**
     * Rotate user keys
     */
    public function rotateUserKeys(int $userId, ?string $password = null): array
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $password) {
                $this->validateUserId($userId);

                // Get current keys
                $currentKeys = $this->getUserKeys($userId, $password);

                // Generate new keys
                $newKeys = $this->generateUserKeys($userId, $password);

                // Mark old keys as rotated
                EncryptionKey::where('user_id', $userId)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'rotated',
                        'rotated_at' => now(),
                    ]);

                // Clear cache
                $this->clearUserKeyCache($userId);

                return [
                    'key_id' => $newKeys['key_id'] ?? null,
                    'master_key' => $newKeys['master_key'] ?? null,
                    'user_key' => $newKeys['user_key'] ?? null,
                ];
            },
            'rotate_user_keys',
            ['user_id' => $userId]
        );
    }

    /**
     * Backup user keys
     */
    public function backupUserKeys(int $userId, string $backupPassword): array
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $backupPassword) {
                $this->validateUserId($userId);

                $userKeys = $this->getUserKeys($userId);
                
                // Create backup data
                $backupData = [
                    'user_id' => $userId,
                    'master_key' => $userKeys['master_key'],
                    'user_key' => $userKeys['user_key'],
                    'backup_date' => now()->toISOString(),
                    'version' => '1.0',
                ];

                // Encrypt backup with backup password
                $derivedKey = $this->deriveKeyFromPassword($backupPassword);
                $encryptedBackup = $this->encryptBackup(json_encode($backupData), $derivedKey['key']);

                // Store backup
                $backupPath = "e2ee/backups/user_{$userId}_" . now()->format('Y-m-d_H-i-s') . ".backup";
                Storage::put($backupPath, $encryptedBackup);

                return [
                    'backup_path' => $backupPath,
                    'backup_date' => now(),
                    'encrypted' => true,
                ];
            },
            'backup_user_keys',
            ['user_id' => $userId]
        );
    }

    /**
     * Restore user keys from backup
     */
    public function restoreUserKeys(string $backupPath, string $backupPassword): array
    {
        return $this->executeWithErrorHandling(
            function () use ($backupPath, $backupPassword) {
                // Read backup file
                if (!Storage::exists($backupPath)) {
                    throw new E2eeException('Backup file not found', 'BACKUP_NOT_FOUND');
                }

                $encryptedBackup = Storage::get($backupPath);

                // Decrypt backup
                $derivedKey = $this->deriveKeyFromPassword($backupPassword);
                $backupData = $this->decryptBackup($encryptedBackup, $derivedKey['key']);

                $backup = json_decode($backupData, true);
                if (!$backup) {
                    throw new E2eeException('Invalid backup data', 'INVALID_BACKUP');
                }

                // Validate backup
                $this->validateBackup($backup);

                // Restore keys
                $userId = $backup['user_id'];
                
                // Deactivate current keys
                EncryptionKey::where('user_id', $userId)
                    ->where('status', 'active')
                    ->update(['status' => 'restored']);

                // Create new key record
                $keyRecord = EncryptionKey::create([
                    'user_id' => $userId,
                    'master_key_hash' => hash('sha256', base64_decode($backup['master_key'])),
                    'user_key_encrypted' => $this->encryptUserKey(
                        base64_decode($backup['user_key']),
                        base64_decode($backup['master_key'])
                    ),
                    'status' => 'active',
                    'created_at' => now(),
                    'expires_at' => now()->addDays(self::DEFAULT_ROTATION_INTERVAL),
                    'restored_from_backup' => true,
                    'backup_path' => $backupPath,
                ]);

                // Clear cache
                $this->clearUserKeyCache($userId);

                return [
                    'key_id' => $keyRecord->id,
                    'user_id' => $userId,
                    'restored_at' => now(),
                ];
            },
            'restore_user_keys',
            ['backup_path' => $backupPath]
        );
    }

    /**
     * Validate user keys
     */
    public function validateUserKeys(int $userId): bool
    {
        return $this->executeWithErrorHandling(
            function () use ($userId) {
                $this->validateUserId($userId);

                $userKeys = $this->getUserKeys($userId);
                
                // Test encryption/decryption cycle
                $testData = Str::random(100);
                $encrypted = $this->encryptTestData($testData, $userKeys['user_key']);
                $decrypted = $this->decryptTestData($encrypted, $userKeys['user_key']);
                
                return $testData === $decrypted;
            },
            'validate_user_keys',
            ['user_id' => $userId]
        );
    }

    /**
     * Clean up expired keys
     */
    public function cleanupExpiredKeys(): int
    {
        return $this->executeWithErrorHandling(
            function () {
                $expiredKeys = EncryptionKey::where('expires_at', '<', now())
                    ->where('status', 'active')
                    ->get();

                $count = 0;
                foreach ($expiredKeys as $key) {
                    $key->update(['status' => 'expired']);
                    $this->clearUserKeyCache($key->user_id);
                    $count++;
                }

                return $count;
            },
            'cleanup_expired_keys'
        );
    }

    /**
     * Generate master key
     */
    private function generateMasterKey(): string
    {
        return random_bytes(32);
    }

    /**
     * Derive key from password
     */
    private function deriveKeyFromPassword(string $password): array
    {
        $salt = random_bytes(32);
        $iterations = $this->getKeyDerivationIterations();
        
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
        
        return [
            'key' => base64_encode($key),
            'salt' => base64_encode($salt),
            'iterations' => $iterations,
        ];
    }

    /**
     * Encrypt user key
     */
    private function encryptUserKey(string $userKey, string $masterKey): string
    {
        $encryptionService = app('e2ee.encryption.service');
        return $encryptionService->encrypt($userKey, base64_encode($masterKey));
    }

    /**
     * Decrypt user key
     */
    private function decryptUserKey(string $encryptedUserKey, string $masterKey): string
    {
        $encryptionService = app('e2ee.encryption.service');
        return $encryptionService->decrypt($encryptedUserKey, base64_encode($masterKey));
    }

    /**
     * Get master key
     */
    private function getMasterKey($keyRecord, ?string $password = null): string
    {
        // For now, we'll use a simplified approach
        // In a real implementation, you'd need to handle key derivation properly
        return random_bytes(32); // This is a placeholder
    }

    /**
     * Cache user keys
     */
    private function cacheUserKeys(int $userId, string $masterKey, string $userKey): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        $cacheKey = "e2ee:user_keys:{$userId}";
        $cacheData = [
            'master_key' => base64_encode($masterKey),
            'user_key' => base64_encode($userKey),
            'cached_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $cacheData, $this->getCacheTtl());
    }

    /**
     * Get cached user keys
     */
    private function getCachedUserKeys(int $userId): ?array
    {
        if (!$this->isCachingEnabled()) {
            return null;
        }

        $cacheKey = "e2ee:user_keys:{$userId}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear user key cache
     */
    private function clearUserKeyCache(int $userId): void
    {
        $cacheKey = "e2ee:user_keys:{$userId}";
        Cache::forget($cacheKey);
    }

    /**
     * Encrypt backup data
     */
    private function encryptBackup(string $data, string $key): string
    {
        $encryptionService = app('e2ee.encryption.service');
        return $encryptionService->encrypt($data, $key);
    }

    /**
     * Decrypt backup data
     */
    private function decryptBackup(string $encryptedData, string $key): string
    {
        $encryptionService = app('e2ee.encryption.service');
        return $encryptionService->decrypt($encryptedData, $key);
    }

    /**
     * Validate backup data
     */
    private function validateBackup(array $backup): void
    {
        $requiredFields = ['user_id', 'master_key', 'user_key', 'backup_date', 'version'];
        
        foreach ($requiredFields as $field) {
            if (!isset($backup[$field])) {
                throw new E2eeException("Missing required field: {$field}", 'INVALID_BACKUP');
            }
        }

        if ($backup['version'] !== '1.0') {
            throw new E2eeException('Unsupported backup version', 'UNSUPPORTED_VERSION');
        }
    }

    /**
     * Encrypt test data
     */
    private function encryptTestData(string $data, string $key): string
    {
        $encryptionService = app('e2ee.encryption.service');
        return $encryptionService->encrypt($data, $key);
    }

    /**
     * Decrypt test data
     */
    private function decryptTestData(string $encryptedData, string $key): string
    {
        $encryptionService = app('e2ee.encryption.service');
        return $encryptionService->decrypt($encryptedData, $key);
    }

    /**
     * Get or generate user's encryption key
     */
    public function getUserKey(int $userId): EncryptionKey
    {
        return $this->executeWithErrorHandling(
            function () use ($userId) {
                $this->validateUserId($userId);

                $cacheKey = "e2ee_user_key_{$userId}";
                return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($userId) {
                    $key = EncryptionKey::where('user_id', $userId)
                        ->where('status', 'active')
                        ->first();
                    if (!$key) {
                        // Generate a new key if none exists
                        $key = EncryptionKey::create([
                            'user_id' => $userId,
                            'key' => random_bytes($this->keyLength),
                            'algorithm' => $this->getEncryptionAlgorithm(),
                            'key_length' => $this->keyLength,
                            'status' => 'active',
                            'created_at' => now(),
                            'expires_at' => now()->addDays($this->rotationDays),
                            'metadata' => [],
                        ]);
                    } elseif ($this->shouldRotateKey($key)) {
                        $key = $this->rotateUserKey($userId, $key);
                    }
                    return $key;
                });
            },
            'get_user_key',
            ['user_id' => $userId]
        );
    }

    /**
     * Rotate user's encryption key
     */
    public function rotateUserKey(int $userId, EncryptionKey $oldKey): EncryptionKey
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $oldKey) {
                $this->validateUserId($userId);

                // Generate new key
                $newKey = $this->generateUserKey($userId);
                
                // Mark old key as rotated
                $oldKey->update([
                    'status' => 'rotated',
                    'rotated_at' => now(),
                    'metadata' => array_merge($oldKey->metadata ?? [], [
                        'rotated_to_key_id' => $newKey->id,
                        'rotation_reason' => 'automatic'
                    ])
                ]);

                // Clear cache
                Cache::forget("e2ee_user_key_{$userId}");

                return $newKey;
            },
            'rotate_user_key',
            ['user_id' => $userId, 'old_key_id' => $oldKey->id]
        );
    }

    /**
     * Force key rotation for a user
     */
    public function forceKeyRotation(int $userId, string $reason = 'manual'): EncryptionKey
    {
        return $this->executeWithErrorHandling(
            function () use ($userId, $reason) {
                $this->validateUserId($userId);

                // Get current active key
                $currentKey = EncryptionKey::where('user_id', $userId)
                    ->where('status', 'active')
                    ->first();

                if (!$currentKey) {
                    return $this->generateUserKey($userId);
                }

                // Mark current key as rotated
                $currentKey->update([
                    'status' => 'rotated',
                    'rotated_at' => now(),
                    'metadata' => array_merge($currentKey->metadata ?? [], [
                        'rotation_reason' => $reason,
                        'forced_rotation' => true
                    ])
                ]);

                // Generate new key
                $newKey = $this->generateUserKey($userId);
                
                // Update new key metadata
                $newKey->update([
                    'metadata' => array_merge($newKey->metadata ?? [], [
                        'rotated_from_key_id' => $currentKey->id,
                        'rotation_reason' => $reason,
                        'forced_rotation' => true
                    ])
                ]);

                // Clear cache
                Cache::forget("e2ee_user_key_{$userId}");

                return $newKey;
            },
            'force_key_rotation',
            ['user_id' => $userId, 'reason' => $reason]
        );
    }

    /**
     * Revoke user's encryption key
     */
    public function revokeUserKey(int $userId, string $reason = 'security'): void
    {
        $this->executeWithErrorHandling(
            function () use ($userId, $reason) {
                $this->validateUserId($userId);

                $keys = EncryptionKey::where('user_id', $userId)
                    ->whereIn('status', ['active', 'rotated'])
                    ->get();

                foreach ($keys as $key) {
                    $key->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'metadata' => array_merge($key->metadata ?? [], [
                            'revocation_reason' => $reason,
                            'revoked_by' => 'system'
                        ])
                    ]);
                }

                // Clear cache
                Cache::forget("e2ee_user_key_{$userId}");
            },
            'revoke_user_key',
            ['user_id' => $userId, 'reason' => $reason]
        );
    }

    /**
     * Get key statistics
     */
    public function getKeyStatistics(): array
    {
        return $this->executeWithErrorHandling(
            function () {
                $totalKeys = EncryptionKey::count();
                $activeKeys = EncryptionKey::where('status', 'active')->count();
                $rotatedKeys = EncryptionKey::where('status', 'rotated')->count();
                $revokedKeys = EncryptionKey::where('status', 'revoked')->count();
                
                $expiringKeys = EncryptionKey::where('status', 'active')
                    ->where('expires_at', '<=', now()->addDays(7))
                    ->count();

                return [
                    'total_keys' => $totalKeys,
                    'active_keys' => $activeKeys,
                    'rotated_keys' => $rotatedKeys,
                    'revoked_keys' => $revokedKeys,
                    'expiring_keys' => $expiringKeys,
                    'key_length' => $this->keyLength,
                    'rotation_days' => $this->rotationDays,
                ];
            },
            'get_key_statistics'
        );
    }

    /**
     * Check if key should be rotated
     */
    protected function shouldRotateKey(EncryptionKey $key): bool
    {
        return $key->expires_at && $key->expires_at->isPast();
    }

    /**
     * Validate key parameters
     */
    public function validateKeyParameters(int $keyLength = null): bool
    {
        return $this->executeWithErrorHandling(
            function () use ($keyLength) {
                $keyLength = $keyLength ?? $this->keyLength;

                // Check key length
                if ($keyLength < 16 || $keyLength > 64) {
                    return false;
                }

                return true;
            },
            'validate_key_parameters'
        );
    }

    /**
     * Generate a new user key
     */
    private function generateUserKey(int $userId): EncryptionKey
    {
        return EncryptionKey::create([
            'user_id' => $userId,
            'key' => random_bytes($this->keyLength),
            'algorithm' => $this->getEncryptionAlgorithm(),
            'key_length' => $this->keyLength,
            'status' => 'active',
            'created_at' => now(),
            'expires_at' => now()->addDays($this->rotationDays),
            'metadata' => [],
        ]);
    }

    /**
     * Get service statistics
     */
    public function getStatistics(): array
    {
        return $this->getKeyStatistics();
    }

    /**
     * Validate service parameters
     */
    public function validateParameters(): bool
    {
        return $this->validateKeyParameters();
    }
} 