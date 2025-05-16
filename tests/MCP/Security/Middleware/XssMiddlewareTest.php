<?php

namespace Tests\MCP\Security\Middleware;

use MCP\Security\Middleware\XssMiddleware;
use MCP\Exceptions\XssException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * XSS Middleware Test
 * 
 * Tests the functionality of the XSS middleware.
 * 
 * @package Tests\MCP\Security\Middleware
 */
class XssMiddlewareTest extends TestCase
{
    protected XssMiddleware $middleware;
    protected ServerRequestInterface|MockObject $request;
    protected RequestHandlerInterface|MockObject $handler;
    protected ResponseInterface|MockObject $response;
    protected StreamInterface|MockObject $stream;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);

        $this->middleware = new XssMiddleware();
    }

    /**
     * Test successful XSS protection
     */
    public function testSuccessfulXssProtection(): void
    {
        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);

        $this->request->method('getQueryParams')
            ->willReturn(['test' => '<script>alert("xss")</script>']);

        $this->request->method('getParsedBody')
            ->willReturn(['test' => '<script>alert("xss")</script>']);

        $this->request->method('getCookieParams')
            ->willReturn(['test' => '<script>alert("xss")</script>']);

        // Mock the response
        $this->response->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/html');

        $this->response->method('getBody')
            ->willReturn($this->stream);

        $this->stream->method('__toString')
            ->willReturn('<p>Test content</p>');

        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->response->method('withBody')
            ->willReturnSelf();

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    /**
     * Test XSS protection with excluded route
     */
    public function testXssProtectionWithExcludedRoute(): void
    {
        // Create middleware with excluded route
        $middleware = new XssMiddleware([], ['excluded.route']);

        // Mock the route
        $route = $this->createMock(\MCP\Core\Route::class);
        $route->method('getName')->willReturn('excluded.route');

        // Mock the request
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

    /**
     * Test XSS protection with non-HTML response
     */
    public function testXssProtectionWithNonHtmlResponse(): void
    {
        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);

        // Mock the response
        $this->response->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    /**
     * Test XSS protection with allowed HTML tags
     */
    public function testXssProtectionWithAllowedHtmlTags(): void
    {
        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);

        $this->request->method('getQueryParams')
            ->willReturn(['test' => '<p>Allowed tag</p><script>Not allowed</script>']);

        // Mock the response
        $this->response->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/html');

        $this->response->method('getBody')
            ->willReturn($this->stream);

        $this->stream->method('__toString')
            ->willReturn('<p>Test content</p>');

        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->response->method('withBody')
            ->willReturnSelf();

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    /**
     * Test XSS protection with security headers
     */
    public function testXssProtectionWithSecurityHeaders(): void
    {
        // Mock the request
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);

        // Mock the response
        $this->response->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/html');

        $this->response->method('getBody')
            ->willReturn($this->stream);

        $this->stream->method('__toString')
            ->willReturn('<p>Test content</p>');

        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->response->method('withBody')
            ->willReturnSelf();

        // Mock the handler
        $this->handler->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        // Test the middleware
        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }
} 