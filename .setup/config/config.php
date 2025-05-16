<?php

return [
    'app' => [
        'name' => 'Service Learning Management',
        'env' => 'development',
        'debug' => true,
        'url' => 'http://localhost',
        'timezone' => 'UTC'
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'service_learning',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => ''
    ],
    'cache' => [
        'driver' => 'file',
        'path' => 'storage/cache',
        'prefix' => 'slm_'
    ],
    'session' => [
        'driver' => 'file',
        'path' => 'storage/sessions',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'cookie' => 'slm_session',
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax'
    ],
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => null,
        'password' => null,
        'encryption' => 'tls',
        'from' => [
            'address' => 'noreply@example.com',
            'name' => 'Service Learning Management'
        ]
    ],
    'logging' => [
        'default' => 'stack',
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single', 'daily']
            ],
            'single' => [
                'driver' => 'single',
                'path' => 'storage/logs/slm.log',
                'level' => 'debug'
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => 'storage/logs/slm.log',
                'level' => 'debug',
                'days' => 14
            ]
        ]
    ],
    'services' => [
        'api' => [
            'enabled' => true,
            'port' => 8000,
            'workers' => 4,
            'timeout' => 60
        ],
        'queue' => [
            'enabled' => true,
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => null
        ],
        'cache' => [
            'enabled' => true,
            'driver' => 'redis',
            'connection' => 'default'
        ]
    ],
    'testing' => [
        'enabled' => true,
        'suites' => [
            'unit' => true,
            'integration' => true,
            'e2e' => true
        ],
        'coverage' => [
            'enabled' => true,
            'threshold' => 80
        ],
        'performance' => [
            'enabled' => true,
            'threshold' => 1000 // ms
        ]
    ]
]; 