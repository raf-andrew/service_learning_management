<?php

return [
    'mcp' => [
        'url' => getenv('MCP_SERVICE_URL') ?: 'https://codespaces.service-learning.edu',
        'api_key' => getenv('MCP_API_KEY'),
        'environment' => getenv('MCP_ENVIRONMENT') ?: 'production',
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 2,
    ],
    'database' => [
        'connection' => 'mysql',
        'host' => getenv('CODESPACES_DB_HOST'),
        'port' => 3306,
        'database' => 'service_learning',
        'username' => getenv('CODESPACES_DB_USERNAME'),
        'password' => getenv('CODESPACES_DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
    'redis' => [
        'host' => getenv('CODESPACES_REDIS_HOST'),
        'password' => getenv('CODESPACES_REDIS_PASSWORD'),
        'port' => 6379,
        'database' => 0,
        'timeout' => 5,
    ],
    'mail' => [
        'driver' => 'smtp',
        'host' => getenv('CODESPACES_MAIL_HOST'),
        'port' => 587,
        'username' => getenv('CODESPACES_MAIL_USERNAME'),
        'password' => getenv('CODESPACES_MAIL_PASSWORD'),
        'encryption' => 'tls',
        'from' => [
            'address' => 'test@service-learning.edu',
            'name' => 'Service Learning Test'
        ],
    ],
    'logging' => [
        'test_log_dir' => storage_path('logs/tests'),
        'error_log_dir' => base_path('.errors'),
        'failure_log_dir' => base_path('.failures'),
        'coverage_log_dir' => base_path('.coverage'),
    ],
    'self_healing' => [
        'enabled' => true,
        'max_retries' => 3,
        'retry_delay' => 2,
        'cleanup_old_logs' => true,
        'log_retention_days' => 7,
    ],
]; 