<?php

namespace Setup\Utils;

class CacheManager {
    private Logger $logger;
    private array $config;
    private ?\Redis $redis = null;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function setConfig(array $config): void {
        $this->config = $config;
    }

    public function connect(): void {
        if ($this->redis !== null) {
            return;
        }

        try {
            $this->redis = new \Redis();
            $this->redis->connect($this->config['host'], $this->config['port']);
            
            if (!empty($this->config['password'])) {
                $this->redis->auth($this->config['password']);
            }

            $this->logger->info('Cache connection established');
        } catch (\RedisException $e) {
            throw new \RuntimeException('Failed to connect to cache: ' . $e->getMessage());
        }
    }

    public function disconnect(): void {
        if ($this->redis !== null) {
            $this->redis->close();
            $this->redis = null;
            $this->logger->info('Cache connection closed');
        }
    }

    public function flush(): void {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $this->redis->flushAll();
            $this->logger->info('Cache flushed successfully');
        } catch (\RedisException $e) {
            throw new \RuntimeException('Failed to flush cache: ' . $e->getMessage());
        }
    }

    public function set(string $key, $value, ?int $ttl = null): void {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            if ($ttl !== null) {
                $this->redis->setex($key, $ttl, $value);
            } else {
                $this->redis->set($key, $value);
            }
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to set cache key {$key}: " . $e->getMessage());
        }
    }

    public function get(string $key, $default = null) {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $value = $this->redis->get($key);
            if ($value === false) {
                return $default;
            }

            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to get cache key {$key}: " . $e->getMessage());
        }
    }

    public function delete(string $key): void {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $this->redis->del($key);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to delete cache key {$key}: " . $e->getMessage());
        }
    }

    public function exists(string $key): bool {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->exists($key);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to check cache key {$key}: " . $e->getMessage());
        }
    }

    public function increment(string $key, int $value = 1): int {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->incrBy($key, $value);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to increment cache key {$key}: " . $e->getMessage());
        }
    }

    public function decrement(string $key, int $value = 1): int {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->decrBy($key, $value);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to decrement cache key {$key}: " . $e->getMessage());
        }
    }

    public function ttl(string $key): int {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->ttl($key);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to get TTL for cache key {$key}: " . $e->getMessage());
        }
    }

    public function expire(string $key, int $ttl): void {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $this->redis->expire($key, $ttl);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to set TTL for cache key {$key}: " . $e->getMessage());
        }
    }

    public function persist(string $key): void {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $this->redis->persist($key);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to persist cache key {$key}: " . $e->getMessage());
        }
    }

    public function keys(string $pattern): array {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->keys($pattern);
        } catch (\RedisException $e) {
            throw new \RuntimeException("Failed to get keys with pattern {$pattern}: " . $e->getMessage());
        }
    }

    public function getConnection(): ?\Redis {
        return $this->redis;
    }

    public function isConnected(): bool {
        return $this->redis !== null;
    }

    public function ping(): bool {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->ping() === '+PONG';
        } catch (\RedisException $e) {
            return false;
        }
    }

    public function getInfo(): array {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            return $this->redis->info();
        } catch (\RedisException $e) {
            throw new \RuntimeException('Failed to get cache info: ' . $e->getMessage());
        }
    }

    public function getMemoryUsage(): int {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $info = $this->redis->info('memory');
            return (int)($info['used_memory'] ?? 0);
        } catch (\RedisException $e) {
            throw new \RuntimeException('Failed to get memory usage: ' . $e->getMessage());
        }
    }

    public function getClientCount(): int {
        if ($this->redis === null) {
            $this->connect();
        }

        try {
            $info = $this->redis->info('clients');
            return (int)($info['connected_clients'] ?? 0);
        } catch (\RedisException $e) {
            throw new \RuntimeException('Failed to get client count: ' . $e->getMessage());
        }
    }
} 