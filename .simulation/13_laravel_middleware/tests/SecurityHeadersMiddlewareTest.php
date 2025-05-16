<?php

namespace Tests;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
        $this->request = new Request();
    }

    public function testDefaultSecurityHeadersAreSet()
    {
        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next);
        
        // Check essential security headers
        $this->assertEquals('nosniff', $result->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $result->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $result->headers->get('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $result->headers->get('Referrer-Policy'));
        $this->assertStringContainsString("default-src 'self'", $result->headers->get('Content-Security-Policy'));
        $this->assertEquals('noopen', $result->headers->get('X-Download-Options'));
        $this->assertEquals('IE=edge', $result->headers->get('X-UA-Compatible'));
        $this->assertStringContainsString('max-age=31536000', $result->headers->get('Strict-Transport-Security'));
        $this->assertEquals('no-store, no-cache, must-revalidate, max-age=0', $result->headers->get('Cache-Control'));
        $this->assertEquals('no-cache', $result->headers->get('Pragma'));
        $this->assertStringContainsString('geolocation=()', $result->headers->get('Permissions-Policy'));
        $this->assertEquals('same-site', $result->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertEquals('require-corp', $result->headers->get('Cross-Origin-Embedder-Policy'));
        $this->assertEquals('same-origin', $result->headers->get('Cross-Origin-Opener-Policy'));
    }

    public function testCustomSecurityHeadersAreApplied()
    {
        Config::set('security.headers', [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'none'",
        ]);

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('SAMEORIGIN', $result->headers->get('X-Frame-Options'));
        $this->assertEquals("default-src 'none'", $result->headers->get('Content-Security-Policy'));
    }

    public function testEnvironmentVariablesOverrideDefaults()
    {
        putenv('CSP_POLICY=default-src \'none\'');
        putenv('HSTS_MAX_AGE=max-age=63072000');
        putenv('PERMISSIONS_POLICY=geolocation=(self)');

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals("default-src 'none'", $result->headers->get('Content-Security-Policy'));
        $this->assertEquals('max-age=63072000', $result->headers->get('Strict-Transport-Security'));
        $this->assertEquals('geolocation=(self)', $result->headers->get('Permissions-Policy'));

        // Clean up environment variables
        putenv('CSP_POLICY');
        putenv('HSTS_MAX_AGE');
        putenv('PERMISSIONS_POLICY');
    }

    public function testResponseIsNotModifiedIfAlreadyHasHeaders()
    {
        $response = new Response();
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('SAMEORIGIN', $result->headers->get('X-Frame-Options'));
    }
} 