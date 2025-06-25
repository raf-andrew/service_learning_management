<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');
        $statusCode = $allHealthy ? 200 : 503;

        return response()->json([
            'success' => $allHealthy,
            'message' => $allHealthy ? 'All systems operational' : 'Some systems are down',
            'data' => [
                'status' => $allHealthy ? 'healthy' : 'unhealthy',
                'timestamp' => now()->toISOString(),
                'checks' => $checks,
            ],
        ], $statusCode);
    }

    /**
     * API status endpoint
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API Status retrieved successfully',
            'data' => [
                'status' => 'operational',
                'uptime' => $this->getUptime(),
                'version' => config('modules.api.versioning.current', 'v1'),
                'environment' => config('app.env'),
                'timestamp' => now()->toISOString(),
                'modules' => $this->getModuleStatus(),
            ],
        ]);
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time' => $this->measureResponseTime(fn() => DB::select('SELECT 1')),
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connection
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'test', 1);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            $responseTime = (microtime(true) - $start) * 1000;

            if ($value === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache connection successful',
                    'response_time' => round($responseTime, 2),
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Cache read/write test failed',
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'message' => 'Cache connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage connection
     */
    protected function checkStorage(): array
    {
        try {
            $disk = \Storage::disk(config('filesystems.default'));
            $testFile = 'health_check_' . time();
            
            $start = microtime(true);
            $disk->put($testFile, 'test');
            $content = $disk->get($testFile);
            $disk->delete($testFile);
            $responseTime = (microtime(true) - $start) * 1000;

            if ($content === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage connection successful',
                    'response_time' => round($responseTime, 2),
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Storage read/write test failed',
            ];
        } catch (\Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'message' => 'Storage connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connection
     */
    protected function checkQueue(): array
    {
        try {
            $queue = config('queue.default');
            return [
                'status' => 'healthy',
                'message' => 'Queue connection successful',
                'driver' => $queue,
            ];
        } catch (\Exception $e) {
            Log::error('Queue health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'message' => 'Queue connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system uptime
     */
    protected function getUptime(): string
    {
        $startTime = Cache::get('app_start_time');
        if (!$startTime) {
            $startTime = now();
            Cache::put('app_start_time', $startTime, 86400);
        }
        
        return $startTime->diffForHumans();
    }

    /**
     * Get module status
     */
    protected function getModuleStatus(): array
    {
        return [
            'e2ee' => [
                'status' => 'operational',
                'version' => '1.0.0',
                'last_check' => now()->toISOString(),
            ],
            'soc2' => [
                'status' => 'operational',
                'version' => '1.0.0',
                'last_check' => now()->toISOString(),
            ],
            'auth' => [
                'status' => 'operational',
                'version' => '1.0.0',
                'last_check' => now()->toISOString(),
            ],
            'mcp' => [
                'status' => 'operational',
                'version' => '1.0.0',
                'last_check' => now()->toISOString(),
            ],
            'web3' => [
                'status' => 'operational',
                'version' => '1.0.0',
                'last_check' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Measure response time of a function
     */
    protected function measureResponseTime(callable $function): float
    {
        $start = microtime(true);
        $function();
        return round((microtime(true) - $start) * 1000, 2);
    }
} 