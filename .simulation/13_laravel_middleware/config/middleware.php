<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be applied to every request.
    |
    */
    'global' => [
        \App\Http\Middleware\RequestLoggingMiddleware::class,
        \App\Http\Middleware\ResponseTimeTrackingMiddleware::class,
        \App\Http\Middleware\SecurityHeadersMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be applied to specific routes.
    |
    */
    'route' => [
        'auth' => \App\Http\Middleware\AuthenticationMiddleware::class,
        'authorize' => \App\Http\Middleware\AuthorizationMiddleware::class,
        'cache' => \App\Http\Middleware\CachingMiddleware::class,
        'compress' => \App\Http\Middleware\CompressionMiddleware::class,
        'csrf' => \App\Http\Middleware\CsrfProtectionMiddleware::class,
        'sanitize' => \App\Http\Middleware\InputSanitizationMiddleware::class,
        'rate_limit' => \App\Http\Middleware\RateLimitingMiddleware::class,
        'sql_injection' => \App\Http\Middleware\SqlInjectionProtectionMiddleware::class,
        'xss' => \App\Http\Middleware\XssProtectionMiddleware::class,
        'role' => \App\Http\Middleware\RoleBasedAccessControlMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | Groups of middleware that can be applied together.
    |
    */
    'groups' => [
        'web' => [
            \App\Http\Middleware\CsrfProtectionMiddleware::class,
            \App\Http\Middleware\InputSanitizationMiddleware::class,
            \App\Http\Middleware\XssProtectionMiddleware::class,
            \App\Http\Middleware\SqlInjectionProtectionMiddleware::class,
        ],
        'api' => [
            \App\Http\Middleware\RateLimitingMiddleware::class,
            \App\Http\Middleware\AuthenticationMiddleware::class,
            \App\Http\Middleware\AuthorizationMiddleware::class,
            \App\Http\Middleware\RoleBasedAccessControlMiddleware::class,
        ],
        'performance' => [
            \App\Http\Middleware\CachingMiddleware::class,
            \App\Http\Middleware\CompressionMiddleware::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Priority
    |--------------------------------------------------------------------------
    |
    | The priority of middleware execution.
    |
    */
    'priority' => [
        \App\Http\Middleware\RequestLoggingMiddleware::class,
        \App\Http\Middleware\RateLimitingMiddleware::class,
        \App\Http\Middleware\CsrfProtectionMiddleware::class,
        \App\Http\Middleware\AuthenticationMiddleware::class,
        \App\Http\Middleware\AuthorizationMiddleware::class,
        \App\Http\Middleware\RoleBasedAccessControlMiddleware::class,
        \App\Http\Middleware\InputSanitizationMiddleware::class,
        \App\Http\Middleware\XssProtectionMiddleware::class,
        \App\Http\Middleware\SqlInjectionProtectionMiddleware::class,
        \App\Http\Middleware\SecurityHeadersMiddleware::class,
        \App\Http\Middleware\CachingMiddleware::class,
        \App\Http\Middleware\CompressionMiddleware::class,
        \App\Http\Middleware\ResponseTimeTrackingMiddleware::class,
    ],
]; 