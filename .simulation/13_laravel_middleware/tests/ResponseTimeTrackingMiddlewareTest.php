<?php

namespace Tests;

use App\Http\Middleware\ResponseTimeTrackingMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;

class ResponseTimeTrackingMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ResponseTimeTrackingMiddleware();
        $this->request = new Request();
        Log::shouldReceive('warning')->byDefault();
    }

    public function testAddsResponseTimeHeader()
    {
        $response = new Response('test response');
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertNotNull($result->headers->get('X-Response-Time'));
        $this->assertStringStartsWith('0.', $result->headers->get('X-Response-Time'));
        $this->assertStringEndsWith(' ms', $result->headers->get('X-Response-Time'));
    }

    public function testLogsSlowResponses()
    {
        $response = new Response('slow response');
        
        $next = function ($request) use ($response) {
            usleep(1100000); // Sleep for 1.1 seconds
            return $response;
        };
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Slow response detected', [
                'path' => $this->request->path(),
                'method' => $this->request->method(),
                'duration' => \Mockery::type('float'),
                'status' => 200
            ]);
        
        $this->middleware->handle($this->request, $next);
    }

    public function testDoesNotLogFastResponses()
    {
        $response = new Response('fast response');
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        Log::shouldReceive('warning')->never();
        
        $this->middleware->handle($this->request, $next);
    }

    public function testPreservesResponseContent()
    {
        $content = 'test content';
        $response = new Response($content);
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals($content, $result->getContent());
    }

    public function testHandlesErrorResponses()
    {
        $response = new Response('error', 500);
        
        $next = function ($request) use ($response) {
            usleep(1100000); // Sleep for 1.1 seconds
            return $response;
        };
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Slow response detected', [
                'path' => $this->request->path(),
                'method' => $this->request->method(),
                'duration' => \Mockery::type('float'),
                'status' => 500
            ]);
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals(500, $result->getStatusCode());
    }
} 