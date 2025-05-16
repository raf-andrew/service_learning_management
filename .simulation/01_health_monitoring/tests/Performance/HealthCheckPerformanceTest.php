<?php

namespace Tests\Performance;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

class HealthCheckPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an API key for testing
        $this->apiKey = ApiKey::create([
            'name' => 'Performance Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'permissions' => ['health:check', 'health:status']
        ]);

        // Disable actual job processing and event dispatching
        Queue::fake();
        Event::fake();
    }

    public function test_health_check_endpoint_performance()
    {
        $startTime = microtime(true);
        $iterations = 100;
        $successCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->withHeaders([
                'X-API-Key' => $this->apiKey->key
            ])->postJson('/api/health/check', [
                'service_name' => 'test-service',
                'type' => 'http',
                'target' => 'http://example.com'
            ]);

            if ($response->status() === 200) {
                $successCount++;
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $averageTime = $executionTime / $iterations;
        $successRate = ($successCount / $iterations) * 100;

        // Assert performance metrics
        $this->assertLessThan(0.5, $averageTime, 'Average response time should be less than 500ms');
        $this->assertEquals(100, $successRate, 'Success rate should be 100%');
        $this->assertLessThan(2.0, $executionTime, 'Total execution time should be less than 2 seconds');

        // Verify job dispatching
        Queue::assertPushed(HealthCheckJob::class, $iterations);
    }

    public function test_concurrent_health_checks()
    {
        $startTime = microtime(true);
        $concurrentRequests = 50;
        $successCount = 0;

        $promises = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $promises[] = $this->withHeaders([
                'X-API-Key' => $this->apiKey->key
            ])->postJsonAsync('/api/health/check', [
                'service_name' => "test-service-{$i}",
                'type' => 'http',
                'target' => 'http://example.com'
            ]);
        }

        $responses = \GuzzleHttp\Promise\Utils::unwrap($promises);
        foreach ($responses as $response) {
            if ($response->status() === 200) {
                $successCount++;
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $successRate = ($successCount / $concurrentRequests) * 100;

        // Assert concurrent performance metrics
        $this->assertLessThan(3.0, $executionTime, 'Concurrent requests should complete within 3 seconds');
        $this->assertEquals(100, $successRate, 'Concurrent requests should have 100% success rate');

        // Verify job dispatching
        Queue::assertPushed(HealthCheckJob::class, $concurrentRequests);
    }

    public function test_health_status_endpoint_performance()
    {
        $startTime = microtime(true);
        $iterations = 100;
        $successCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->withHeaders([
                'X-API-Key' => $this->apiKey->key
            ])->getJson('/api/health/status');

            if ($response->status() === 200) {
                $successCount++;
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $averageTime = $executionTime / $iterations;
        $successRate = ($successCount / $iterations) * 100;

        // Assert performance metrics
        $this->assertLessThan(0.2, $averageTime, 'Average response time should be less than 200ms');
        $this->assertEquals(100, $successRate, 'Success rate should be 100%');
        $this->assertLessThan(1.0, $executionTime, 'Total execution time should be less than 1 second');
    }

    public function test_health_metrics_endpoint_performance()
    {
        $startTime = microtime(true);
        $iterations = 100;
        $successCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->withHeaders([
                'X-API-Key' => $this->apiKey->key
            ])->getJson('/api/health/metrics');

            if ($response->status() === 200) {
                $successCount++;
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $averageTime = $executionTime / $iterations;
        $successRate = ($successCount / $iterations) * 100;

        // Assert performance metrics
        $this->assertLessThan(0.3, $averageTime, 'Average response time should be less than 300ms');
        $this->assertEquals(100, $successRate, 'Success rate should be 100%');
        $this->assertLessThan(1.5, $executionTime, 'Total execution time should be less than 1.5 seconds');
    }
} 