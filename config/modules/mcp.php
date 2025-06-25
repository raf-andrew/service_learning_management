<?php

return [
    'database' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_NAME', 'service_learning'),
        'username' => env('DB_USER', 'root'),
        'password' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    'logging' => [
        'name' => 'mcp-framework',
        'path' => __DIR__ . '/../logs/mcp.log',
        'level' => \Monolog\Logger::DEBUG,
    ],
    'testing' => [
        'database' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
        'logging' => [
            'path' => __DIR__ . '/../logs/test.log',
            'level' => \Monolog\Logger::INFO,
        ],
    ],
]; 