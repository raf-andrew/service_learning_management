<?php

namespace Tests;

use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\AuthorizationMiddleware;
use App\Http\Middleware\CachingMiddleware;
use App\Http\Middleware\CompressionMiddleware;
use App\Http\Middleware\CsrfProtectionMiddleware;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\PermissionBasedAccessControlMiddleware;
use App\Http\Middleware\RateLimitingMiddleware;
use App\Http\Middleware\RequestLoggingMiddleware;
use App\Http\Middleware\ResponseTimeTrackingMiddleware;
use App\Http\Middleware\RoleBasedAccessControlMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SqlInjectionProtectionMiddleware;
use App\Http\Middleware\XssProtectionMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class MiddlewarePerformanceTest extends TestCase
{
    private $middlewareChain;
    private $request;
    private $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
        
        // Initialize middleware chain in the correct order
        $this->middlewareChain = [
            new RequestLoggingMiddleware(),
            new RateLimitingMiddleware(),
            new CsrfProtectionMiddleware(),
            new AuthenticationMiddleware(),
            new AuthorizationMiddleware(),
            new RoleBasedAccessControlMiddleware(),
            new PermissionBasedAccessControlMiddleware(),
            new InputSanitizationMiddleware(),
            new XssProtectionMiddleware(),
            new SqlInjectionProtectionMiddleware(),
            new SecurityHeadersMiddleware(),
            new CachingMiddleware(),
            new CompressionMiddleware(),
            new ResponseTimeTrackingMiddleware()
        ];
    }

    public function testMiddlewareChainPerformance()
    {
        $iterations = 100;
        $totalTime = 0;
        $memoryUsage = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $response = new Response('test content');
            $next = function ($request) use ($response) {
                return $response;
            };

            $result = $this->executeMiddlewareChain($this->request, $next);

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $totalTime += ($endTime - $startTime);
            $memoryUsage[] = $endMemory - $startMemory;
        }

        $averageTime = $totalTime / $iterations;
        $averageMemory = array_sum($memoryUsage) / count($memoryUsage);

        // Performance assertions
        $this->assertLessThan(0.1, $averageTime, 'Average response time should be less than 100ms');
        $this->assertLessThan(1024 * 1024, $averageMemory, 'Average memory usage should be less than 1MB');
    }

    public function testIndividualMiddlewarePerformance()
    {
        foreach ($this->middlewareChain as $middleware) {
            $iterations = 50;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                $response = new Response('test content');
                $next = function ($request) use ($response) {
                    return $response;
                };

                $middleware->handle($this->request, $next);

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime);
            }

            $averageTime = $totalTime / $iterations;
            $this->performanceMetrics[get_class($middleware)] = $averageTime;

            // Individual middleware performance assertions
            $this->assertLessThan(0.05, $averageTime, 
                sprintf('%s should process in less than 50ms', get_class($middleware)));
        }
    }

    public function testCachingMiddlewarePerformance()
    {
        $iterations = 100;
        $totalTimeWithoutCache = 0;
        $totalTimeWithCache = 0;

        // Test without cache
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = new Response('test content');
            $next = function ($request) use ($response) {
                return $response;
            };

            $this->executeMiddlewareChain($this->request, $next);

            $endTime = microtime(true);
            $totalTimeWithoutCache += ($endTime - $startTime);
        }

        // Test with cache
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = new Response('test content');
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $next = function ($request) use ($response) {
                return $response;
            };

            $this->executeMiddlewareChain($this->request, $next);

            $endTime = microtime(true);
            $totalTimeWithCache += ($endTime - $startTime);
        }

        $averageTimeWithoutCache = $totalTimeWithoutCache / $iterations;
        $averageTimeWithCache = $totalTimeWithCache / $iterations;

        // Verify caching improves performance
        $this->assertLessThan($averageTimeWithoutCache, $averageTimeWithCache,
            'Cached responses should be faster than non-cached responses');
    }

    public function testCompressionMiddlewarePerformance()
    {
        $iterations = 50;
        $totalTimeWithoutCompression = 0;
        $totalTimeWithCompression = 0;
        $largeContent = str_repeat('test content', 1000);

        // Test without compression
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = new Response($largeContent);
            $next = function ($request) use ($response) {
                return $response;
            };

            $this->executeMiddlewareChain($this->request, $next);

            $endTime = microtime(true);
            $totalTimeWithoutCompression += ($endTime - $startTime);
        }

        // Test with compression
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = new Response($largeContent);
            $response->headers->set('Accept-Encoding', 'gzip');
            $next = function ($request) use ($response) {
                return $response;
            };

            $this->executeMiddlewareChain($this->request, $next);

            $endTime = microtime(true);
            $totalTimeWithCompression += ($endTime - $startTime);
        }

        $averageTimeWithoutCompression = $totalTimeWithoutCompression / $iterations;
        $averageTimeWithCompression = $totalTimeWithCompression / $iterations;

        // Verify compression improves performance for large responses
        $this->assertLessThan($averageTimeWithoutCompression, $averageTimeWithCompression,
            'Compressed responses should be faster for large content');
    }

    public function testConcurrentRequestPerformance()
    {
        $iterations = 10;
        $concurrentRequests = 5;
        $totalTime = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            // Simulate concurrent requests
            $promises = [];
            for ($j = 0; $j < $concurrentRequests; $j++) {
                $response = new Response('test content');
                $next = function ($request) use ($response) {
                    return $response;
                };

                $this->executeMiddlewareChain($this->request, $next);
            }

            $endTime = microtime(true);
            $totalTime += ($endTime - $startTime);
        }

        $averageTime = $totalTime / $iterations;

        // Verify concurrent request performance
        $this->assertLessThan(0.5, $averageTime,
            'Average time for concurrent requests should be less than 500ms');
    }

    public function testMemoryUsageUnderLoad()
    {
        $iterations = 1000;
        $memoryUsage = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startMemory = memory_get_usage();

            $response = new Response('test content');
            $next = function ($request) use ($response) {
                return $response;
            };

            $this->executeMiddlewareChain($this->request, $next);

            $endMemory = memory_get_usage();
            $memoryUsage[] = $endMemory - $startMemory;
        }

        $averageMemory = array_sum($memoryUsage) / count($memoryUsage);
        $maxMemory = max($memoryUsage);

        // Verify memory usage under load
        $this->assertLessThan(1024 * 1024, $averageMemory,
            'Average memory usage should be less than 1MB');
        $this->assertLessThan(5 * 1024 * 1024, $maxMemory,
            'Maximum memory usage should be less than 5MB');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Output performance metrics
        echo "\nMiddleware Performance Metrics:\n";
        foreach ($this->performanceMetrics as $middleware => $time) {
            echo sprintf("%s: %.4f seconds\n", $middleware, $time);
        }
    }

    private function executeMiddlewareChain(Request $request, callable $next)
    {
        $chain = $next;
        
        // Build the middleware chain in reverse order
        foreach (array_reverse($this->middlewareChain) as $middleware) {
            $chain = function ($request) use ($middleware, $chain) {
                return $middleware->handle($request, $chain);
            };
        }
        
        return $chain($request);
    }
} 