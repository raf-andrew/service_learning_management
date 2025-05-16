<?php

namespace Tests;

use App\Http\Middleware\CompressionMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class CompressionMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CompressionMiddleware();
        $this->request = new Request();
    }

    public function testCompressesLargeResponseWhenClientAcceptsGzip()
    {
        // Create a large response
        $largeContent = str_repeat('test content', 1000);
        $response = new Response($largeContent);
        
        // Set Accept-Encoding header
        $this->request->headers->set('Accept-Encoding', 'gzip');
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('gzip', $result->headers->get('Content-Encoding'));
        $this->assertEquals('Accept-Encoding', $result->headers->get('Vary'));
        $this->assertLessThan(strlen($largeContent), strlen($result->getContent()));
    }

    public function testDoesNotCompressSmallResponse()
    {
        // Create a small response
        $smallContent = 'small content';
        $response = new Response($smallContent);
        
        // Set Accept-Encoding header
        $this->request->headers->set('Accept-Encoding', 'gzip');
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertNull($result->headers->get('Content-Encoding'));
        $this->assertEquals($smallContent, $result->getContent());
    }

    public function testDoesNotCompressWhenClientDoesNotAcceptGzip()
    {
        // Create a large response
        $largeContent = str_repeat('test content', 1000);
        $response = new Response($largeContent);
        
        // Don't set Accept-Encoding header
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertNull($result->headers->get('Content-Encoding'));
        $this->assertEquals($largeContent, $result->getContent());
    }

    public function testDoesNotCompressAlreadyCompressedResponse()
    {
        // Create a response that's already compressed
        $response = new Response('compressed content');
        $response->headers->set('Content-Encoding', 'gzip');
        
        $this->request->headers->set('Accept-Encoding', 'gzip');
        
        $next = function ($request) use ($response) {
            return $response;
        };
        
        $result = $this->middleware->handle($this->request, $next);
        
        $this->assertEquals('gzip', $result->headers->get('Content-Encoding'));
        $this->assertEquals('compressed content', $result->getContent());
    }
} 