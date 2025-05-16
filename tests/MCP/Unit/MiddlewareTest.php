<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Middleware\AuthenticationMiddleware;
use MCP\Middleware\AuthorizationMiddleware;
use MCP\Middleware\RateLimitMiddleware;
use MCP\Middleware\CorsMiddleware;
use MCP\Middleware\MaintenanceModeMiddleware;
use MCP\Middleware\ValidationMiddleware;
use Psr\Log\LoggerInterface;
use MCP\Configuration;
use MCP\Request;
use MCP\Response;

class MiddlewareTest extends TestCase
{
    private LoggerInterface $logger;
    private Configuration $config;
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->config = new Configuration($this->logger);
        $this->request = new Request($this->logger);
        $this->response = new Response($this->logger);
    }

    public function testAuthenticationMiddleware(): void
    {
        $middleware = new AuthenticationMiddleware($this->logger, $this->config);
        
        $this->request->setHeader('Authorization', 'Bearer test-token');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testAuthenticationMiddlewareWithoutToken(): void
    {
        $middleware = new AuthenticationMiddleware($this->logger, $this->config);
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(401, $result->getStatusCode());
    }

    public function testAuthorizationMiddleware(): void
    {
        $middleware = new AuthorizationMiddleware($this->logger, $this->config);
        
        $this->request->setAttribute('user', ['role' => 'admin']);
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testAuthorizationMiddlewareWithoutUser(): void
    {
        $middleware = new AuthorizationMiddleware($this->logger, $this->config);
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(403, $result->getStatusCode());
    }

    public function testRateLimitMiddleware(): void
    {
        $middleware = new RateLimitMiddleware($this->logger, $this->config);
        
        $this->request->setIp('127.0.0.1');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testRateLimitMiddlewareExceeded(): void
    {
        $middleware = new RateLimitMiddleware($this->logger, $this->config);
        
        $this->request->setIp('127.0.0.1');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        // Simulate rate limit exceeded
        for ($i = 0; $i < 100; $i++) {
            $middleware->handle($this->request, $this->response, $next);
        }
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(429, $result->getStatusCode());
    }

    public function testCorsMiddleware(): void
    {
        $middleware = new CorsMiddleware($this->logger, $this->config);
        
        $this->request->setMethod('OPTIONS');
        $this->request->setHeader('Origin', 'http://example.com');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(204, $result->getStatusCode());
        $this->assertEquals('http://example.com', $result->getHeader('Access-Control-Allow-Origin'));
    }

    public function testCorsMiddlewareWithInvalidOrigin(): void
    {
        $middleware = new CorsMiddleware($this->logger, $this->config);
        
        $this->request->setMethod('GET');
        $this->request->setHeader('Origin', 'http://invalid.com');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(403, $result->getStatusCode());
    }

    public function testMaintenanceModeMiddleware(): void
    {
        $middleware = new MaintenanceModeMiddleware($this->logger, $this->config);
        
        $this->config->set('app.maintenance', true);
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(503, $result->getStatusCode());
    }

    public function testMaintenanceModeMiddlewareDisabled(): void
    {
        $middleware = new MaintenanceModeMiddleware($this->logger, $this->config);
        
        $this->config->set('app.maintenance', false);
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testValidationMiddleware(): void
    {
        $middleware = new ValidationMiddleware($this->logger, $this->config);
        
        $this->request->setMethod('POST');
        $this->request->setPost('name', 'Test Name');
        $this->request->setPost('email', 'test@example.com');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testValidationMiddlewareWithInvalidData(): void
    {
        $middleware = new ValidationMiddleware($this->logger, $this->config);
        
        $this->request->setMethod('POST');
        $this->request->setPost('name', '');
        $this->request->setPost('email', 'invalid-email');
        
        $next = function ($request, $response) {
            return $response;
        };
        
        $result = $middleware->handle($this->request, $this->response, $next);
        
        $this->assertEquals(422, $result->getStatusCode());
    }
} 