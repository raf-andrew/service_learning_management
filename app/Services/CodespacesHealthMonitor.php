<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class CodespacesHealthMonitor
{
    protected $serviceManager;
    protected $logPath;
    protected $healthChecks = [];

    public function __construct(CodespacesServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->logPath = storage_path('logs/codespaces/health');
        $this->initializeHealthChecks();
    }

    protected function initializeHealthChecks()
    {
        $this->healthChecks = [
            'database' => function() {
                try {
                    $config = $this->serviceManager->getServiceConfig('database');
                    if (!$config) {
                        return false;
                    }

                    $dsn = sprintf(
                        'mysql:host=%s;port=%s;dbname=%s',
                        $config['env']['DB_HOST'],
                        $config['env']['DB_PORT'],
                        $config['env']['DB_DATABASE']
                    );

                    $pdo = new \PDO(
                        $dsn,
                        $config['env']['DB_USERNAME'],
                        $config['env']['DB_PASSWORD']
                    );
                    return $pdo->query('SELECT 1')->fetch() !== false;
                } catch (\Exception $e) {
                    $this->logFailure('database', $e->getMessage());
                    return false;
                }
            },
            'redis' => function() {
                try {
                    $config = $this->serviceManager->getServiceConfig('redis');
                    if (!$config) {
                        return false;
                    }

                    $redis = new \Redis();
                    $redis->connect(
                        $config['env']['REDIS_HOST'],
                        $config['env']['REDIS_PORT']
                    );
                    return $redis->ping() === 'PONG';
                } catch (\Exception $e) {
                    $this->logFailure('redis', $e->getMessage());
                    return false;
                }
            },
            'mail' => function() {
                try {
                    $config = $this->serviceManager->getServiceConfig('mail');
                    if (!$config) {
                        return false;
                    }

                    $transport = new \Swift_SmtpTransport(
                        $config['env']['MAIL_HOST'],
                        $config['env']['MAIL_PORT']
                    );
                    return $transport->start() !== false;
                } catch (\Exception $e) {
                    $this->logFailure('mail', $e->getMessage());
                    return false;
                }
            }
        ];
    }

    public function checkServiceHealth(string $serviceName): array
    {
        if (!isset($this->healthChecks[$serviceName])) {
            return [
                'healthy' => false,
                'error' => "No health check defined for service: {$serviceName}"
            ];
        }

        $healthy = $this->healthChecks[$serviceName]();
        $result = [
            'healthy' => $healthy,
            'timestamp' => now()->toIso8601String(),
            'service' => $serviceName
        ];

        if (!$healthy) {
            $result['error'] = "Service {$serviceName} is not healthy";
        }

        $this->logHealthCheck($result);
        return $result;
    }

    public function checkAllServices(): array
    {
        $results = [];
        foreach ($this->healthChecks as $service => $check) {
            $results[$service] = $this->checkServiceHealth($service);
        }
        return $results;
    }

    public function healService(string $serviceName): bool
    {
        $config = $this->serviceManager->getServiceConfig($serviceName);
        if (!$config) {
            return false;
        }

        // Deactivate the service first
        $this->serviceManager->deactivateService($serviceName);

        // Attempt to heal based on service type
        switch ($serviceName) {
            case 'database':
                return $this->healDatabase($config);
            case 'redis':
                return $this->healRedis($config);
            case 'mail':
                return $this->healMail($config);
            default:
                return false;
        }
    }

    protected function healDatabase(array $config): bool
    {
        try {
            // Attempt to recreate database
            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s', $config['env']['DB_HOST'], $config['env']['DB_PORT']),
                $config['env']['DB_USERNAME'],
                $config['env']['DB_PASSWORD']
            );
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['env']['DB_DATABASE']}");
            return true;
        } catch (\Exception $e) {
            $this->logFailure('database', "Healing failed: " . $e->getMessage());
            return false;
        }
    }

    protected function healRedis(array $config): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect($config['env']['REDIS_HOST'], $config['env']['REDIS_PORT']);
            $redis->flushAll(); // Reset Redis to a clean state
            return true;
        } catch (\Exception $e) {
            $this->logFailure('redis', "Healing failed: " . $e->getMessage());
            return false;
        }
    }

    protected function healMail(array $config): bool
    {
        try {
            $transport = new \Swift_SmtpTransport(
                $config['env']['MAIL_HOST'],
                $config['env']['MAIL_PORT']
            );
            return $transport->start() !== false;
        } catch (\Exception $e) {
            $this->logFailure('mail', "Healing failed: " . $e->getMessage());
            return false;
        }
    }

    protected function logFailure(string $service, string $error): void
    {
        $logEntry = sprintf(
            "[%s] Service %s failed: %s",
            now()->toIso8601String(),
            $service,
            $error
        );

        Log::channel('codespaces')->error($logEntry);
    }

    protected function logHealthCheck(array $result): void
    {
        $logEntry = sprintf(
            "[%s] Health check for %s: %s",
            $result['timestamp'],
            $result['service'],
            $result['healthy'] ? 'HEALTHY' : 'UNHEALTHY'
        );

        if (!$result['healthy'] && isset($result['error'])) {
            $logEntry .= " - {$result['error']}";
        }

        Log::channel('codespaces')->info($logEntry);
    }
} 