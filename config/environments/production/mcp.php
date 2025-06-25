<?php

return [
    'app' => [
        'env' => 'production',
        'debug' => false,
        'mcp' => [
            'enabled' => false, // MCP is disabled in production by default
            'environment' => 'production'
        ]
    ],
    
    'database' => [
        'production' => [
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'production-db'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'mcp_production'),
            'username' => env('DB_USERNAME', 'mcp_production_user'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ],
        ]
    ],
    
    'logging' => [
        'channel' => 'production',
        'production_enabled' => true,
        'channels' => [
            'production' => [
                'driver' => 'stack',
                'channels' => ['daily', 'slack', 'papertrail'],
                'ignore_exceptions' => false,
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/production.log'),
                'level' => 'error',
                'days' => 30,
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'MCP Production Logger',
                'emoji' => ':warning:',
                'level' => 'critical',
            ],
            'papertrail' => [
                'driver' => 'syslog',
                'level' => 'error',
                'app_name' => 'mcp-production',
                'facility' => LOG_USER,
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
            ],
            'pagerduty' => [
                'enabled' => true,
                'service_key' => env('PAGERDUTY_SERVICE_KEY'),
            ]
        ],
        'metrics' => [
            'enabled' => true,
            'driver' => 'prometheus',
            'namespace' => 'mcp_production',
            'labels' => [
                'environment' => 'production',
                'application' => 'mcp'
            ]
        ]
    ],
    
    'backup' => [
        'enabled' => true,
        'schedule' => '0 0 * * *', // Daily at midnight
        'retention_policy' => [
            'daily' => 14,
            'weekly' => 8,
            'monthly' => 12,
            'yearly' => 3
        ],
        'storage' => [
            'driver' => 's3',
            'bucket' => env('BACKUP_S3_BUCKET'),
            'path' => 'production/backups',
            'encryption' => 'AES256'
        ],
        'verification' => [
            'enabled' => true,
            'schedule' => '0 6 * * *', // Daily at 6 AM
            'notify_on_failure' => true
        ]
    ],
    
    'security' => [
        'production_checks_enabled' => true,
        'audit_logging_enabled' => true,
        'rate_limiting_enabled' => true,
        'ssl_required' => true,
        'headers_enabled' => true,
        'headers' => [
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self';",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-Download-Options' => 'noopen',
            'X-DNS-Prefetch-Control' => 'off'
        ],
        'session' => [
            'secure' => true,
            'httponly' => true,
            'samesite' => 'strict',
            'lifetime' => 120, // minutes
            'expire_on_close' => true
        ],
        'cookies' => [
            'secure' => true,
            'httponly' => true,
            'samesite' => 'strict'
        ]
    ],
    
    'cache' => [
        'driver' => 'redis',
        'connection' => 'production',
        'prefix' => 'mcp_production:',
        'ttl' => 3600, // 1 hour
        'tags' => true
    ],
    
    'queue' => [
        'driver' => 'redis',
        'connection' => 'production',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => true
    ]
]; 