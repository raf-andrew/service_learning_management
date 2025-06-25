<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for all modules in the application.
    | Each module can have its own configuration section.
    |
    */

    'enabled' => env('MODULES_ENABLED', true),

    'discovery' => [
        'scan_paths' => [
            base_path('modules'),
        ],
        'exclude_patterns' => [
            'shared',
            'vendor',
            'node_modules',
            '.git',
            '.tmp',
        ],
        'auto_discover' => env('MODULE_AUTO_DISCOVER', true),
    ],

    'autoload' => [
        'enabled' => true,
        'cache' => env('MODULE_AUTOLOAD_CACHE', true),
        'optimize' => env('MODULE_AUTOLOAD_OPTIMIZE', true),
        'psr4' => [
            'App\\Modules\\' => base_path('modules'),
        ],
    ],

    'providers' => [
        'auto_discover' => true,
        'cache' => env('MODULE_PROVIDER_CACHE', true),
        'base_provider' => \App\Providers\BaseModuleServiceProvider::class,
        'discovery_service' => \App\Modules\Shared\ModuleDiscoveryService::class,
    ],

    'testing' => [
        'enabled' => env('MODULE_TESTING_ENABLED', true),
        'coverage' => env('MODULE_TESTING_COVERAGE', true),
        'parallel' => env('MODULE_TESTING_PARALLEL', false),
        'coverage_threshold' => env('MODULE_TESTING_COVERAGE_THRESHOLD', 80),
    ],

    'performance' => [
        'caching' => [
            'enabled' => env('MODULE_CACHING_ENABLED', true),
            'driver' => env('MODULE_CACHE_DRIVER', 'redis'),
            'ttl' => env('MODULE_CACHE_TTL', 3600),
            'prefix' => env('MODULE_CACHE_PREFIX', 'module_'),
        ],
        'optimization' => [
            'autoload_optimization' => env('MODULE_AUTOLOAD_OPTIMIZATION', true),
            'route_caching' => env('MODULE_ROUTE_CACHING', true),
            'config_caching' => env('MODULE_CONFIG_CACHING', true),
            'view_caching' => env('MODULE_VIEW_CACHING', true),
        ],
        'monitoring' => [
            'enabled' => env('MODULE_MONITORING_ENABLED', true),
            'metrics' => env('MODULE_MONITORING_METRICS', true),
            'health_checks' => env('MODULE_HEALTH_CHECKS', true),
        ],
    ],

    'security' => [
        'encryption' => [
            'default' => env('MODULE_ENCRYPTION_DEFAULT', 'AES-256-GCM'),
            'key_length' => env('MODULE_ENCRYPTION_KEY_LENGTH', 32),
            'algorithm' => env('MODULE_ENCRYPTION_ALGORITHM', 'AES-256-GCM'),
        ],
        'audit' => [
            'enabled' => env('MODULE_AUDIT_ENABLED', true),
            'log_level' => env('MODULE_AUDIT_LOG_LEVEL', 'info'),
            'retention_days' => env('MODULE_AUDIT_RETENTION_DAYS', 90),
        ],
        'validation' => [
            'strict_mode' => env('MODULE_VALIDATION_STRICT', true),
            'sanitization' => env('MODULE_SANITIZATION_ENABLED', true),
        ],
    ],

    'modules' => [
        'e2ee' => [
            'enabled' => env('E2EE_ENABLED', true),
            'encryption_algorithm' => env('E2EE_ALGORITHM', 'AES-256-GCM'),
            'key_rotation_days' => env('E2EE_KEY_ROTATION_DAYS', 30),
            'audit_enabled' => env('E2EE_AUDIT_ENABLED', true),
            'key_management' => [
                'auto_rotation' => env('E2EE_AUTO_KEY_ROTATION', true),
                'backup_enabled' => env('E2EE_KEY_BACKUP_ENABLED', true),
                'storage_driver' => env('E2EE_KEY_STORAGE_DRIVER', 'file'),
            ],
            'performance' => [
                'caching_enabled' => env('E2EE_CACHING_ENABLED', true),
                'batch_size' => env('E2EE_BATCH_SIZE', 100),
            ],
        ],

        'soc2' => [
            'enabled' => env('SOC2_ENABLED', true),
            'compliance_level' => env('SOC2_COMPLIANCE_LEVEL', 'type2'),
            'audit_enabled' => env('SOC2_AUDIT_ENABLED', true),
            'reporting_enabled' => env('SOC2_REPORTING_ENABLED', true),
            'controls' => [
                'access_control' => env('SOC2_ACCESS_CONTROL_ENABLED', true),
                'data_protection' => env('SOC2_DATA_PROTECTION_ENABLED', true),
                'incident_response' => env('SOC2_INCIDENT_RESPONSE_ENABLED', true),
            ],
            'reporting' => [
                'auto_generate' => env('SOC2_AUTO_REPORTING', true),
                'retention_period' => env('SOC2_REPORT_RETENTION_DAYS', 365),
                'export_formats' => ['pdf', 'csv', 'json'],
            ],
        ],

        'mcp' => [
            'enabled' => env('MCP_ENABLED', true),
            'agent_enabled' => env('MCP_AGENT_ENABLED', true),
            'api_enabled' => env('MCP_API_ENABLED', true),
            'performance' => [
                'max_concurrent_agents' => env('MCP_MAX_CONCURRENT_AGENTS', 10),
                'timeout_seconds' => env('MCP_TIMEOUT_SECONDS', 30),
                'memory_limit' => env('MCP_MEMORY_LIMIT', '512M'),
            ],
            'security' => [
                'agent_authentication' => env('MCP_AGENT_AUTH_ENABLED', true),
                'api_rate_limiting' => env('MCP_API_RATE_LIMITING', true),
            ],
        ],

        'web3' => [
            'enabled' => env('WEB3_ENABLED', true),
            'network' => env('WEB3_NETWORK', 'ethereum'),
            'contract_enabled' => env('WEB3_CONTRACT_ENABLED', true),
            'providers' => [
                'ethereum' => env('WEB3_ETHEREUM_PROVIDER', 'https://mainnet.infura.io/v3/'),
                'polygon' => env('WEB3_POLYGON_PROVIDER', 'https://polygon-rpc.com/'),
            ],
            'security' => [
                'private_key_encryption' => env('WEB3_PRIVATE_KEY_ENCRYPTION', true),
                'transaction_signing' => env('WEB3_TRANSACTION_SIGNING', true),
            ],
        ],

        'auth' => [
            'enabled' => env('AUTH_MODULE_ENABLED', true),
            'rbac_enabled' => env('AUTH_RBAC_ENABLED', true),
            'audit_enabled' => env('AUTH_AUDIT_ENABLED', true),
            'roles' => [
                'super_admin' => env('AUTH_SUPER_ADMIN_ROLE', 'super_admin'),
                'admin' => env('AUTH_ADMIN_ROLE', 'admin'),
                'user' => env('AUTH_USER_ROLE', 'user'),
            ],
            'permissions' => [
                'module_specific' => env('AUTH_MODULE_PERMISSIONS', true),
                'dynamic_permissions' => env('AUTH_DYNAMIC_PERMISSIONS', true),
            ],
        ],

        'api' => [
            'enabled' => env('API_MODULE_ENABLED', true),
            'versioning_enabled' => env('API_VERSIONING_ENABLED', true),
            'rate_limiting_enabled' => env('API_RATE_LIMITING_ENABLED', true),
            'versions' => [
                'current' => env('API_CURRENT_VERSION', 'v1'),
                'supported' => ['v1', 'v2'],
                'deprecated' => [],
            ],
            'rate_limiting' => [
                'requests_per_minute' => env('API_RATE_LIMIT_PER_MINUTE', 60),
                'burst_limit' => env('API_BURST_LIMIT', 100),
            ],
            'documentation' => [
                'auto_generate' => env('API_AUTO_DOCUMENTATION', true),
                'swagger_enabled' => env('API_SWAGGER_ENABLED', true),
            ],
        ],

        'shared' => [
            'enabled' => env('SHARED_MODULE_ENABLED', true),
            'utilities' => [
                'logging' => env('SHARED_LOGGING_ENABLED', true),
                'caching' => env('SHARED_CACHING_ENABLED', true),
                'validation' => env('SHARED_VALIDATION_ENABLED', true),
            ],
        ],
    ],

    'dependencies' => [
        'e2ee' => ['shared'],
        'soc2' => ['shared', 'e2ee'],
        'mcp' => ['shared'],
        'web3' => ['shared'],
        'auth' => ['shared'],
        'api' => ['shared', 'auth'],
    ],

    'logging' => [
        'enabled' => env('MODULE_LOGGING_ENABLED', true),
        'channels' => [
            'module_activity' => [
                'driver' => 'daily',
                'path' => storage_path('logs/modules/activity.log'),
                'level' => env('MODULE_LOG_LEVEL', 'info'),
                'days' => env('MODULE_LOG_RETENTION_DAYS', 30),
            ],
            'module_errors' => [
                'driver' => 'daily',
                'path' => storage_path('logs/modules/errors.log'),
                'level' => 'error',
                'days' => env('MODULE_ERROR_LOG_RETENTION_DAYS', 90),
            ],
        ],
    ],

    'maintenance' => [
        'auto_cleanup' => [
            'enabled' => env('MODULE_AUTO_CLEANUP', true),
            'schedule' => env('MODULE_CLEANUP_SCHEDULE', 'daily'),
            'retention_days' => env('MODULE_CLEANUP_RETENTION_DAYS', 30),
        ],
        'health_checks' => [
            'enabled' => env('MODULE_HEALTH_CHECKS_ENABLED', true),
            'interval' => env('MODULE_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
            'timeout' => env('MODULE_HEALTH_CHECK_TIMEOUT', 30),
        ],
    ],
]; 