<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\GatewayController;
use App\Services\RateLimiter;
use App\Services\CacheManager;
use App\Services\RouteManager;
use App\Services\AuthenticationService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class GatewayControllerTest extends TestCase
{
    protected $controller;
    protected $rateLimiter;
    protected $cacheManager;
    protected $routeManager;
    protected $authService;
    protected $logger;
    protected $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimiter = Mockery::mock(RateLimiter::class);
        $this->cacheManager = Mockery::mock(CacheManager::class);
        $this->routeManager = Mockery::mock(RouteManager::class);
        $this->authService = Mockery::mock(AuthenticationService::class);
        $this->logger = Mockery::mock(LoggingService::class);
        $this->httpClient = Mockery::mock(Client::class);

        $this->controller = new GatewayController(
            $this->rateLimiter,
            $this->cacheManager,
            $this->routeManager,
            $this->authService,
            $this->logger
        );
    }

    public function test_handles_rate_limit_exceeded()
    {
        $request = Request::create('/api/test', 'GET');
        
        $this->rateLimiter->shouldReceive('check')
            ->once()
            ->with($request)
            ->andReturn(false);

        $this->logger->shouldReceive('logRequest')
            ->once()
            ->with($request);

        $response = $this->controller->handle($request);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Rate limit exceeded',
            'code' => 429
        ], json_decode($response->getContent(), true));
    }

    public function test_handles_unauthorized()
    {
        $request = Request::create('/api/test', 'GET');
        
        $this->rateLimiter->shouldReceive('check')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->authService->shouldReceive('authenticate')
            ->once()
            ->with($request)
            ->andReturn(false);

        $this->logger->shouldReceive('logRequest')
            ->once()
            ->with($request);

        $response = $this->controller->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Unauthorized',
            'code' => 401
        ], json_decode($response->getContent(), true));
    }

    public function test_handles_not_found()
    {
        $request = Request::create('/api/test', 'GET');
        
        $this->rateLimiter->shouldReceive('check')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->authService->shouldReceive('authenticate')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->routeManager->shouldReceive('getRoute')
            ->once()
            ->with('/api/test')
            ->andReturn(null);

        $this->logger->shouldReceive('logRequest')
            ->once()
            ->with($request);

        $response = $this->controller->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Route not found',
            'code' => 404
        ], json_decode($response->getContent(), true));
    }

    public function test_returns_cached_response()
    {
        $request = Request::create('/api/test', 'GET');
        $route = [
            'target_url' => 'http://api.example.com',
            'cache_ttl' => 300
        ];
        $cachedResponse = response('cached data', 200);
        
        $this->rateLimiter->shouldReceive('check')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->authService->shouldReceive('authenticate')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->routeManager->shouldReceive('getRoute')
            ->once()
            ->with('/api/test')
            ->andReturn($route);

        $this->cacheManager->shouldReceive('get')
            ->once()
            ->with($request)
            ->andReturn($cachedResponse);

        $this->logger->shouldReceive('logRequest')
            ->once()
            ->with($request);

        $response = $this->controller->handle($request);

        $this->assertEquals($cachedResponse, $response);
    }

    public function test_forwards_request_and_caches_response()
    {
        $request = Request::create('/api/test', 'GET');
        $route = [
            'target_url' => 'http://api.example.com',
            'cache_ttl' => 300,
            'timeout' => 30
        ];
        $targetResponse = new Response(200, [], 'response data');
        
        $this->rateLimiter->shouldReceive('check')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->authService->shouldReceive('authenticate')
            ->once()
            ->with($request)
            ->andReturn(true);

        $this->routeManager->shouldReceive('getRoute')
            ->once()
            ->with('/api/test')
            ->andReturn($route);

        $this->cacheManager->shouldReceive('get')
            ->once()
            ->with($request)
            ->andReturn(null);

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'http://api.example.com/api/test', Mockery::any())
            ->andReturn($targetResponse);

        $this->cacheManager->shouldReceive('put')
            ->once()
            ->with($request, Mockery::any(), 300);

        $this->logger->shouldReceive('logRequest')
            ->once()
            ->with($request);

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('response data', $response->getContent());
    }

    public function test_handles_server_error()
    {
        $request = Request::create('/api/test', 'GET');
        
        $this->rateLimiter->shouldReceive('check')
            ->once()
            ->with($request)
            ->andThrow(new \Exception('Server error'));

        $this->logger->shouldReceive('logRequest')
            ->once()
            ->with($request);

        $this->logger->shouldReceive('logError')
            ->once()
            ->with(Mockery::type(\Exception::class));

        $response = $this->controller->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Internal server error',
            'code' => 500
        ], json_decode($response->getContent(), true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 