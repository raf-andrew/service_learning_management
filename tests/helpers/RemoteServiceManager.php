<?php

namespace Tests\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;

class RemoteServiceManager
{
    protected LoggerInterface $logger;
    protected array $config;
    protected array $connections = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->config = require __DIR__ . '/../config/remote-services.php';
    }

    /**
     * Initialize all remote service connections
     */
    public function initialize(): void
    {
        $this->initializeDatabase();
        $this->initializeRedis();
        $this->initializeMail();
        $this->initializeMCP();
    }

    /**
     * Initialize database connection
     */
    protected function initializeDatabase(): void
    {
        try {
            $config = $this->config['database'];
            DB::purge();
            DB::reconnect();
            $this->connections['database'] = DB::connection();
            $this->logger->info('Database connection initialized');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize database connection: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize Redis connection
     */
    protected function initializeRedis(): void
    {
        try {
            $config = $this->config['redis'];
            Redis::purge();
            Redis::reconnect();
            $this->connections['redis'] = Redis::connection();
            $this->logger->info('Redis connection initialized');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Redis connection: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize mail connection
     */
    protected function initializeMail(): void
    {
        try {
            $config = $this->config['mail'];
            config(['mail' => $config]);
            $this->connections['mail'] = Mail::mailer();
            $this->logger->info('Mail connection initialized');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize mail connection: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize MCP service connection
     */
    protected function initializeMCP(): void
    {
        try {
            $config = $this->config['mcp'];
            $this->connections['mcp'] = new Client([
                'base_uri' => $config['url'],
                'timeout' => $config['timeout'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Accept' => 'application/json',
                ],
            ]);
            $this->logger->info('MCP service connection initialized');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize MCP service connection: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a service connection
     */
    public function getConnection(string $service)
    {
        if (!isset($this->connections[$service])) {
            throw new \RuntimeException("Service {$service} not initialized");
        }
        return $this->connections[$service];
    }

    /**
     * Check if all services are healthy
     */
    public function checkHealth(): bool
    {
        $healthy = true;
        foreach ($this->connections as $service => $connection) {
            if (!$this->checkServiceHealth($service)) {
                $healthy = false;
            }
        }
        return $healthy;
    }

    /**
     * Check individual service health
     */
    protected function checkServiceHealth(string $service): bool
    {
        try {
            switch ($service) {
                case 'database':
                    return $this->connections[$service]->getPdo() !== null;
                case 'redis':
                    return $this->connections[$service]->ping() === true;
                case 'mail':
                    return $this->connections[$service]->getSymfonyTransport()->start();
                case 'mcp':
                    $response = $this->connections[$service]->get('/health');
                    return $response->getStatusCode() === 200;
                default:
                    return false;
            }
        } catch (\Exception $e) {
            $this->logger->error("Service {$service} health check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up connections
     */
    public function cleanup(): void
    {
        foreach ($this->connections as $service => $connection) {
            try {
                switch ($service) {
                    case 'database':
                        DB::disconnect();
                        break;
                    case 'redis':
                        Redis::disconnect();
                        break;
                    case 'mail':
                        // No cleanup needed
                        break;
                    case 'mcp':
                        // No cleanup needed
                        break;
                }
                $this->logger->info("Cleaned up {$service} connection");
            } catch (\Exception $e) {
                $this->logger->error("Failed to clean up {$service} connection: " . $e->getMessage());
            }
        }
    }
} 