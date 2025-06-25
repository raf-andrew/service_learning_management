<?php

return [
    'app' => [
        'env' => 'test',
        'debug' => true,
        'mcp' => [
            'enabled' => true,
            'environment' => 'test'
        ]
    ],
    
    'database' => [
        'test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]
    ],
    
    'logging' => [
        'channel' => 'test',
        'test_enabled' => true,
        'channels' => [
            'test' => [
                'driver' => 'daily',
                'path' => storage_path('logs/test.log'),
                'level' => 'debug',
                'days' => 7,
            ],
        ]
    ],
    
    'security' => [
        'test_checks_enabled' => true,
        'audit_logging_enabled' => true,
        'rate_limiting_enabled' => false,
        'ssl_required' => false,
    ],
];
