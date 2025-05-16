<?php

namespace Tests\MCP\Security\Middleware;

use MCP\Security\Middleware\CsrfMiddleware;
use MCP\Exceptions\CsrfException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * CSRF Middleware Test
 * 
 * Tests the functionality of the CSRF middleware.
 * 
 * @package Tests\MCP\Security\Middleware
 */
class CsrfMiddlewareTest extends TestCase
{
    protected CsrfMiddleware $middleware;
    protected ServerRequestInterface|MockObject $request;
    protected RequestHandlerInterface|MockObject $handler;
    protected ResponseInterface|MockObject $response;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->middleware = new CsrfMiddleware();
    }

    /**
     * Test successful CSRF validation
     */
    public function testSuccessfulCsrfValidation(): void
    {
        // Mock the request method
        $this->request->method('getMethod')->willReturn('POST');

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getName')->willReturn('test.route');
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the token
        $token = bin2hex(random_bytes(32));
        $this->request->method('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->willReturn($token);

        // Mock the cookie
        $this->request->method('getCookieParams')
            ->willReturn(['csrf_token' => $token]);

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Mock the response
        $this->response->method('withHeader')
            ->willReturnSelf();

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    /**
     * Test CSRF validation with missing token
     */
    public function testCsrfValidationWithMissingToken(): void
    {
        // Mock the request method
        $this->request->method('getMethod')->willReturn('POST');

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getName')->willReturn('test.route');
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the token (missing)
        $this->request->method('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->willReturn('');

        // Mock the parsed body (missing token)
        $this->request->method('getParsedBody')
            ->willReturn([]);

        // Test the middleware
        $this->expectException(CsrfException::class);
        $this->expectExceptionMessage('CSRF token missing.');

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test CSRF validation with missing cookie
     */
    public function testCsrfValidationWithMissingCookie(): void
    {
        // Mock the request method
        $this->request->method('getMethod')->willReturn('POST');

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getName')->willReturn('test.route');
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the token
        $token = bin2hex(random_bytes(32));
        $this->request->method('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->willReturn($token);

        // Mock the cookie (missing)
        $this->request->method('getCookieParams')
            ->willReturn([]);

        // Test the middleware
        $this->expectException(CsrfException::class);
        $this->expectExceptionMessage('CSRF cookie missing.');

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test CSRF validation with token mismatch
     */
    public function testCsrfValidationWithTokenMismatch(): void
    {
        // Mock the request method
        $this->request->method('getMethod')->willReturn('POST');

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getName')->willReturn('test.route');
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the token
        $this->request->method('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->willReturn('token1');

        // Mock the cookie
        $this->request->method('getCookieParams')
            ->willReturn(['csrf_token' => 'token2']);

        // Test the middleware
        $this->expectException(CsrfException::class);
        $this->expectExceptionMessage('CSRF token mismatch.');

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test CSRF validation with safe method
     */
    public function testCsrfValidationWithSafeMethod(): void
    {
        // Mock the request method
        $this->request->method('getMethod')->willReturn('GET');

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    /**
     * Test CSRF validation with excluded route
     */
    public function testCsrfValidationWithExcludedRoute(): void
    {
        // Create middleware with excluded route
        $middleware = new CsrfMiddleware([], ['excluded.route']);

        // Mock the request method
        $this->request->method('getMethod')->willReturn('POST');

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getName')->willReturn('excluded.route');
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }
} 