<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Docker Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Docker-related settings
    | used throughout the application.
    |
    */

    'enabled' => env('DOCKER_ENABLED', false),
    
    'host' => env('DOCKER_HOST', 'unix:///var/run/docker.sock'),
    
    'api_version' => env('DOCKER_API_VERSION', '1.41'),
    
    'timeout' => env('DOCKER_TIMEOUT', 30),
    
    'containers' => [
        'prefix' => env('DOCKER_CONTAINER_PREFIX', 'slm_'),
        'network' => env('DOCKER_NETWORK', 'slm_network'),
    ],
    
    'volumes' => [
        'prefix' => env('DOCKER_VOLUME_PREFIX', 'slm_'),
        'base_path' => env('DOCKER_VOLUME_BASE_PATH', storage_path('docker/volumes')),
    ],
    
    'images' => [
        'base' => env('DOCKER_BASE_IMAGE', 'php:8.2-fpm'),
        'nginx' => env('DOCKER_NGINX_IMAGE', 'nginx:alpine'),
        'mysql' => env('DOCKER_MYSQL_IMAGE', 'mysql:8.0'),
        'redis' => env('DOCKER_REDIS_IMAGE', 'redis:alpine'),
    ],
    
    'ports' => [
        'web' => env('DOCKER_WEB_PORT', 8080),
        'mysql' => env('DOCKER_MYSQL_PORT', 3306),
        'redis' => env('DOCKER_REDIS_PORT', 6379),
    ],
]; 