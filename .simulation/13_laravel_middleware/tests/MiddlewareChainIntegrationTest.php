<?php

namespace Tests;

use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\AuthorizationMiddleware;
use App\Http\Middleware\CachingMiddleware;
use App\Http\Middleware\CompressionMiddleware;
use App\Http\Middleware\CsrfProtectionMiddleware;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\PermissionBasedAccessControlMiddleware;
use App\Http\Middleware\RateLimitingMiddleware;
use App\Http\Middleware\RequestLoggingMiddleware;
use App\Http\Middleware\ResponseTimeTrackingMiddleware;
use App\Http\Middleware\RoleBasedAccessControlMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SqlInjectionProtectionMiddleware;
use App\Http\Middleware\XssProtectionMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class MiddlewareChainIntegrationTest extends TestCase
{
    private $middlewareChain;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
        
        // Initialize middleware chain in the correct order
        $this->middlewareChain = [
            new RequestLoggingMiddleware(),
            new RateLimitingMiddleware(),
            new CsrfProtectionMiddleware(),
            new AuthenticationMiddleware(),
            new AuthorizationMiddleware(),
            new RoleBasedAccessControlMiddleware(),
            new PermissionBasedAccessControlMiddleware(),
            new InputSanitizationMiddleware(),
            new XssProtectionMiddleware(),
            new SqlInjectionProtectionMiddleware(),
            new SecurityHeadersMiddleware(),
            new CachingMiddleware(),
            new CompressionMiddleware(),
            new ResponseTimeTrackingMiddleware()
        ];
    }

    public function testMiddlewareChainExecutesInCorrectOrder()
    {
        $response = new Response('test content');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);

        // Verify security headers are present
        $this->assertEquals('nosniff', $result->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $result->headers->get('X-Frame-Options'));
        
        // Verify compression is applied
        $this->assertTrue($result->headers->has('Content-Encoding'));
        
        // Verify response time tracking
        $this->assertTrue($result->headers->has('X-Response-Time'));
        
        // Verify content is preserved
        $this->assertEquals('test content', $result->getContent());
    }

    public function testMiddlewareChainHandlesAuthentication()
    {
        $this->request->headers->set('Authorization', 'Bearer test-token');
        
        $response = new Response('authenticated content');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify authentication headers are processed
        $this->assertTrue($result->headers->has('X-Authenticated-User'));
    }

    public function testMiddlewareChainHandlesRateLimiting()
    {
        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 5; $i++) {
            $response = new Response('rate limited content');
            
            $next = function ($request) use ($response) {
                return $response;
            };

            $result = $this->executeMiddlewareChain($this->request, $next);
        }

        // Verify rate limiting headers
        $this->assertTrue($result->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($result->headers->has('X-RateLimit-Remaining'));
    }

    public function testMiddlewareChainHandlesCaching()
    {
        $response = new Response('cacheable content');
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify caching headers
        $this->assertTrue($result->headers->has('X-Cache'));
    }

    public function testMiddlewareChainHandlesRoleBasedAccess()
    {
        $user = new User();
        $user->roles = ['admin'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response('role-protected content');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify role-based access headers
        $this->assertTrue($result->headers->has('X-Role-Access'));
    }

    public function testMiddlewareChainHandlesPermissionBasedAccess()
    {
        $user = new User();
        $user->permissions = ['manage_users'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response('permission-protected content');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify permission-based access headers
        $this->assertTrue($result->headers->has('X-Permission-Access'));
    }

    public function testMiddlewareChainHandlesInputSanitization()
    {
        $this->request->merge([
            'name' => '<script>alert("xss")</script>',
            'email' => 'test@example.com'
        ]);

        $response = new Response('sanitized content');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify input sanitization
        $this->assertEquals('alert("xss")', $this->request->get('name'));
    }

    public function testMiddlewareChainHandlesErrorResponses()
    {
        $response = new Response('error content', 500);
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify error response handling
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertTrue($result->headers->has('X-Content-Type-Options'));
    }

    public function testMiddlewareChainHandlesEmptyResponses()
    {
        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify empty response handling
        $this->assertTrue($result->headers->has('X-Content-Type-Options'));
        $this->assertTrue($result->headers->has('X-Response-Time'));
    }

    public function testMiddlewareChainHandlesRedirectResponses()
    {
        $response = new Response('', 302);
        $response->headers->set('Location', '/redirect');
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->executeMiddlewareChain($this->request, $next);
        
        // Verify redirect response handling
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/redirect', $result->headers->get('Location'));
        $this->assertTrue($result->headers->has('X-Content-Type-Options'));
    }

    private function executeMiddlewareChain(Request $request, callable $next)
    {
        $chain = $next;
        
        // Build the middleware chain in reverse order
        foreach (array_reverse($this->middlewareChain) as $middleware) {
            $chain = function ($request) use ($middleware, $chain) {
                return $middleware->handle($request, $chain);
            };
        }
        
        return $chain($request);
    }
} 