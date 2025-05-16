<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\RequestLoggingMiddleware::class,
        \App\Http\Middleware\ResponseTimeTrackingMiddleware::class,
        \App\Http\Middleware\SecurityHeadersMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
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
        ],

        'performance' => [
            \App\Http\Middleware\CachingMiddleware::class,
            \App\Http\Middleware\CompressionMiddleware::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\AuthenticationMiddleware::class,
        'authorize' => \App\Http\Middleware\AuthorizationMiddleware::class,
        'cache' => \App\Http\Middleware\CachingMiddleware::class,
        'compress' => \App\Http\Middleware\CompressionMiddleware::class,
        'csrf' => \App\Http\Middleware\CsrfProtectionMiddleware::class,
        'sanitize' => \App\Http\Middleware\InputSanitizationMiddleware::class,
        'rate_limit' => \App\Http\Middleware\RateLimitingMiddleware::class,
        'sql_injection' => \App\Http\Middleware\SqlInjectionProtectionMiddleware::class,
        'xss' => \App\Http\Middleware\XssProtectionMiddleware::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \App\Http\Middleware\RequestLoggingMiddleware::class,
        \App\Http\Middleware\RateLimitingMiddleware::class,
        \App\Http\Middleware\CsrfProtectionMiddleware::class,
        \App\Http\Middleware\AuthenticationMiddleware::class,
        \App\Http\Middleware\AuthorizationMiddleware::class,
        \App\Http\Middleware\InputSanitizationMiddleware::class,
        \App\Http\Middleware\XssProtectionMiddleware::class,
        \App\Http\Middleware\SqlInjectionProtectionMiddleware::class,
        \App\Http\Middleware\SecurityHeadersMiddleware::class,
        \App\Http\Middleware\CachingMiddleware::class,
        \App\Http\Middleware\CompressionMiddleware::class,
        \App\Http\Middleware\ResponseTimeTrackingMiddleware::class,
    ];
} 