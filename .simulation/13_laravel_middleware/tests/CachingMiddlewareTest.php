<?php

namespace Tests;

use App\Http\Middleware\CachingMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CachingMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CachingMiddleware();
        $this->request = new Request();
        $this->request->setMethod('GET');
    }

    /** @test */
    public function it_caches_successful_responses()
    {
        $response = response('Test Response', 200);
        $response->headers->set('Content-Type', 'application/json');

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return str_starts_with($key, 'response_') && $ttl === 3600;
            });

        Log::shouldReceive('debug')
            ->once()
            ->with('Response Cached', \Mockery::on(function ($args) {
                return isset($args['key']) &&
                       isset($args['ttl']) &&
                       isset($args['status']);
            }));

        $result = $this->middleware->handle($this->request, function ($request) use ($response) {
            return $response;
        });

        $this->assertEquals('Test Response', $result->getContent());
        $this->assertEquals('MISS', $result->headers->get('X-Cache'));
        $this->assertEquals('public, max-age=3600', $result->headers->get('Cache-Control'));
    }

    /** @test */
    public function it_returns_cached_responses()
    {
        $cachedResponse = response('Cached Response', 200);
        $cachedResponse->headers->set('Content-Type', 'application/json');

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn($cachedResponse);

        $result = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Cached Response', $result->getContent());
        $this->assertEquals('HIT', $result->headers->get('X-Cache'));
        $this->assertEquals('public, max-age=3600', $result->headers->get('Cache-Control'));
    }

    /** @test */
    public function it_does_not_cache_non_get_requests()
    {
        $this->request->setMethod('POST');

        Cache::shouldReceive('has')
            ->never();

        Cache::shouldReceive('put')
            ->never();

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_does_not_cache_error_responses()
    {
        $response = response('Error Response', 500);
        $response->headers->set('Content-Type', 'application/json');

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->never();

        $result = $this->middleware->handle($this->request, function ($request) use ($response) {
            return $response;
        });

        $this->assertEquals('Error Response', $result->getContent());
        $this->assertEquals('MISS', $result->headers->get('X-Cache'));
    }

    /** @test */
    public function it_does_not_cache_non_json_or_html_responses()
    {
        $response = response('Test Response', 200);
        $response->headers->set('Content-Type', 'text/plain');

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->never();

        $result = $this->middleware->handle($this->request, function ($request) use ($response) {
            return $response;
        });

        $this->assertEquals('Test Response', $result->getContent());
        $this->assertEquals('MISS', $result->headers->get('X-Cache'));
    }

    /** @test */
    public function it_allows_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(CachingMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        Cache::shouldReceive('has')
            ->never();

        Cache::shouldReceive('put')
            ->never();

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_includes_auth_header_in_cache_key()
    {
        $this->request->headers->set('Authorization', 'Bearer token123');

        $response = response('Test Response', 200);
        $response->headers->set('Content-Type', 'application/json');

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return str_contains($key, md5('Bearer token123'));
            });

        $result = $this->middleware->handle($this->request, function ($request) use ($response) {
            return $response;
        });

        $this->assertEquals('Test Response', $result->getContent());
    }

    /** @test */
    public function it_respects_custom_ttl_from_config()
    {
        $this->middleware = $this->getMockBuilder(CachingMiddleware::class)
            ->onlyMethods(['config'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('config')
            ->with('cache.ttl')
            ->willReturn(7200);

        $response = response('Test Response', 200);
        $response->headers->set('Content-Type', 'application/json');

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return $ttl === 7200;
            });

        $result = $this->middleware->handle($this->request, function ($request) use ($response) {
            return $response;
        });

        $this->assertEquals('public, max-age=7200', $result->headers->get('Cache-Control'));
    }
} 