<?php

namespace Scripts;

class TestEnvironmentVerifier
{
    private array $requiredExtensions = [
        'pdo',
        'pdo_mysql',
        'pdo_sqlite',
        'mbstring',
        'xml',
        'json',
        'redis'
    ];

    private array $requiredDirectories = [
        '.reports/tests',
        '.reports/coverage',
        '.reports/performance',
        '.reports/security'
    ];

    public function verify(): bool
    {
        $this->checkExtensions();
        $this->createDirectories();
        $this->verifyDatabase();
        $this->verifyRedis();
        return true;
    }

    private function checkExtensions(): void
    {
        foreach ($this->requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                throw new \RuntimeException("Required PHP extension '{$extension}' is not loaded");
            }
        }
    }

    private function createDirectories(): void
    {
        foreach ($this->requiredDirectories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    private function verifyDatabase(): void
    {
        try {
            $pdo = new \PDO(
                "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD')
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Create test database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . getenv('DB_DATABASE'));
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    private function verifyRedis(): void
    {
        try {
            $redis = new \Redis();
            $redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
            $redis->ping();
        } catch (\Exception $e) {
            throw new \RuntimeException("Redis connection failed: " . $e->getMessage());
        }
    }
}

// Run verification
try {
    $verifier = new TestEnvironmentVerifier();
    $verifier->verify();
    echo "Test environment verification completed successfully.\n";
    exit(0);
} catch (\Exception $e) {
    echo "Test environment verification failed: " . $e->getMessage() . "\n";
    exit(1);
} 