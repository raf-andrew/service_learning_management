<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Docker Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for Docker integration.
    |
    */

    'default' => env('DOCKER_DRIVER', 'local'),

    'drivers' => [
        'local' => [
            'driver' => 'local',
        ],
    ],

    'networks' => [
        'default' => [
            'driver' => 'bridge',
            'subnet' => env('DOCKER_NETWORK_SUBNET', '172.20.0.0/16'),
            'gateway' => env('DOCKER_NETWORK_GATEWAY', '172.20.0.1'),
        ],
    ],

    'volumes' => [
        'default' => [
            'driver' => 'local',
            'driver_opts' => [
                'type' => 'none',
                'device' => env('DOCKER_VOLUME_PATH', storage_path('docker')),
                'o' => 'bind',
            ],
        ],
    ],

    'services' => [
        'app' => [
            'image' => env('DOCKER_APP_IMAGE', 'php:8.2-fpm'),
            'ports' => [
                env('DOCKER_APP_PORT', '9000') => '9000',
            ],
            'volumes' => [
                base_path() => '/var/www/html',
            ],
            'networks' => [
                'default',
            ],
        ],
        'nginx' => [
            'image' => env('DOCKER_NGINX_IMAGE', 'nginx:alpine'),
            'ports' => [
                env('DOCKER_NGINX_PORT', '80') => '80',
            ],
            'volumes' => [
                base_path() => '/var/www/html',
                base_path('docker/nginx') => '/etc/nginx/conf.d',
            ],
            'networks' => [
                'default',
            ],
        ],
        'mysql' => [
            'image' => env('DOCKER_MYSQL_IMAGE', 'mysql:8.0'),
            'ports' => [
                env('DOCKER_MYSQL_PORT', '3306') => '3306',
            ],
            'environment' => [
                'MYSQL_DATABASE' => env('DB_DATABASE', 'forge'),
                'MYSQL_ROOT_PASSWORD' => env('DB_PASSWORD', ''),
                'MYSQL_USER' => env('DB_USERNAME', 'forge'),
                'MYSQL_PASSWORD' => env('DB_PASSWORD', ''),
            ],
            'volumes' => [
                'mysql_data' => '/var/lib/mysql',
            ],
            'networks' => [
                'default',
            ],
        ],
        'redis' => [
            'image' => env('DOCKER_REDIS_IMAGE', 'redis:alpine'),
            'ports' => [
                env('DOCKER_REDIS_PORT', '6379') => '6379',
            ],
            'volumes' => [
                'redis_data' => '/data',
            ],
            'networks' => [
                'default',
            ],
        ],
    ],

    'compose' => [
        'version' => '3.8',
        'services' => [],
        'networks' => [],
        'volumes' => [],
    ],
]; 