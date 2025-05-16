<?php

namespace Tests\MCP\Security\Middleware;

use MCP\Security\Middleware\AuthorizationMiddleware;
use MCP\Security\RBAC;
use MCP\Security\Authentication;
use MCP\Exceptions\AuthorizationException;
use MCP\Interfaces\Authenticatable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Authorization Middleware Test
 * 
 * Tests the functionality of the authorization middleware.
 * 
 * @package Tests\MCP\Security\Middleware
 */
class AuthorizationMiddlewareTest extends TestCase
{
    protected AuthorizationMiddleware $middleware;
    protected RBAC|MockObject $rbac;
    protected Authentication|MockObject $auth;
    protected Authenticatable|MockObject $user;
    protected ServerRequestInterface|MockObject $request;
    protected RequestHandlerInterface|MockObject $handler;
    protected ResponseInterface|MockObject $response;

    protected function setUp(): void
    {
        $this->rbac = $this->createMock(RBAC::class);
        $this->auth = $this->createMock(Authentication::class);
        $this->user = $this->createMock(Authenticatable::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->middleware = new AuthorizationMiddleware($this->rbac, $this->auth);
    }

    /**
     * Test successful authorization
     */
    public function testSuccessfulAuthorization(): void
    {
        // Mock the authentication
        $this->auth->method('getCurrentUser')->willReturn($this->user);

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getPermission')->willReturn('test.permission');

        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the RBAC check
        $this->rbac->method('hasPermission')
            ->with($this->user, 'test.permission')
            ->willReturn(true);

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    /**
     * Test authorization with no authentication
     */
    public function testAuthorizationWithNoAuthentication(): void
    {
        // Mock the authentication
        $this->auth->method('getCurrentUser')->willReturn(null);

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getPermission')->willReturn('test.permission');

        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Test the middleware
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Authentication required.');
        $this->expectExceptionCode(401);

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test authorization with insufficient permissions
     */
    public function testAuthorizationWithInsufficientPermissions(): void
    {
        // Mock the authentication
        $this->auth->method('getCurrentUser')->willReturn($this->user);

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getPermission')->willReturn('test.permission');

        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the RBAC check
        $this->rbac->method('hasPermission')
            ->with($this->user, 'test.permission')
            ->willReturn(false);

        // Test the middleware
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Permission denied.');
        $this->expectExceptionCode(403);

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test authorization with no required permission
     */
    public function testAuthorizationWithNoRequiredPermission(): void
    {
        // Mock the authentication
        $this->auth->method('getCurrentUser')->willReturn($this->user);

        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }
} 