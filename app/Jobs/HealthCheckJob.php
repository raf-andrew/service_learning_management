<?php

namespace App\Jobs;

use App\Models\HealthCheck;
use App\Services\HealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $healthCheck;
    protected $retryCount = 0;

    public function __construct(HealthCheck $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    public function handle(HealthCheckService $healthCheckService): void
    {
        try {
            $result = $healthCheckService->checkService(
                $this->healthCheck->name,
                $this->healthCheck->config ?? []
            );

            if ($result['status'] === 'unhealthy' && $this->retryCount < $this->healthCheck->retry_attempts) {
                $this->retryCount++;
                $this->release($this->healthCheck->retry_delay);
                return;
            }

            Log::info('Health check completed', [
                'service' => $this->healthCheck->name,
                'status' => $result['status'],
                'retries' => $this->retryCount
            ]);
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'service' => $this->healthCheck->name,
                'error' => $e->getMessage(),
                'retries' => $this->retryCount
            ]);

            if ($this->retryCount < $this->healthCheck->retry_attempts) {
                $this->retryCount++;
                $this->release($this->healthCheck->retry_delay);
                return;
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Health check job failed', [
            'service' => $this->healthCheck->name,
            'error' => $exception->getMessage(),
            'retries' => $this->retryCount
        ]);
    }
} 