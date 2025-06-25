<?php

namespace App\Modules\E2ee\Traits;

trait E2eeConfigTrait
{
    /**
     * Get E2EE configuration value with fallback
     */
    protected function getE2eeConfig(string $key, $default = null)
    {
        return config("e2ee.{$key}", $default);
    }

    /**
     * Get encryption configuration
     */
    protected function getEncryptionConfig(string $key, $default = null)
    {
        return $this->getE2eeConfig("encryption.{$key}", $default);
    }

    /**
     * Get key management configuration
     */
    protected function getKeyConfig(string $key, $default = null)
    {
        return $this->getE2eeConfig("keys.{$key}", $default);
    }

    /**
     * Get transaction configuration
     */
    protected function getTransactionConfig(string $key, $default = null)
    {
        return $this->getE2eeConfig("transactions.{$key}", $default);
    }

    /**
     * Get performance configuration
     */
    protected function getPerformanceConfig(string $key, $default = null)
    {
        return $this->getE2eeConfig("performance.{$key}", $default);
    }

    /**
     * Get audit configuration
     */
    protected function getAuditConfig(string $key, $default = null)
    {
        return $this->getE2eeConfig("audit.{$key}", $default);
    }

    /**
     * Get security configuration
     */
    protected function getSecurityConfig(string $key, $default = null)
    {
        return $this->getE2eeConfig("security.{$key}", $default);
    }

    /**
     * Check if E2EE is enabled
     */
    protected function isE2eeEnabled(): bool
    {
        return $this->getE2eeConfig('enabled', true);
    }

    /**
     * Check if audit is enabled
     */
    protected function isAuditEnabled(): bool
    {
        return $this->getAuditConfig('enabled', true);
    }

    /**
     * Get encryption algorithm
     */
    protected function getEncryptionAlgorithm(): string
    {
        return $this->getEncryptionConfig('algorithm', 'AES-256-GCM');
    }

    /**
     * Get key size
     */
    protected function getKeySize(): int
    {
        return $this->getEncryptionConfig('key_size', 32);
    }

    /**
     * Get key rotation days
     */
    protected function getKeyRotationDays(): int
    {
        return $this->getKeyConfig('rotation_days', 90);
    }

    /**
     * Get cache TTL
     */
    protected function getCacheTtl(): int
    {
        return $this->getPerformanceConfig('cache_ttl', 3600);
    }

    /**
     * Check if caching is enabled
     */
    protected function isCachingEnabled(): bool
    {
        return $this->getPerformanceConfig('cache_enabled', true);
    }

    /**
     * Get batch size
     */
    protected function getBatchSize(): int
    {
        return $this->getPerformanceConfig('batch_size', 100);
    }

    /**
     * Get key derivation iterations
     */
    protected function getKeyDerivationIterations(): int
    {
        return $this->getKeyConfig('derivation_iterations', 100000);
    }

    /**
     * Get cleanup interval
     */
    protected function getCleanupInterval(): int
    {
        return $this->getTransactionConfig('cleanup_interval_hours', 24);
    }
} 