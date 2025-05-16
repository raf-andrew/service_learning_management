<?php

return [
    'enabled' => env('CODESPACES_ENABLED', false),
    
    'services' => [
        'database' => [
            'host' => env('CODESPACES_DB_HOST', 'mysql'),
            'port' => env('CODESPACES_DB_PORT', 3306),
            'database' => env('CODESPACES_DB_DATABASE', 'service_learning'),
            'username' => env('CODESPACES_DB_USERNAME', 'root'),
            'password' => env('CODESPACES_DB_PASSWORD', 'root'),
        ],
        'redis' => [
            'host' => env('CODESPACES_REDIS_HOST', 'redis'),
            'port' => env('CODESPACES_REDIS_PORT', 6379),
            'password' => env('CODESPACES_REDIS_PASSWORD', null),
        ],
        'mail' => [
            'host' => env('CODESPACES_MAIL_HOST', 'mailhog'),
            'port' => env('CODESPACES_MAIL_PORT', 1025),
            'username' => env('CODESPACES_MAIL_USERNAME', null),
            'password' => env('CODESPACES_MAIL_PASSWORD', null),
            'encryption' => env('CODESPACES_MAIL_ENCRYPTION', null),
        ],
    ],
    
    'logging' => [
        'path' => storage_path('logs/codespaces'),
        'level' => env('CODESPACES_LOG_LEVEL', 'debug'),
    ],
    
    'testing' => [
        'report_path' => '.codespaces/testing/.test/results',
        'log_path' => '.codespaces/log',
        'complete_path' => '.codespaces/testing/.test/.complete',
        'failures_path' => '.codespaces/testing/.test/.failures',
    ],
];
