<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CodespacesLifecycleManager
{
    protected $configManager;
    protected $healthMonitor;
    protected $logPath;
    protected $maxRetries = 3;

    public function __construct(
        CodespacesConfigManager $configManager,
        CodespacesHealthMonitor $healthMonitor
    ) {
        $this->configManager = $configManager;
        $this->healthMonitor = $healthMonitor;
        $this->logPath = storage_path('logs/codespaces/lifecycle');
    }

    public function createService(string $serviceName, array $config): bool
    {
        try {
            // Save the configuration
            $this->configManager->saveServiceConfig($serviceName, $config);

            // Create service-specific resources
            switch ($serviceName) {
                case 'database':
                    return $this->createDatabase($config);
                case 'redis':
                    return $this->createRedis($config);
                case 'mail':
                    return $this->createMail($config);
                default:
                    throw new \InvalidArgumentException("Unknown service type: {$serviceName}");
            }
        } catch (\Exception $e) {
            $this->logError($serviceName, "Failed to create service: " . $e->getMessage());
            return false;
        }
    }

    public function teardownService(string $serviceName): bool
    {
        try {
            $config = $this->configManager->getServiceConfig($serviceName);
            if (!$config) {
                return false;
            }

            // Perform service-specific teardown
            switch ($serviceName) {
                case 'database':
                    return $this->teardownDatabase($config);
                case 'redis':
                    return $this->teardownRedis($config);
                case 'mail':
                    return $this->teardownMail($config);
                default:
                    throw new \InvalidArgumentException("Unknown service type: {$serviceName}");
            }
        } catch (\Exception $e) {
            $this->logError($serviceName, "Failed to teardown service: " . $e->getMessage());
            return false;
        }
    }

    public function rebuildService(string $serviceName): bool
    {
        $config = $this->configManager->getServiceConfig($serviceName);
        if (!$config) {
            return false;
        }

        // Teardown the service first
        if (!$this->teardownService($serviceName)) {
            return false;
        }

        // Wait for resources to be released
        sleep(2);

        // Recreate the service
        return $this->createService($serviceName, $config);
    }

    public function ensureServiceHealth(string $serviceName): bool
    {
        $retries = 0;
        while ($retries < $this->maxRetries) {
            $health = $this->healthMonitor->checkServiceHealth($serviceName);
            if ($health['healthy']) {
                return true;
            }

            $this->logWarning($serviceName, "Service unhealthy, attempt {$retries} of {$this->maxRetries}");
            
            if (!$this->rebuildService($serviceName)) {
                $retries++;
                continue;
            }

            // Wait for service to stabilize
            sleep(5);
        }

        return false;
    }

    protected function createDatabase(array $config): bool
    {
        try {
            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s', $config['env']['DB_HOST'], $config['env']['DB_PORT']),
                $config['env']['DB_USERNAME'],
                $config['env']['DB_PASSWORD']
            );
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['env']['DB_DATABASE']}");
            return true;
        } catch (\Exception $e) {
            $this->logError('database', "Failed to create database: " . $e->getMessage());
            return false;
        }
    }

    protected function createRedis(array $config): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect($config['env']['REDIS_HOST'], $config['env']['REDIS_PORT']);
            $redis->flushAll(); // Start with a clean slate
            return true;
        } catch (\Exception $e) {
            $this->logError('redis', "Failed to initialize Redis: " . $e->getMessage());
            return false;
        }
    }

    protected function createMail(array $config): bool
    {
        try {
            $transport = new \Swift_SmtpTransport(
                $config['env']['MAIL_HOST'],
                $config['env']['MAIL_PORT']
            );
            return $transport->start() !== false;
        } catch (\Exception $e) {
            $this->logError('mail', "Failed to initialize mail service: " . $e->getMessage());
            return false;
        }
    }

    protected function teardownDatabase(array $config): bool
    {
        try {
            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s', $config['env']['DB_HOST'], $config['env']['DB_PORT']),
                $config['env']['DB_USERNAME'],
                $config['env']['DB_PASSWORD']
            );
            
            $pdo->exec("DROP DATABASE IF EXISTS {$config['env']['DB_DATABASE']}");
            return true;
        } catch (\Exception $e) {
            $this->logError('database', "Failed to teardown database: " . $e->getMessage());
            return false;
        }
    }

    protected function teardownRedis(array $config): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect($config['env']['REDIS_HOST'], $config['env']['REDIS_PORT']);
            $redis->flushAll();
            return true;
        } catch (\Exception $e) {
            $this->logError('redis', "Failed to teardown Redis: " . $e->getMessage());
            return false;
        }
    }

    protected function teardownMail(array $config): bool
    {
        // Mail service doesn't require explicit teardown
        return true;
    }

    protected function logError(string $service, string $message): void
    {
        $logEntry = sprintf(
            "[%s] ERROR - Service %s: %s",
            now()->toIso8601String(),
            $service,
            $message
        );

        Log::channel('codespaces')->error($logEntry);
    }

    protected function logWarning(string $service, string $message): void
    {
        $logEntry = sprintf(
            "[%s] WARNING - Service %s: %s",
            now()->toIso8601String(),
            $service,
            $message
        );

        Log::channel('codespaces')->warning($logEntry);
    }
} 