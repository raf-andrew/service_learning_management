<?php

namespace Tests\Performance;

use App\Models\ApiKey;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class HealthCheckStressTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;
    protected $testServices = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an API key for testing
        $this->apiKey = ApiKey::create([
            'name' => 'Stress Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'permissions' => ['health:check', 'health:status', 'health:metrics']
        ]);

        // Create test services
        for ($i = 0; $i < 50; $i++) {
            $this->testServices[] = HealthCheck::create([
                'name' => "stress-test-service-{$i}",
                'type' => 'http',
                'target' => 'http://example.com',
                'is_active' => true
            ]);
        }

        // Disable actual job processing and event dispatching
        Queue::fake();
        Event::fake();
    }

    public function test_sustained_load_performance()
    {
        $startTime = microtime(true);
        $duration = 60; // 1 minute test
        $requestsPerSecond = 10;
        $totalRequests = 0;
        $successCount = 0;
        $errors = [];

        while (microtime(true) - $startTime < $duration) {
            $batchStart = microtime(true);
            
            // Send a batch of requests
            for ($i = 0; $i < $requestsPerSecond; $i++) {
                $service = $this->testServices[array_rand($this->testServices)];
                
                try {
                    $response = $this->withHeaders([
                        'X-API-Key' => $this->apiKey->key
                    ])->postJson('/api/health/check', [
                        'service_name' => $service->name,
                        'type' => $service->type,
                        'target' => $service->target
                    ]);

                    if ($response->status() === 200) {
                        $successCount++;
                    } else {
                        $errors[] = "Request failed with status {$response->status()}";
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }

                $totalRequests++;
            }

            // Calculate sleep time to maintain requests per second
            $batchTime = microtime(true) - $batchStart;
            $sleepTime = max(0, (1 / $requestsPerSecond) - $batchTime);
            usleep($sleepTime * 1000000);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $actualRPS = $totalRequests / $executionTime;
        $successRate = ($successCount / $totalRequests) * 100;

        // Assert stress test metrics
        $this->assertGreaterThanOrEqual(9, $actualRPS, 'Should maintain at least 9 requests per second');
        $this->assertGreaterThanOrEqual(95, $successRate, 'Success rate should be at least 95%');
        $this->assertLessThan(5, count($errors), 'Should have less than 5 errors');

        // Verify database performance
        $this->assertLessThan(0.1, $this->getAverageQueryTime(), 'Average query time should be less than 100ms');
    }

    public function test_peak_load_handling()
    {
        $startTime = microtime(true);
        $peakRequests = 200;
        $successCount = 0;
        $errors = [];

        // Send a burst of requests
        $promises = [];
        for ($i = 0; $i < $peakRequests; $i++) {
            $service = $this->testServices[array_rand($this->testServices)];
            
            $promises[] = $this->withHeaders([
                'X-API-Key' => $this->apiKey->key
            ])->postJsonAsync('/api/health/check', [
                'service_name' => $service->name,
                'type' => $service->type,
                'target' => $service->target
            ]);
        }

        try {
            $responses = \GuzzleHttp\Promise\Utils::unwrap($promises);
            foreach ($responses as $response) {
                if ($response->status() === 200) {
                    $successCount++;
                } else {
                    $errors[] = "Request failed with status {$response->status()}";
                }
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $successRate = ($successCount / $peakRequests) * 100;

        // Assert peak load metrics
        $this->assertLessThan(5.0, $executionTime, 'Should handle peak load within 5 seconds');
        $this->assertGreaterThanOrEqual(90, $successRate, 'Success rate should be at least 90%');
        $this->assertLessThan(20, count($errors), 'Should have less than 20 errors');

        // Verify system resources
        $this->assertLessThan(80, $this->getCpuUsage(), 'CPU usage should be less than 80%');
        $this->assertLessThan(80, $this->getMemoryUsage(), 'Memory usage should be less than 80%');
    }

    protected function getAverageQueryTime()
    {
        $queries = DB::getQueryLog();
        if (empty($queries)) {
            return 0;
        }

        $totalTime = 0;
        foreach ($queries as $query) {
            $totalTime += $query['time'];
        }

        return $totalTime / count($queries);
    }

    protected function getCpuUsage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] * 100;
        }
        return 0;
    }

    protected function getMemoryUsage()
    {
        return memory_get_usage(true) / memory_get_peak_usage(true) * 100;
    }
} 