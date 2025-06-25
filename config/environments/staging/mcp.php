<?php

return [
    'app' => [
        'env' => 'staging',
        'debug' => false,
        'mcp' => [
            'enabled' => true,
            'environment' => 'staging'
        ]
    ],
    
    'database' => [
        'staging' => [
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'staging-db'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'mcp_staging'),
            'username' => env('DB_USERNAME', 'mcp_staging_user'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]
    ],
    
    'logging' => [
        'channel' => 'staging',
        'staging_enabled' => true,
        'channels' => [
            'staging' => [
                'driver' => 'stack',
                'channels' => ['daily', 'slack'],
                'ignore_exceptions' => false,
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/staging.log'),
                'level' => 'debug',
                'days' => 14,
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'MCP Staging Logger',
                'emoji' => ':boom:',
                'level' => 'critical',
            ],
        ],
    ],
    
    'monitoring' => [
        'enabled' => true,
        'endpoints' => [
            'health' => '/health',
            'metrics' => '/metrics',
            'status' => '/status'
        ],
        'alerting_enabled' => true,
        'alert_channels' => [
            'email' => [
                'enabled' => true,
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
            ],
            'slack' => [
                'enabled' => true,
                'webhook_url' => env('ALERT_SLACK_WEBHOOK_URL'),
            ]
        ]
    ],
    
    'backup' => [
        'enabled' => true,
        'schedule' => '0 0 * * *', // Daily at midnight
        'retention_policy' => [
            'daily' => 7,
            'weekly' => 4,
            'monthly' => 3
        ],
        'storage' => [
            'driver' => 's3',
            'bucket' => env('BACKUP_S3_BUCKET'),
            'path' => 'staging/backups'
        ]
    ],
    
    'security' => [
        'staging_checks_enabled' => true,
        'audit_logging_enabled' => true,
        'rate_limiting_enabled' => true,
        'ssl_required' => true,
        'headers_enabled' => true,
        'headers' => [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
        ]
    ]
]; 