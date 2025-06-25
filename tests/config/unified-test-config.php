<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Unified Test Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all test-related configuration settings for the
    | application. It consolidates testing configurations from multiple
    | sources and provides a single source of truth for test settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Test Environment Settings
    |--------------------------------------------------------------------------
    */
    'environment' => [
        'name' => env('TEST_ENV', 'testing'),
        'debug' => env('TEST_DEBUG', true),
        'cache' => env('TEST_CACHE_DRIVER', 'array'),
        'session' => env('TEST_SESSION_DRIVER', 'array'),
        'queue' => env('TEST_QUEUE_DRIVER', 'sync'),
        'mail' => env('TEST_MAIL_DRIVER', 'array'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'default' => env('TEST_DB_CONNECTION', 'sqlite'),
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('TEST_DB_HOST', '127.0.0.1'),
                'port' => env('TEST_DB_PORT', '3306'),
                'database' => env('TEST_DB_DATABASE', 'laravel_test'),
                'username' => env('TEST_DB_USERNAME', 'root'),
                'password' => env('TEST_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('TEST_DB_HOST', '127.0.0.1'),
                'port' => env('TEST_DB_PORT', '5432'),
                'database' => env('TEST_DB_DATABASE', 'laravel_test'),
                'username' => env('TEST_DB_USERNAME', 'postgres'),
                'password' => env('TEST_DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Modules Configuration
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'enabled' => env('TEST_MODULES_ENABLED', true),
        'auto_load' => env('TEST_MODULES_AUTO_LOAD', true),
        'test_modules' => [
            'e2ee' => [
                'enabled' => env('TEST_E2EE_ENABLED', true),
                'encryption_key' => env('TEST_E2EE_KEY', 'test-key-32-chars-long'),
                'audit_enabled' => env('TEST_E2EE_AUDIT', false),
            ],
            'soc2' => [
                'enabled' => env('TEST_SOC2_ENABLED', true),
                'compliance_level' => env('TEST_SOC2_LEVEL', 'type1'),
                'audit_enabled' => env('TEST_SOC2_AUDIT', false),
            ],
            'mcp' => [
                'enabled' => env('TEST_MCP_ENABLED', true),
                'agent_enabled' => env('TEST_MCP_AGENT', false),
                'api_enabled' => env('TEST_MCP_API', true),
            ],
            'web3' => [
                'enabled' => env('TEST_WEB3_ENABLED', true),
                'network' => env('TEST_WEB3_NETWORK', 'testnet'),
                'contract_enabled' => env('TEST_WEB3_CONTRACT', false),
            ],
            'auth' => [
                'enabled' => env('TEST_AUTH_ENABLED', true),
                'rbac_enabled' => env('TEST_AUTH_RBAC', true),
                'audit_enabled' => env('TEST_AUTH_AUDIT', false),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Testing Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'enabled' => env('TEST_PERFORMANCE_ENABLED', true),
        'thresholds' => [
            'response_time' => env('TEST_PERF_RESPONSE_TIME', 200), // milliseconds
            'memory_usage' => env('TEST_PERF_MEMORY_USAGE', 512), // MB
            'database_queries' => env('TEST_PERF_DB_QUERIES', 10),
            'cache_hits' => env('TEST_PERF_CACHE_HITS', 0.8), // 80%
            'cpu_usage' => env('TEST_PERF_CPU_USAGE', 80), // percentage
        ],
        'load_testing' => [
            'concurrent_requests' => env('TEST_LOAD_CONCURRENT', 10),
            'total_requests' => env('TEST_LOAD_TOTAL', 100),
            'timeout' => env('TEST_LOAD_TIMEOUT', 30), // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Testing Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enabled' => env('TEST_SECURITY_ENABLED', true),
        'requirements' => [
            'authentication' => env('TEST_SEC_AUTH', true),
            'authorization' => env('TEST_SEC_AUTHZ', true),
            'encryption' => env('TEST_SEC_ENCRYPTION', true),
            'input_validation' => env('TEST_SEC_VALIDATION', true),
            'csrf_protection' => env('TEST_SEC_CSRF', true),
            'rate_limiting' => env('TEST_SEC_RATE_LIMIT', true),
        ],
        'test_data' => [
            'sql_injection' => [
                "' OR '1'='1",
                "'; DROP TABLE users; --",
                "' UNION SELECT * FROM users --",
            ],
            'xss_attacks' => [
                "<script>alert('XSS')</script>",
                "javascript:alert('XSS')",
                "<img src=x onerror=alert('XSS')>",
            ],
            'file_uploads' => [
                'malicious.php',
                'malicious.exe',
                'malicious.sh',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Coverage Configuration
    |--------------------------------------------------------------------------
    */
    'coverage' => [
        'enabled' => env('TEST_COVERAGE_ENABLED', true),
        'minimum' => env('TEST_COVERAGE_MINIMUM', 80), // percentage
        'reports' => [
            'html' => env('TEST_COVERAGE_HTML', true),
            'xml' => env('TEST_COVERAGE_XML', true),
            'clover' => env('TEST_COVERAGE_CLOVER', true),
        ],
        'exclude' => [
            'vendor/*',
            'node_modules/*',
            'tests/*',
            'bootstrap/*',
            'storage/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Reporting Configuration
    |--------------------------------------------------------------------------
    */
    'reporting' => [
        'enabled' => env('TEST_REPORTING_ENABLED', true),
        'output' => [
            'console' => env('TEST_REPORT_CONSOLE', true),
            'file' => env('TEST_REPORT_FILE', true),
            'email' => env('TEST_REPORT_EMAIL', false),
        ],
        'formats' => [
            'json' => env('TEST_REPORT_JSON', true),
            'xml' => env('TEST_REPORT_XML', true),
            'html' => env('TEST_REPORT_HTML', true),
        ],
        'notifications' => [
            'slack' => env('TEST_NOTIFY_SLACK', false),
            'email' => env('TEST_NOTIFY_EMAIL', false),
            'webhook' => env('TEST_NOTIFY_WEBHOOK', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Data Configuration
    |--------------------------------------------------------------------------
    */
    'data' => [
        'factories' => [
            'enabled' => env('TEST_FACTORIES_ENABLED', true),
            'path' => env('TEST_FACTORIES_PATH', 'database/factories'),
        ],
        'seeders' => [
            'enabled' => env('TEST_SEEDERS_ENABLED', true),
            'path' => env('TEST_SEEDERS_PATH', 'database/seeders'),
        ],
        'fixtures' => [
            'enabled' => env('TEST_FIXTURES_ENABLED', true),
            'path' => env('TEST_FIXTURES_PATH', 'tests/fixtures'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Parallelization Configuration
    |--------------------------------------------------------------------------
    */
    'parallel' => [
        'enabled' => env('TEST_PARALLEL_ENABLED', false),
        'processes' => env('TEST_PARALLEL_PROCESSES', 4),
        'memory_limit' => env('TEST_PARALLEL_MEMORY', '512M'),
        'timeout' => env('TEST_PARALLEL_TIMEOUT', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Cleanup Configuration
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'enabled' => env('TEST_CLEANUP_ENABLED', true),
        'actions' => [
            'clear_cache' => env('TEST_CLEANUP_CACHE', true),
            'clear_sessions' => env('TEST_CLEANUP_SESSIONS', true),
            'reset_database' => env('TEST_CLEANUP_DATABASE', true),
            'clear_logs' => env('TEST_CLEANUP_LOGS', true),
        ],
        'after_each' => env('TEST_CLEANUP_AFTER_EACH', true),
        'after_suite' => env('TEST_CLEANUP_AFTER_SUITE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('TEST_MONITORING_ENABLED', true),
        'metrics' => [
            'response_time' => env('TEST_MONITOR_RESPONSE_TIME', true),
            'memory_usage' => env('TEST_MONITOR_MEMORY', true),
            'database_queries' => env('TEST_MONITOR_DB_QUERIES', true),
            'error_rate' => env('TEST_MONITOR_ERROR_RATE', true),
        ],
        'alerts' => [
            'enabled' => env('TEST_ALERTS_ENABLED', false),
            'thresholds' => [
                'error_rate' => env('TEST_ALERT_ERROR_RATE', 5), // percentage
                'response_time' => env('TEST_ALERT_RESPONSE_TIME', 500), // milliseconds
            ],
        ],
    ],
]; 