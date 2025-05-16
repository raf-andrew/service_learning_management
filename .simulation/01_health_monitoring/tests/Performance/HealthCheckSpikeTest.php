<?php

namespace Tests\Performance;

use App\Models\ApiKey;
use App\Models\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckSpikeTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;
    protected $testServices = [];
    protected $metrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an API key for testing
        $this->apiKey = ApiKey::create([
            'name' => 'Spike Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'permissions' => ['health:check', 'health:status', 'health:metrics']
        ]);

        // Create test services
        for ($i = 0; $i < 100; $i++) {
            $this->testServices[] = HealthCheck::create([
                'name' => "spike-test-service-{$i}",
                'type' => 'http',
                'target' => 'http://example.com',
                'is_active' => true
            ]);
        }

        // Enable query logging
        DB::enableQueryLog();

        // Disable actual job processing and event dispatching
        Queue::fake();
        Event::fake();
    }

    public function test_traffic_spike_handling()
    {
        $startTime = microtime(true);
        $spikeDuration = 30; // 30 seconds spike
        $endTime = $startTime + $spikeDuration;
        $currentTime = $startTime;
        $successCount = 0;
        $totalRequests = 0;
        $errors = [];
        $responseTimes = [];

        while ($currentTime < $endTime) {
            $batchStart = microtime(true);
            
            // Simulate a sudden spike in traffic
            $batchSize = $this->calculateBatchSize($currentTime - $startTime);
            
            // Send a batch of requests
            $promises = [];
            for ($i = 0; $i < $batchSize; $i++) {
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
                    $responseTimes[] = $response->getTime();
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            $totalRequests += $batchSize;

            // Record metrics
            $this->recordMetrics($batchStart, $responseTimes);

            // Calculate sleep time to maintain spike pattern
            $batchTime = microtime(true) - $batchStart;
            $sleepTime = max(0, 0.1 - $batchTime); // 100ms between batches
            usleep($sleepTime * 1000000);

            $currentTime = microtime(true);
        }

        $executionTime = $currentTime - $startTime;
        $successRate = ($successCount / $totalRequests) * 100;
        $averageResponseTime = $this->getAverageResponseTime();

        // Assert spike test metrics
        $this->assertGreaterThanOrEqual(90, $successRate, 'Success rate should be at least 90%');
        $this->assertLessThan(20, count($errors), 'Should have less than 20 errors');
        $this->assertLessThan(2000, $averageResponseTime, 'Average response time should be less than 2 seconds');

        // Verify system recovery
        $this->assertLessThan(80, $this->getCpuUsage(), 'CPU usage should be less than 80%');
        $this->assertLessThan(80, $this->getMemoryUsage(), 'Memory usage should be less than 80%');

        // Log test results
        Log::info('Spike test completed', [
            'duration' => $executionTime,
            'total_requests' => $totalRequests,
            'success_rate' => $successRate,
            'error_count' => count($errors),
            'average_response_time' => $averageResponseTime,
            'peak_cpu_usage' => $this->getPeakCpuUsage(),
            'peak_memory_usage' => $this->getPeakMemoryUsage()
        ]);
    }

    protected function calculateBatchSize($elapsedTime)
    {
        // Simulate a traffic spike pattern
        $baseSize = 50;
        $spikeFactor = sin(($elapsedTime / 30) * M_PI) + 1; // Oscillate between 0 and 2
        return (int)($baseSize * $spikeFactor);
    }

    protected function recordMetrics($timestamp, $responseTimes)
    {
        $this->metrics[] = [
            'timestamp' => $timestamp,
            'response_times' => $responseTimes,
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'query_time' => $this->getAverageQueryTime()
        ];
    }

    protected function getAverageResponseTime()
    {
        if (empty($this->metrics)) {
            return 0;
        }

        $totalTime = 0;
        $count = 0;
        foreach ($this->metrics as $metric) {
            foreach ($metric['response_times'] as $time) {
                $totalTime += $time;
                $count++;
            }
        }

        return $count > 0 ? $totalTime / $count : 0;
    }

    protected function getPeakCpuUsage()
    {
        if (empty($this->metrics)) {
            return 0;
        }

        $peak = 0;
        foreach ($this->metrics as $metric) {
            $peak = max($peak, $metric['cpu_usage']);
        }

        return $peak;
    }

    protected function getPeakMemoryUsage()
    {
        if (empty($this->metrics)) {
            return 0;
        }

        $peak = 0;
        foreach ($this->metrics as $metric) {
            $peak = max($peak, $metric['memory_usage']);
        }

        return $peak;
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