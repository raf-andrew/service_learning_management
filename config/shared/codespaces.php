<?php

return [
    'enabled' => env('CODESPACES_ENABLED', false),
    'github_token' => env('GITHUB_TOKEN'),
    'repository' => env('CODESPACES_REPOSITORY'),
    'health_check_interval' => env('CODESPACES_HEALTH_CHECK_INTERVAL', 300),
    'max_codespaces' => env('CODESPACES_MAX_COUNT', 10),
    'timeout' => env('CODESPACES_TIMEOUT', 300),
    'webhook_secret' => env('CODESPACES_WEBHOOK_SECRET'),
    'default_environment' => env('CODESPACES_DEFAULT_ENV', 'development'),
    'default_size' => env('CODESPACES_DEFAULT_SIZE', 'Standard-2x4'),
    'retry_attempts' => env('CODESPACES_RETRY_ATTEMPTS', 3),
    'services' => [
        'database' => [
            'enabled' => true,
            'type' => 'mysql',
            'version' => '8.0',
        ],
        'cache' => [
            'enabled' => true,
            'type' => 'redis',
            'version' => '6.0',
        ],
        'queue' => [
            'enabled' => true,
            'type' => 'redis',
        ],
    ],
    'health_checks' => [
        'enabled' => true,
        'interval' => 60,
        'timeout' => 30,
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'path' => storage_path('logs/codespaces.log'),
    ],
]; 