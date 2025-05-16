<?php

namespace Tests\Feature;

use App\Services\AuthenticationService;
use App\Services\CacheManager;
use App\Services\LoggingService;
use App\Services\RateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ApiGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected $authService;
    protected $rateLimitService;
    protected $cacheManager;
    protected $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authService = $this->app->make(AuthenticationService::class);
        $this->rateLimitService = $this->app->make(RateLimitService::class);
        $this->cacheManager = $this->app->make(CacheManager::class);
        $this->loggingService = $this->app->make(LoggingService::class);
    }

    public function test_complete_request_flow_with_authentication()
    {
        // Create test API key
        $apiKey = $this->authService->generateApiKey('test-client');

        // Create test request
        $request = Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_X-API-Key' => $apiKey]
        );

        // Test authentication
        $this->assertTrue($this->authService->validateRequest($request));

        // Test rate limiting
        $this->assertTrue($this->rateLimitService->checkLimit($request));

        // Test request logging
        $this->loggingService->logRequest($request);

        // Create test response
        $response = new Response('Test response', 200);

        // Test response caching
        $this->cacheManager->cacheResponse($request, $response);

        // Test response logging
        $this->loggingService->logResponse($request, $response);

        // Test access logging
        $this->loggingService->logAccess($request, $response);

        // Verify cached response
        $cachedResponse = $this->cacheManager->getCachedResponse($request);
        $this->assertNotNull($cachedResponse);
        $this->assertEquals(200, $cachedResponse->status());
    }

    public function test_error_handling_scenarios()
    {
        // Test invalid API key
        $request = Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_X-API-Key' => 'invalid-key']
        );

        $this->assertFalse($this->authService->validateRequest($request));

        // Test rate limit exceeded
        $apiKey = $this->authService->generateApiKey('test-client');
        $request = Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_X-API-Key' => $apiKey]
        );

        // Exceed rate limit
        for ($i = 0; $i < 100; $i++) {
            $this->rateLimitService->checkLimit($request);
        }

        $this->assertFalse($this->rateLimitService->checkLimit($request));

        // Test error logging
        $error = new \Exception('Test error');
        $this->loggingService->logError($request, $error);
    }

    public function test_rate_limiting_integration()
    {
        $apiKey = $this->authService->generateApiKey('test-client');
        $request = Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_X-API-Key' => $apiKey]
        );

        // Test rate limit headers
        $this->rateLimitService->checkLimit($request);
        $headers = $this->rateLimitService->getRateLimitHeaders($request);
        
        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    public function test_caching_integration()
    {
        $request = Request::create('/api/test', 'GET');
        $response = new Response('Test response', 200);

        // Test cache headers
        $this->cacheManager->addCacheHeaders($response);
        $headers = $response->headers->all();

        $this->assertArrayHasKey('cache-control', $headers);
        $this->assertArrayHasKey('expires', $headers);

        // Test cache invalidation
        $this->cacheManager->cacheResponse($request, $response);
        $this->cacheManager->invalidateCache('/api/test');
        
        $cachedResponse = $this->cacheManager->getCachedResponse($request);
        $this->assertNull($cachedResponse);
    }

    public function test_authentication_integration()
    {
        // Test API key authentication
        $apiKey = $this->authService->generateApiKey('test-client');
        $request = Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_X-API-Key' => $apiKey]
        );

        $this->assertTrue($this->authService->validateRequest($request));

        // Test JWT authentication
        $token = $this->authService->generateJwt(['user_id' => 1]);
        $request = Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );

        $this->assertTrue($this->authService->validateRequest($request));
    }

    public function test_logging_integration()
    {
        $request = Request::create(
            '/api/test',
            'POST',
            ['username' => 'test', 'password' => 'secret'],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer token123']
        );

        $response = new Response('Test response', 200);

        // Test request logging with sensitive data
        $this->loggingService->logRequest($request);

        // Test response logging
        $this->loggingService->logResponse($request, $response);

        // Test error logging
        $error = new \Exception('Test error');
        $this->loggingService->logError($request, $error);

        // Test access logging
        $this->loggingService->logAccess($request, $response);
    }
} 