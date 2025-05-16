<?php

namespace App\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Event;
use App\Events\BaseEvent;

class EventMonitor
{
    protected $redis;
    protected $metrics = [
        'total_events' => 0,
        'failed_events' => 0,
        'processing_time' => 0,
        'memory_usage' => 0
    ];

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Start monitoring an event
     *
     * @param BaseEvent $event
     * @return void
     */
    public function startMonitoring(BaseEvent $event)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        Event::listen(get_class($event), function ($event) use ($startTime, $startMemory) {
            $this->recordMetrics($event, $startTime, $startMemory);
        });
    }

    /**
     * Record metrics for an event
     *
     * @param BaseEvent $event
     * @param float $startTime
     * @param int $startMemory
     * @return void
     */
    protected function recordMetrics(BaseEvent $event, float $startTime, int $startMemory)
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $processingTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        $this->metrics['total_events']++;
        $this->metrics['processing_time'] += $processingTime;
        $this->metrics['memory_usage'] += $memoryUsage;

        $this->storeMetrics($event, $processingTime, $memoryUsage);
    }

    /**
     * Store metrics in Redis
     *
     * @param BaseEvent $event
     * @param float $processingTime
     * @param int $memoryUsage
     * @return void
     */
    protected function storeMetrics(BaseEvent $event, float $processingTime, int $memoryUsage)
    {
        $key = 'event_metrics:' . get_class($event);
        $data = [
            'timestamp' => now()->timestamp,
            'processing_time' => $processingTime,
            'memory_usage' => $memoryUsage
        ];

        $this->redis->hset($key, now()->timestamp, json_encode($data));
        $this->redis->expire($key, 86400); // Store for 24 hours
    }

    /**
     * Record a failed event
     *
     * @param BaseEvent $event
     * @param \Throwable $exception
     * @return void
     */
    public function recordFailure(BaseEvent $event, \Throwable $exception)
    {
        $this->metrics['failed_events']++;

        Log::error('Event failed', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->redis->hset(
            'event_failures:' . get_class($event),
            now()->timestamp,
            json_encode([
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ])
        );
    }

    /**
     * Get current metrics
     *
     * @return array
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * Get event metrics from Redis
     *
     * @param string $eventClass
     * @return array
     */
    public function getEventMetrics(string $eventClass)
    {
        $key = 'event_metrics:' . $eventClass;
        $data = $this->redis->hgetall($key);

        return collect($data)->map(function ($value) {
            return json_decode($value, true);
        })->toArray();
    }

    /**
     * Get event failures from Redis
     *
     * @param string $eventClass
     * @return array
     */
    public function getEventFailures(string $eventClass)
    {
        $key = 'event_failures:' . $eventClass;
        $data = $this->redis->hgetall($key);

        return collect($data)->map(function ($value) {
            return json_decode($value, true);
        })->toArray();
    }

    /**
     * Clear old metrics
     *
     * @return void
     */
    public function clearOldMetrics()
    {
        $keys = $this->redis->keys('event_metrics:*');
        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $keys = $this->redis->keys('event_failures:*');
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
    }

    /**
     * Get average processing time
     *
     * @return float
     */
    public function getAverageProcessingTime()
    {
        if ($this->metrics['total_events'] === 0) {
            return 0;
        }

        return $this->metrics['processing_time'] / $this->metrics['total_events'];
    }

    /**
     * Get average memory usage
     *
     * @return float
     */
    public function getAverageMemoryUsage()
    {
        if ($this->metrics['total_events'] === 0) {
            return 0;
        }

        return $this->metrics['memory_usage'] / $this->metrics['total_events'];
    }

    /**
     * Get failure rate
     *
     * @return float
     */
    public function getFailureRate()
    {
        if ($this->metrics['total_events'] === 0) {
            return 0;
        }

        return ($this->metrics['failed_events'] / $this->metrics['total_events']) * 100;
    }
} 