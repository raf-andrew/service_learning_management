<?php

namespace App\Services;

use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    public function checkService(string $name, array $config = []): array
    {
        try {
            $healthCheck = HealthCheck::where('name', $name)->firstOrFail();
            
            $result = match ($healthCheck->type) {
                'http' => $this->checkHttpEndpoint($healthCheck->target, $config),
                'database' => $this->checkDatabaseConnection($healthCheck->target),
                'cache' => $this->checkCacheService($healthCheck->target),
                'queue' => $this->checkQueueService($healthCheck->target),
                default => $this->checkCustomService($healthCheck->target, $config),
            };

            // Store the result
            $this->storeHealthCheckResult($healthCheck, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function checkHttpEndpoint(string $url, array $config): array
    {
        $timeout = $config['timeout'] ?? 30;
        
        try {
            $response = Http::timeout($timeout)->get($url);
            
            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->handlerStats()['total_time'] ?? 0,
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function checkDatabaseConnection(string $connection): array
    {
        try {
            DB::connection($connection)->getPdo();
            
            return [
                'status' => 'healthy',
                'connection' => $connection
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function checkCacheService(string $store): array
    {
        try {
            $key = 'health_check_' . uniqid();
            $value = 'test';
            
            Cache::store($store)->put($key, $value, 1);
            $retrieved = Cache::store($store)->get($key);
            
            return [
                'status' => $retrieved === $value ? 'healthy' : 'unhealthy',
                'store' => $store
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function checkQueueService(string $connection): array
    {
        try {
            Queue::connection($connection)->size('default');
            
            return [
                'status' => 'healthy',
                'connection' => $connection
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function checkCustomService(string $target, array $config): array
    {
        // This method should be implemented based on custom service requirements
        return [
            'status' => 'unknown',
            'message' => 'Custom service check not implemented'
        ];
    }

    protected function storeHealthCheckResult(HealthCheck $healthCheck, array $result): void
    {
        HealthCheckResult::create([
            'health_check_id' => $healthCheck->id,
            'status' => $result['status'],
            'details' => $result,
            'checked_at' => now()
        ]);
    }
} 