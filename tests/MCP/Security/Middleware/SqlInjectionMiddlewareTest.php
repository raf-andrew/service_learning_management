<?php

namespace Tests\MCP\Security\Middleware;

use MCP\Security\Middleware\SqlInjectionMiddleware;
use MCP\Exceptions\SqlInjectionException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SqlInjectionMiddlewareTest extends TestCase
{
    protected SqlInjectionMiddleware $middleware;
    protected ServerRequestInterface $request;
    protected RequestHandlerInterface $handler;
    protected ResponseInterface $response;
    protected StreamInterface $stream;

    protected function setUp(): void
    {
        $this->middleware = new SqlInjectionMiddleware();
        
        // Create mock request
        $this->request = $this->createMock(ServerRequestInterface::class);
        
        // Create mock handler
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        
        // Create mock response
        $this->response = $this->createMock(ResponseInterface::class);
        
        // Create mock stream
        $this->stream = $this->createMock(StreamInterface::class);
    }

    public function testSuccessfulSqlInjectionProtection()
    {
        // Mock request with safe data
        $this->request->method('getQueryParams')
            ->willReturn(['name' => 'John Doe', 'age' => '25']);
        
        $this->request->method('getParsedBody')
            ->willReturn(['email' => 'john@example.com']);
        
        $this->request->method('getCookieParams')
            ->willReturn(['session' => 'abc123']);
        
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);
        
        // Mock handler response
        $this->handler->method('handle')
            ->willReturn($this->response);
        
        // Test middleware
        $response = $this->middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $response);
    }

    public function testSqlInjectionProtectionWithExcludedRoute()
    {
        // Mock request with excluded route
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn((object)['getName' => 'excluded_route']);
        
        // Mock handler response
        $this->handler->method('handle')
            ->willReturn($this->response);
        
        // Create middleware with excluded route
        $middleware = new SqlInjectionMiddleware([], ['excluded_route']);
        
        // Test middleware
        $response = $middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $response);
    }

    public function testSqlInjectionProtectionWithMaliciousQuery()
    {
        // Mock request with malicious data
        $this->request->method('getQueryParams')
            ->willReturn(['query' => "'; DROP TABLE users; --"]);
        
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);
        
        // Test middleware
        $this->expectException(SqlInjectionException::class);
        $this->expectExceptionMessage('Potential SQL injection detected');
        
        $this->middleware->process($this->request, $this->handler);
    }

    public function testSqlInjectionProtectionWithMaliciousPattern()
    {
        // Mock request with malicious pattern
        $this->request->method('getQueryParams')
            ->willReturn(['query' => "1' OR '1'='1"]);
        
        $this->request->method('getAttribute')
            ->with('route')
            ->willReturn(null);
        
        // Test middleware
        $this->expectException(SqlInjectionException::class);
        $this->expectExceptionMessage('Potential SQL injection pattern detected');
        
        $this->middleware->process($this->request, $this->handler);
    }

    public function testQueryValidationWithValidQuery()
    {
        $query = "SELECT * FROM users WHERE id = ?";
        $parameters = [1];
        
        $result = $this->middleware->validateQuery($query, $parameters);
        
        $this->assertTrue($result);
    }

    public function testQueryValidationWithInvalidQuery()
    {
        $query = "SELECT * FROM users WHERE id = 1 OR 1=1";
        
        $this->expectException(SqlInjectionException::class);
        $this->expectExceptionMessage('Invalid SQL keyword context');
        
        $this->middleware->validateQuery($query);
    }

    public function testQueryValidationWithExcessiveLength()
    {
        $query = str_repeat('a', 10001);
        
        $this->expectException(SqlInjectionException::class);
        $this->expectExceptionMessage('Query exceeds maximum length');
        
        $this->middleware->validateQuery($query);
    }

    public function testQueryValidationWithExcessiveParameters()
    {
        $query = "SELECT * FROM users WHERE id IN (" . str_repeat('?,', 101) . "?)";
        $parameters = array_fill(0, 102, 1);
        
        $this->expectException(SqlInjectionException::class);
        $this->expectExceptionMessage('Too many parameters in query');
        
        $this->middleware->validateQuery($query, $parameters);
    }

    public function testQueryValidationWithParameterMismatch()
    {
        $query = "SELECT * FROM users WHERE id = ? AND name = ?";
        $parameters = [1];
        
        $this->expectException(SqlInjectionException::class);
        $this->expectExceptionMessage('Parameter count mismatch');
        
        $this->middleware->validateQuery($query, $parameters);
    }

    public function testQueryValidationWithDisabledPreparedStatements()
    {
        $middleware = new SqlInjectionMiddleware([
            'enforce_prepared_statements' => false
        ]);
        
        $query = "SELECT * FROM users WHERE id = 1";
        $result = $middleware->validateQuery($query);
        
        $this->assertTrue($result);
    }

    public function testQueryValidationWithCustomConfig()
    {
        $middleware = new SqlInjectionMiddleware([
            'max_query_length' => 100,
            'max_parameters' => 10,
            'log_violations' => false,
            'block_violations' => false
        ]);
        
        $query = "SELECT * FROM users WHERE id = ?";
        $parameters = [1];
        
        $result = $middleware->validateQuery($query, $parameters);
        
        $this->assertTrue($result);
    }
} 