<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\ValidationException;
use MCP\AuthenticationException;
use MCP\AuthorizationException;
use MCP\RateLimitException;
use MCP\NotFoundException;
use MCP\MethodNotAllowedException;
use MCP\ServerErrorException;
use MCP\MaintenanceModeException;
use MCP\CorsException;

class ExceptionsTest extends TestCase
{
    public function testValidationException(): void
    {
        $exception = new ValidationException('Validation failed');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    public function testAuthenticationException(): void
    {
        $exception = new AuthenticationException('Authentication failed');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Authentication failed', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }

    public function testAuthorizationException(): void
    {
        $exception = new AuthorizationException('Authorization failed');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Authorization failed', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }

    public function testRateLimitException(): void
    {
        $exception = new RateLimitException('Rate limit exceeded');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testNotFoundException(): void
    {
        $exception = new NotFoundException('Resource not found');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Resource not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testMethodNotAllowedException(): void
    {
        $exception = new MethodNotAllowedException('Method not allowed');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Method not allowed', $exception->getMessage());
        $this->assertEquals(405, $exception->getCode());
    }

    public function testServerErrorException(): void
    {
        $exception = new ServerErrorException('Server error');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Server error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    public function testMaintenanceModeException(): void
    {
        $exception = new MaintenanceModeException('System is under maintenance');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('System is under maintenance', $exception->getMessage());
        $this->assertEquals(503, $exception->getCode());
    }

    public function testCorsException(): void
    {
        $exception = new CorsException('CORS validation failed');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('CORS validation failed', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }

    public function testValidationExceptionWithErrors(): void
    {
        $errors = [
            'name' => 'Name is required',
            'email' => 'Email is invalid'
        ];
        
        $exception = new ValidationException('Validation failed', $errors);
        
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function testRateLimitExceptionWithRetryAfter(): void
    {
        $retryAfter = 60;
        $exception = new RateLimitException('Rate limit exceeded', $retryAfter);
        
        $this->assertEquals($retryAfter, $exception->getRetryAfter());
    }

    public function testMethodNotAllowedExceptionWithAllowedMethods(): void
    {
        $allowedMethods = ['GET', 'POST'];
        $exception = new MethodNotAllowedException('Method not allowed', $allowedMethods);
        
        $this->assertEquals($allowedMethods, $exception->getAllowedMethods());
    }

    public function testMaintenanceModeExceptionWithRetryAfter(): void
    {
        $retryAfter = 3600;
        $exception = new MaintenanceModeException('System is under maintenance', $retryAfter);
        
        $this->assertEquals($retryAfter, $exception->getRetryAfter());
    }

    public function testCorsExceptionWithOrigin(): void
    {
        $origin = 'http://invalid.com';
        $exception = new CorsException('CORS validation failed', $origin);
        
        $this->assertEquals($origin, $exception->getOrigin());
    }
} 