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

class HealthCheckEnduranceTest extends TestCase
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
            'name' => 'Endurance Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'permissions' => ['health:check', 'health:status', 'health:metrics']
        ]);

        // Create test services
        for ($i = 0; $i < 20; $i++) {
            $this->testServices[] = HealthCheck::create([
                'name' => "endurance-test-service-{$i}",
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

    public function test_long_running_health_checks()
    {
        $duration = 300; // 5 minutes test
        $interval = 5; // 5 seconds between checks
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        $currentTime = $startTime;
        $successCount = 0;
        $totalRequests = 0;
        $errors = [];

        while ($currentTime < $endTime) {
            $batchStart = microtime(true);
            
            // Perform health checks for all services
            foreach ($this->testServices as $service) {
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
                        $errors[] = "Request failed with status {$response->status()} for service {$service->name}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Exception for service {$service->name}: " . $e->getMessage();
                }

                $totalRequests++;
            }

            // Record metrics
            $this->recordMetrics($batchStart);

            // Calculate sleep time
            $batchTime = microtime(true) - $batchStart;
            $sleepTime = max(0, $interval - $batchTime);
            usleep($sleepTime * 1000000);

            $currentTime = microtime(true);
        }

        $executionTime = $currentTime - $startTime;
        $successRate = ($successCount / $totalRequests) * 100;

        // Assert endurance test metrics
        $this->assertGreaterThanOrEqual(95, $successRate, 'Success rate should be at least 95%');
        $this->assertLessThan(10, count($errors), 'Should have less than 10 errors');
        $this->assertLessThan(0.2, $this->getAverageQueryTime(), 'Average query time should be less than 200ms');

        // Verify system stability
        $this->assertLessThan(70, $this->getAverageCpuUsage(), 'Average CPU usage should be less than 70%');
        $this->assertLessThan(70, $this->getAverageMemoryUsage(), 'Average memory usage should be less than 70%');
        $this->assertLessThan(1000, $this->getAverageResponseTime(), 'Average response time should be less than 1 second');

        // Log test results
        Log::info('Endurance test completed', [
            'duration' => $executionTime,
            'total_requests' => $totalRequests,
            'success_rate' => $successRate,
            'error_count' => count($errors),
            'average_response_time' => $this->getAverageResponseTime(),
            'average_cpu_usage' => $this->getAverageCpuUsage(),
            'average_memory_usage' => $this->getAverageMemoryUsage()
        ]);
    }

    protected function recordMetrics($timestamp)
    {
        $this->metrics[] = [
            'timestamp' => $timestamp,
            'response_time' => $this->getAverageResponseTime(),
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
        foreach ($this->metrics as $metric) {
            $totalTime += $metric['response_time'];
        }

        return $totalTime / count($this->metrics);
    }

    protected function getAverageCpuUsage()
    {
        if (empty($this->metrics)) {
            return 0;
        }

        $totalUsage = 0;
        foreach ($this->metrics as $metric) {
            $totalUsage += $metric['cpu_usage'];
        }

        return $totalUsage / count($this->metrics);
    }

    protected function getAverageMemoryUsage()
    {
        if (empty($this->metrics)) {
            return 0;
        }

        $totalUsage = 0;
        foreach ($this->metrics as $metric) {
            $totalUsage += $metric['memory_usage'];
        }

        return $totalUsage / count($this->metrics);
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