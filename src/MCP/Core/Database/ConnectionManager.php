<?php

declare(strict_types=1);

namespace MCP\Core\Database;

use PDO;
use PDOException;
use MCP\Core\Logger\Logger;
use MCP\Core\Config\Config;

class ConnectionManager
{
    private array $connections = [];
    private ?string $defaultConnection = null;

    public function __construct(
        private Config $config,
        private Logger $logger
    ) {
        $this->defaultConnection = $this->config->get('database.default', 'mysql');
    }

    public function getConnection(?string $name = null): PDO
    {
        $name = $name ?? $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    public function setDefaultConnection(string $name): void
    {
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        $this->defaultConnection = $name;
    }

    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    public function disconnect(string $name): void
    {
        if (isset($this->connections[$name])) {
            $this->connections[$name] = null;
            unset($this->connections[$name]);
        }
    }

    public function disconnectAll(): void
    {
        foreach (array_keys($this->connections) as $name) {
            $this->disconnect($name);
        }
    }

    private function createConnection(string $name): PDO
    {
        $config = $this->config->get("database.connections.{$name}");

        if (!$config) {
            throw new PDOException("Database connection '{$name}' not configured");
        }

        $dsn = $this->buildDsn($config);
        $options = $this->getConnectionOptions();

        try {
            $connection = new PDO(
                $dsn,
                $config['username'] ?? null,
                $config['password'] ?? null,
                $options
            );

            $this->logger->info("Database connection '{$name}' established");
            return $connection;
        } catch (PDOException $e) {
            $this->logger->error("Failed to connect to database '{$name}'", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function buildDsn(array $config): string
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? null;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "{$driver}:host={$host}";
        
        if ($port) {
            $dsn .= ";port={$port}";
        }

        $dsn .= ";dbname={$database};charset={$charset}";

        return $dsn;
    }

    private function getConnectionOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
    }

    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    public function lastInsertId(?string $name = null): string
    {
        return $this->getConnection()->lastInsertId($name);
    }

    public function prepare(string $query): \PDOStatement
    {
        return $this->getConnection()->prepare($query);
    }

    public function query(string $query): \PDOStatement
    {
        return $this->getConnection()->query($query);
    }

    public function exec(string $query): int
    {
        return $this->getConnection()->exec($query);
    }
} 