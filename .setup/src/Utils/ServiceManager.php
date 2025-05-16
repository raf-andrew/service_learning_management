<?php

namespace Setup\Utils;

class ServiceManager {
    private array $config;
    private Logger $logger;
    private array $processes = [];

    public function __construct(array $config, Logger $logger) {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function startServices(): void {
        $this->logger->info('Starting services');

        if ($this->config['api']['enabled']) {
            $this->startApiService();
        }

        if ($this->config['queue']['enabled']) {
            $this->startQueueService();
        }

        if ($this->config['cache']['enabled']) {
            $this->startCacheService();
        }

        $this->logger->info('Services started');
    }

    public function stopServices(): void {
        $this->logger->info('Stopping services');

        foreach ($this->processes as $name => $process) {
            $this->logger->info("Stopping {$name} service");
            $this->stopProcess($process);
        }

        $this->processes = [];
        $this->logger->info('Services stopped');
    }

    private function startApiService(): void {
        $this->logger->info('Starting API service');

        $port = $this->config['api']['port'];
        $workers = $this->config['api']['workers'];
        $timeout = $this->config['api']['timeout'];

        $command = sprintf(
            'php -S localhost:%d -t public/ > storage/logs/api.log 2>&1 & echo $!',
            $port
        );

        $pid = $this->startProcess($command);
        if ($pid) {
            $this->processes['api'] = $pid;
            $this->logger->info('API service started', ['pid' => $pid]);
        }
    }

    private function startQueueService(): void {
        $this->logger->info('Starting queue service');

        $driver = $this->config['queue']['driver'];
        $connection = $this->config['queue']['connection'];
        $queue = $this->config['queue']['queue'];
        $retryAfter = $this->config['queue']['retry_after'];

        $command = sprintf(
            'php artisan queue:work %s --queue=%s --tries=3 --timeout=%d > storage/logs/queue.log 2>&1 & echo $!',
            $connection,
            $queue,
            $retryAfter
        );

        $pid = $this->startProcess($command);
        if ($pid) {
            $this->processes['queue'] = $pid;
            $this->logger->info('Queue service started', ['pid' => $pid]);
        }
    }

    private function startCacheService(): void {
        $this->logger->info('Starting cache service');

        $driver = $this->config['cache']['driver'];
        $connection = $this->config['cache']['connection'];

        switch ($driver) {
            case 'redis':
                $this->startRedisService();
                break;
            case 'memcached':
                $this->startMemcachedService();
                break;
            default:
                $this->logger->warning('No cache service to start', ['driver' => $driver]);
        }
    }

    private function startRedisService(): void {
        $this->logger->info('Starting Redis service');

        $command = 'redis-server > storage/logs/redis.log 2>&1 & echo $!';
        $pid = $this->startProcess($command);
        
        if ($pid) {
            $this->processes['redis'] = $pid;
            $this->logger->info('Redis service started', ['pid' => $pid]);
        }
    }

    private function startMemcachedService(): void {
        $this->logger->info('Starting Memcached service');

        $command = 'memcached -d > storage/logs/memcached.log 2>&1 & echo $!';
        $pid = $this->startProcess($command);
        
        if ($pid) {
            $this->processes['memcached'] = $pid;
            $this->logger->info('Memcached service started', ['pid' => $pid]);
        }
    }

    private function startProcess(string $command): ?int {
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $this->logger->error('Failed to start process', [
                'command' => $command,
                'output' => $output,
                'returnVar' => $returnVar
            ]);
            return null;
        }

        return (int) end($output);
    }

    private function stopProcess(int $pid): void {
        if (posix_kill($pid, SIGTERM)) {
            $this->logger->info('Process stopped', ['pid' => $pid]);
        } else {
            $this->logger->warning('Failed to stop process', ['pid' => $pid]);
        }
    }

    public function getRunningServices(): array {
        return array_keys($this->processes);
    }

    public function isServiceRunning(string $service): bool {
        return isset($this->processes[$service]);
    }
} 