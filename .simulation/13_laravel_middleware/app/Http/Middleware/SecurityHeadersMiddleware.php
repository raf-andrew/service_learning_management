<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class SecurityHeadersMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Get security headers configuration
        $headers = Config::get('security.headers', $this->getDefaultHeaders());
        
        // Add security headers
        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }
        
        return $response;
    }

    /**
     * Get default security headers configuration.
     *
     * @return array
     */
    private function getDefaultHeaders(): array
    {
        return [
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Prevent clickjacking
            'X-Frame-Options' => 'DENY',
            
            // Enable XSS protection
            'X-XSS-Protection' => '1; mode=block',
            
            // Control referrer information
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Content Security Policy
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';",
            
            // Prevent browsers from performing MIME sniffing
            'X-Download-Options' => 'noopen',
            
            // Disable IE compatibility mode
            'X-UA-Compatible' => 'IE=edge',
            
            // Enable HSTS
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            
            // Prevent caching of sensitive data
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            
            // Feature Policy
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            
            // Cross-Origin Resource Policy
            'Cross-Origin-Resource-Policy' => 'same-site',
            
            // Cross-Origin Embedder Policy
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            
            // Cross-Origin Opener Policy
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];
    }
} 