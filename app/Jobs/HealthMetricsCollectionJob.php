<?php

namespace App\Jobs;

use App\Models\HealthCheck;
use App\Services\MetricService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HealthMetricsCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $healthCheck;
    protected $retryCount = 0;
    protected $maxRetries = 3;

    public function __construct(HealthCheck $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    public function handle(MetricService $metricService): void
    {
        try {
            $metrics = $metricService->collectMetrics($this->healthCheck->name);

            Log::info('Metrics collected', [
                'service' => $this->healthCheck->name,
                'metrics' => array_keys($metrics)
            ]);
        } catch (\Exception $e) {
            Log::error('Metrics collection failed', [
                'service' => $this->healthCheck->name,
                'error' => $e->getMessage(),
                'retries' => $this->retryCount
            ]);

            if ($this->retryCount < $this->maxRetries) {
                $this->retryCount++;
                $this->release(30); // Retry after 30 seconds
                return;
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Metrics collection job failed', [
            'service' => $this->healthCheck->name,
            'error' => $exception->getMessage(),
            'retries' => $this->retryCount
        ]);
    }

    public function tags(): array
    {
        return ['metrics', 'health-check', $this->healthCheck->name];
    }
} 