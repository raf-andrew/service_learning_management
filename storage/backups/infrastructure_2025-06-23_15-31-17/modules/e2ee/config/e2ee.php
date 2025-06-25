<?php

return [
    /*
    |--------------------------------------------------------------------------
    | E2EE Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the End-to-End Encryption
    | system used throughout the application.
    |
    */

    'enabled' => env('E2EE_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'algorithm' => env('E2EE_ALGORITHM', 'AES-256-GCM'),
        'key_size' => env('E2EE_KEY_SIZE', 32), // 256 bits
        'iv_size' => env('E2EE_IV_SIZE', 16), // 128 bits
        'auth_tag_size' => env('E2EE_AUTH_TAG_SIZE', 16), // 128 bits
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Key Management Settings
    |--------------------------------------------------------------------------
    */
    'keys' => [
        'derivation_iterations' => env('E2EE_KEY_ITERATIONS', 100000),
        'rotation_days' => env('E2EE_KEY_ROTATION_DAYS', 90),
        'backup_enabled' => env('E2EE_KEY_BACKUP_ENABLED', true),
        'backup_retention_days' => env('E2EE_KEY_BACKUP_RETENTION_DAYS', 365),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */
    'transactions' => [
        'timeout_minutes' => env('E2EE_TRANSACTION_TIMEOUT', 30),
        'cleanup_interval_hours' => env('E2EE_CLEANUP_INTERVAL', 24),
        'max_concurrent' => env('E2EE_MAX_CONCURRENT_TRANSACTIONS', 10),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('E2EE_AUDIT_ENABLED', true),
        'retention_days' => env('E2EE_AUDIT_RETENTION_DAYS', 2555), // 7 years
        'log_level' => env('E2EE_AUDIT_LOG_LEVEL', 'info'),
        'encrypt_logs' => env('E2EE_AUDIT_ENCRYPT_LOGS', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'keys' => env('E2EE_KEY_STORAGE', 'database'), // database, hsm, vault
        'backup' => env('E2EE_BACKUP_STORAGE', 'encrypted_file'),
        'encrypted_data' => env('E2EE_DATA_STORAGE', 'database'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_enabled' => env('E2EE_CACHE_ENABLED', true),
        'cache_ttl' => env('E2EE_CACHE_TTL', 3600),
        'batch_size' => env('E2EE_BATCH_SIZE', 100),
        'parallel_processing' => env('E2EE_PARALLEL_PROCESSING', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'require_mfa' => env('E2EE_REQUIRE_MFA', true),
        'session_timeout_minutes' => env('E2EE_SESSION_TIMEOUT', 60),
        'max_failed_attempts' => env('E2EE_MAX_FAILED_ATTEMPTS', 5),
        'lockout_duration_minutes' => env('E2EE_LOCKOUT_DURATION', 30),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        'gdpr_enabled' => env('E2EE_GDPR_ENABLED', true),
        'hipaa_enabled' => env('E2EE_HIPAA_ENABLED', false),
        'pci_enabled' => env('E2EE_PCI_ENABLED', false),
        'soc2_enabled' => env('E2EE_SOC2_ENABLED', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection' => env('E2EE_DB_CONNECTION', 'mysql'),
        'prefix' => env('E2EE_DB_PREFIX', 'e2ee_'),
        'encrypt_connection' => env('E2EE_ENCRYPT_DB_CONNECTION', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limit' => env('E2EE_API_RATE_LIMIT', 100),
        'rate_limit_window' => env('E2EE_API_RATE_LIMIT_WINDOW', 60),
        'version' => env('E2EE_API_VERSION', 'v1'),
        'prefix' => env('E2EE_API_PREFIX', 'api/e2ee'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('E2EE_MONITORING_ENABLED', true),
        'metrics_enabled' => env('E2EE_METRICS_ENABLED', true),
        'alerting_enabled' => env('E2EE_ALERTING_ENABLED', true),
        'log_performance' => env('E2EE_LOG_PERFORMANCE', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    */
    'development' => [
        'debug_enabled' => env('E2EE_DEBUG_ENABLED', false),
        'test_mode' => env('E2EE_TEST_MODE', false),
        'mock_encryption' => env('E2EE_MOCK_ENCRYPTION', false),
        'log_encryption_operations' => env('E2EE_LOG_ENCRYPTION_OPS', false),
    ],
]; 