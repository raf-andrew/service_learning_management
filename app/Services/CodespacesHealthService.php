<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class CodespacesHealthService
{
    protected $logPath;
    protected $failuresPath;
    protected $completePath;

    public function __construct()
    {
        $this->logPath = base_path('.codespaces/log');
        $this->failuresPath = base_path('.codespaces/log/failures');
        $this->completePath = base_path('.codespaces/log/complete');

        $this->ensureDirectoriesExist();
    }

    protected function ensureDirectoriesExist(): void
    {
        foreach ([$this->logPath, $this->failuresPath, $this->completePath] as $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    public function checkServiceHealth(string $service): array
    {
        $config = Config::get("codespaces.services.{$service}");
        if (!$config) {
            return $this->logFailure($service, "Service configuration not found");
        }

        if (!$config['enabled']) {
            return $this->logFailure($service, "Service is disabled");
        }

        $healthCheck = $config['health_check'] ?? [];
        if (empty($healthCheck)) {
            return $this->logFailure($service, "Health check configuration not found");
        }

        $result = $this->performHealthCheck($service, $healthCheck);
        
        if ($result['healthy']) {
            $this->logSuccess($service, $result);
        } else {
            $this->logFailure($service, $result['message']);
        }

        return $result;
    }

    protected function performHealthCheck(string $service, array $config): array
    {
        $host = Config::get("codespaces.services.{$service}.host");
        $port = Config::get("codespaces.services.{$service}.port");

        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, $config['timeout'] ?? 5);
            
            if (!$socket) {
                return [
                    'healthy' => false,
                    'message' => "Connection failed: {$errstr} ({$errno})",
                    'timestamp' => now()->toIso8601String(),
                    'service' => $service
                ];
            }

            fclose($socket);

            return [
                'healthy' => true,
                'message' => 'Service is healthy',
                'timestamp' => now()->toIso8601String(),
                'service' => $service
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => "Health check failed: {$e->getMessage()}",
                'timestamp' => now()->toIso8601String(),
                'service' => $service
            ];
        }
    }

    protected function logSuccess(string $service, array $result): void
    {
        $timestamp = now()->format('Ymd-His');
        $logFile = "{$this->completePath}/health-{$service}-{$timestamp}.json";
        
        File::put($logFile, json_encode($result, JSON_PRETTY_PRINT));
        
        Log::channel('codespaces')->info("Service {$service} is healthy", $result);
    }

    protected function logFailure(string $service, string $message): array
    {
        $timestamp = now()->format('Ymd-His');
        $result = [
            'healthy' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'service' => $service
        ];

        $logFile = "{$this->failuresPath}/health-{$service}-{$timestamp}.json";
        File::put($logFile, json_encode($result, JSON_PRETTY_PRINT));

        Log::channel('codespaces')->error("Service {$service} is unhealthy: {$message}", $result);

        return $result;
    }

    public function checkAllServices(): array
    {
        $results = [];
        $services = Config::get('codespaces.services', []);

        foreach ($services as $service => $config) {
            if ($config['enabled'] ?? false) {
                $results[$service] = $this->checkServiceHealth($service);
            }
        }

        return $results;
    }

    public function cleanupOldLogs(int $days = 7): void
    {
        $cutoff = now()->subDays($days);
        
        foreach ([$this->failuresPath, $this->completePath] as $path) {
            $files = File::files($path);
            
            foreach ($files as $file) {
                if (File::lastModified($file) < $cutoff->timestamp) {
                    File::delete($file);
                }
            }
        }
    }
} 