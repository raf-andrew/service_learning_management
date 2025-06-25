<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        // Security Headers
        $this->addSecurityHeaders($response);

        // Content Security Policy
        $this->addContentSecurityPolicy($response);

        // CORS Headers (if needed)
        $this->addCorsHeaders($response);

        return $response;
    }

    /**
     * Add security headers to the response
     */
    private function addSecurityHeaders(SymfonyResponse $response): void
    {
        $headers = [
            // Prevent clickjacking
            'X-Frame-Options' => 'DENY',
            
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Enable XSS protection
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions policy
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            
            // Strict transport security (HTTPS only)
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            
            // Cache control for sensitive data
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            
            // Pragma for backward compatibility
            'Pragma' => 'no-cache',
            
            // Expires header
            'Expires' => '0',
        ];

        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }
    }

    /**
     * Add Content Security Policy header
     */
    private function addContentSecurityPolicy(SymfonyResponse $response): void
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https: wss:",
            "media-src 'self' https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
    }

    /**
     * Add CORS headers if needed
     */
    private function addCorsHeaders(SymfonyResponse $response): void
    {
        // Only add CORS headers for API routes
        if (request()->is('api/*')) {
            $response->headers->set('Access-Control-Allow-Origin', config('app.frontend_url', '*'));
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }
    }

    /**
     * Add rate limiting headers
     */
    private function addRateLimitHeaders(SymfonyResponse $response, array $limits): void
    {
        if (isset($limits['remaining'])) {
            $response->headers->set('X-RateLimit-Remaining', $limits['remaining']);
        }
        
        if (isset($limits['limit'])) {
            $response->headers->set('X-RateLimit-Limit', $limits['limit']);
        }
        
        if (isset($limits['reset'])) {
            $response->headers->set('X-RateLimit-Reset', $limits['reset']);
        }
    }

    /**
     * Add custom headers for monitoring and debugging
     */
    private function addCustomHeaders(SymfonyResponse $response): void
    {
        $headers = [
            'X-Powered-By' => 'Laravel Service Learning Management',
            'X-Application-Version' => config('app.version', '1.0.0'),
            'X-Environment' => config('app.env', 'production'),
        ];

        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }
    }
}
