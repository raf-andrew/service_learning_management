<?php

namespace Tests;

use App\Http\Middleware\InputSanitizationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class InputSanitizationMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InputSanitizationMiddleware();
        $this->request = new Request();
    }

    public function testSanitizesGetParameters()
    {
        $this->request->query->set('name', '<script>alert("XSS")</script>');
        $this->request->query->set('email', 'test@example.com');
        
        $next = function ($request) {
            return new Response();
        };
        
        $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $this->request->query->get('name'));
        $this->assertEquals('test@example.com', $this->request->query->get('email'));
    }

    public function testSanitizesPostParameters()
    {
        $this->request->request->set('content', '<p>HTML content</p>');
        $this->request->request->set('safe', 'safe content');
        
        $next = function ($request) {
            return new Response();
        };
        
        $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('&lt;p&gt;HTML content&lt;/p&gt;', $this->request->request->get('content'));
        $this->assertEquals('safe content', $this->request->request->get('safe'));
    }

    public function testSanitizesJsonInput()
    {
        $jsonData = [
            'name' => '<script>alert("XSS")</script>',
            'nested' => [
                'content' => '<p>HTML content</p>'
            ]
        ];
        
        $this->request->json = new \stdClass();
        $this->request->json->all = function() use ($jsonData) {
            return $jsonData;
        };
        
        $next = function ($request) {
            return new Response();
        };
        
        $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $jsonData['name']);
        $this->assertEquals('&lt;p&gt;HTML content&lt;/p&gt;', $jsonData['nested']['content']);
    }

    public function testPreservesResponse()
    {
        $response = new Response('test response');
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals($response, $result);
    }

    public function testHandlesEmptyInput()
    {
        $next = function ($request) {
            return new Response();
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertInstanceOf(Response::class, $result);
    }
} 