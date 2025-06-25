<?php

namespace Tests\Security;

use Tests\BaseTestCase;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\RateLimiting;
use App\Http\Middleware\InputValidation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;

class SecurityMiddlewareTest extends BaseTestCase
{
    protected SecurityHeaders $securityHeaders;
    protected RateLimiting $rateLimiting;
    protected InputValidation $inputValidation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityHeaders = new SecurityHeaders();
        $this->rateLimiting = new RateLimiting(app(RateLimiter::class));
        $this->inputValidation = new InputValidation();
    }

    /**
     * Test security headers middleware
     */
    public function test_security_headers_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->securityHeaders->handle($request, $next);

        // Check security headers
        $this->assertEquals('nosniff', $result->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $result->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $result->headers->get('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $result->headers->get('Referrer-Policy'));
        $this->assertEquals('geolocation=(), microphone=(), camera=()', $result->headers->get('Permissions-Policy'));

        // Check Content Security Policy
        $csp = $result->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    /**
     * Test HSTS header for secure requests
     */
    public function test_hsts_header_secure_request(): void
    {
        $request = Request::create('https://example.com/test', 'GET');
        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->securityHeaders->handle($request, $next);

        $this->assertEquals('max-age=31536000; includeSubDomains; preload', $result->headers->get('Strict-Transport-Security'));
    }

    /**
     * Test rate limiting middleware
     */
    public function test_rate_limiting_middleware(): void
    {
        $request = Request::create('/api/test', 'GET');
        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        // First request should pass
        $result = $this->rateLimiting->handle($request, $next);
        $this->assertEquals(200, $result->getStatusCode());

        // Check rate limit headers
        $this->assertNotNull($result->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($result->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * Test rate limiting exceeded
     */
    public function test_rate_limiting_exceeded(): void
    {
        $request = Request::create('/api/test', 'GET');
        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        // Exceed rate limit
        $maxAttempts = config('modules.modules.api.rate_limiting.requests_per_minute', 60);
        
        for ($i = 0; $i <= $maxAttempts; $i++) {
            $result = $this->rateLimiting->handle($request, $next);
        }

        // Should be rate limited
        $this->assertEquals(429, $result->getStatusCode());
        $this->assertStringContainsString('Too many requests', $result->getContent());
    }

    /**
     * Test input validation middleware - valid input
     */
    public function test_input_validation_valid_input(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello world'
        ]);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test input validation middleware - SQL injection attempt
     */
    public function test_input_validation_sql_injection(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => "'; DROP TABLE users; --",
            'email' => 'test@example.com'
        ]);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertStringContainsString('SQL injection', $result->getContent());
    }

    /**
     * Test input validation middleware - XSS attempt
     */
    public function test_input_validation_xss_attempt(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '<script>alert("XSS")</script>',
            'email' => 'test@example.com'
        ]);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertStringContainsString('XSS', $result->getContent());
    }

    /**
     * Test input validation middleware - path traversal attempt
     */
    public function test_input_validation_path_traversal(): void
    {
        $request = Request::create('/test', 'POST', [
            'filename' => '../../../etc/passwd',
            'email' => 'test@example.com'
        ]);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertStringContainsString('path traversal', $result->getContent());
    }

    /**
     * Test input sanitization
     */
    public function test_input_sanitization(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => "  John Doe\r\n\t  ",
            'email' => '  test@example.com  '
        ]);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            // Check that input was sanitized
            $this->assertEquals('John Doe', $request->input('name'));
            $this->assertEquals('test@example.com', $request->input('email'));
            return $response;
        };

        $this->inputValidation->handle($request, $next);
    }

    /**
     * Test file upload validation - valid file
     */
    public function test_file_upload_validation_valid(): void
    {
        $file = $this->createMockFile('test.txt', 'text/plain', 1024);
        
        $request = Request::create('/test', 'POST');
        $request->files->set('file', $file);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test file upload validation - invalid file type
     */
    public function test_file_upload_validation_invalid_type(): void
    {
        $file = $this->createMockFile('test.exe', 'application/x-executable', 1024);
        
        $request = Request::create('/test', 'POST');
        $request->files->set('file', $file);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertStringContainsString('File type not allowed', $result->getContent());
    }

    /**
     * Test file upload validation - file too large
     */
    public function test_file_upload_validation_too_large(): void
    {
        $file = $this->createMockFile('test.txt', 'text/plain', 20 * 1024 * 1024); // 20MB
        
        $request = Request::create('/test', 'POST');
        $request->files->set('file', $file);

        $response = new Response('Test response');

        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->inputValidation->handle($request, $next);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertStringContainsString('File size exceeds', $result->getContent());
    }

    /**
     * Test security requirements
     */
    public function test_security_requirements(): void
    {
        $requirements = [
            'headers' => [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'X-XSS-Protection' => '1; mode=block',
            ],
            'rate_limiting' => [
                'enabled' => true,
                'max_requests' => 60,
            ],
            'input_validation' => [
                'enabled' => true,
                'sanitization' => true,
            ],
        ];

        $this->assertSecurityRequirements($requirements);
    }

    /**
     * Create a mock file for testing
     */
    private function createMockFile(string $filename, string $mimeType, int $size)
    {
        $file = $this->createMock(\Illuminate\Http\UploadedFile::class);
        
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn($size);
        $file->method('getClientOriginalExtension')->willReturn(pathinfo($filename, PATHINFO_EXTENSION));
        $file->method('getMimeType')->willReturn($mimeType);
        
        return $file;
    }
} 