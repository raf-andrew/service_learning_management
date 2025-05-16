<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for security headers that will be
    | applied to all responses by the SecurityHeadersMiddleware.
    |
    */

    'headers' => [
        // Prevent MIME type sniffing
        'X-Content-Type-Options' => 'nosniff',
        
        // Prevent clickjacking
        'X-Frame-Options' => 'DENY',
        
        // Enable XSS protection
        'X-XSS-Protection' => '1; mode=block',
        
        // Control referrer information
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        
        // Content Security Policy
        'Content-Security-Policy' => env('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';"),
        
        // Prevent browsers from performing MIME sniffing
        'X-Download-Options' => 'noopen',
        
        // Disable IE compatibility mode
        'X-UA-Compatible' => 'IE=edge',
        
        // Enable HSTS
        'Strict-Transport-Security' => env('HSTS_MAX_AGE', 'max-age=31536000; includeSubDomains; preload'),
        
        // Prevent caching of sensitive data
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        
        // Feature Policy
        'Permissions-Policy' => env('PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
        
        // Cross-Origin Resource Policy
        'Cross-Origin-Resource-Policy' => 'same-site',
        
        // Cross-Origin Embedder Policy
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        
        // Cross-Origin Opener Policy
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for security logging.
    |
    */

    'logging' => [
        'enabled' => env('SECURITY_LOGGING_ENABLED', true),
        'channel' => env('SECURITY_LOG_CHANNEL', 'security'),
        'level' => env('SECURITY_LOG_LEVEL', 'warning'),
    ],
]; 