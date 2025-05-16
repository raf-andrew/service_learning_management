<?php

namespace Tests;

use App\Http\Middleware\RateLimitingMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RateLimitingMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RateLimitingMiddleware();
        $this->request = new Request();
        Cache::flush();
    }

    /** @test */
    public function it_allows_requests_within_rate_limit()
    {
        $maxAttempts = 60;
        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['tooManyAttempts'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('tooManyAttempts')
            ->willReturn(false);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertEquals($maxAttempts, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals($maxAttempts - 1, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /** @test */
    public function it_blocks_requests_exceeding_rate_limit()
    {
        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['tooManyAttempts', 'getRetryAfter'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('tooManyAttempts')
            ->willReturn(true);

        $this->middleware->expects($this->once())
            ->method('getRetryAfter')
            ->willReturn(60);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Too Many Attempts', $response->getContent());
        $this->assertStringContainsString('retry_after', $response->getContent());
    }

    /** @test */
    public function it_logs_rate_limit_exceeded_events()
    {
        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['tooManyAttempts', 'getRetryAfter'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('tooManyAttempts')
            ->willReturn(true);

        $this->middleware->expects($this->once())
            ->method('getRetryAfter')
            ->willReturn(60);

        Log::shouldReceive('warning')
            ->once()
            ->with('Rate Limit Exceeded', \Mockery::on(function ($args) {
                return isset($args['ip']) &&
                       isset($args['method']) &&
                       isset($args['url']) &&
                       isset($args['user_agent']) &&
                       isset($args['key']);
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_uses_ip_based_rate_limiting_by_default()
    {
        $this->request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertTrue(Cache::has('rate_limit:127.0.0.1'));
    }

    /** @test */
    public function it_supports_user_based_rate_limiting()
    {
        $user = new \stdClass();
        $user->id = 1;

        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['config'])
            ->getMock();

        $this->middleware->expects($this->atLeastOnce())
            ->method('config')
            ->with('rate_limit.identifier')
            ->willReturn('user');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertTrue(Cache::has('rate_limit:1'));
    }

    /** @test */
    public function it_supports_route_based_rate_limiting()
    {
        $route = $this->getMockBuilder(\Illuminate\Routing\Route::class)
            ->disableOriginalConstructor()
            ->getMock();

        $route->expects($this->once())
            ->method('getName')
            ->willReturn('test.route');

        $this->request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['config'])
            ->getMock();

        $this->middleware->expects($this->atLeastOnce())
            ->method('config')
            ->with('rate_limit.identifier')
            ->willReturn('route');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertTrue(Cache::has('rate_limit:test.route'));
    }

    /** @test */
    public function it_allows_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }

    /** @test */
    public function it_respects_custom_rate_limit_configuration()
    {
        $this->middleware = $this->getMockBuilder(RateLimitingMiddleware::class)
            ->onlyMethods(['config'])
            ->getMock();

        $this->middleware->expects($this->atLeastOnce())
            ->method('config')
            ->willReturnMap([
                ['rate_limit.max_attempts', 60, 10],
                ['rate_limit.decay_minutes', 1, 5]
            ]);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertEquals(10, $response->headers->get('X-RateLimit-Limit'));
    }
} 