<?php

namespace Setup\Utils;

use PDO;
use PDOException;

class DatabaseManager {
    private ?PDO $connection = null;
    private array $config;
    private Logger $logger;
    private ConfigManager $configManager;
    private array $migrations = [];
    private array $seeds = [];

    public function __construct(ConfigManager $config, Logger $logger) {
        $this->configManager = $config;
        $this->logger = $logger;
    }

    public function setConfig(array $config): void {
        $this->config = $config;
    }

    public function connect(): void {
        $this->logger->info('Connecting to database');
        
        if ($this->connection !== null) {
            $this->logger->info('Already connected to database');
            return;
        }
        
        $dsn = $this->buildDsn();
        
        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            $this->logger->info('Successfully connected to database');
        } catch (PDOException $e) {
            $this->logger->error('Failed to connect to database', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function disconnect(): void {
        $this->logger->info('Disconnecting from database');
        
        $this->connection = null;
        
        $this->logger->info('Successfully disconnected from database');
    }

    public function setup(): void {
        $this->logger->info('Setting up database');
        
        try {
            $this->createDatabase();
            $this->runMigrations();
            $this->runSeeds();
            
            $this->logger->info('Database setup completed');
        } catch (\Exception $e) {
            $this->logger->error('Database setup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createDatabase(): void {
        $this->logger->info('Creating database');
        
        try {
            // Connect without database name
            $tempConfig = $this->config;
            unset($tempConfig['database']);
            $dsn = $this->buildDsn($tempConfig);
            
            $pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Create database if not exists
            $charset = $this->config['charset'] ?? 'utf8mb4';
            $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->config['database']}` CHARACTER SET {$charset} COLLATE {$collation}");
            
            $this->logger->info("Database '{$this->config['database']}' created successfully");
        } catch (PDOException $e) {
            $this->logger->error('Failed to create database', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Database creation failed: ' . $e->getMessage());
        }
    }

    public function dropDatabase(): void {
        $this->logger->info('Dropping database');
        
        $config = $this->configManager->get('database');
        $dbName = $config['database'];
        
        try {
            // Connect without database name
            $tempConfig = $config;
            unset($tempConfig['database']);
            $dsn = $this->buildDsn($tempConfig);
            
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            // Drop database if exists
            $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
            
            $this->logger->info("Database '{$dbName}' dropped successfully");
        } catch (\PDOException $e) {
            $this->logger->error('Failed to drop database', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Database drop failed: ' . $e->getMessage());
        }
    }

    public function runMigrations(): void {
        $this->logger->info('Running database migrations');
        
        if ($this->connection === null) {
            $this->connect();
        }
        
        $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            $this->logger->warning('Migrations directory not found');
            return;
        }
        
        $this->createMigrationsTable();
        
        $files = glob($migrationsDir . '/*.php');
        sort($files);
        
        foreach ($files as $file) {
            $migration = $this->loadMigration($file);
            if ($migration === null) {
                continue;
            }
            
            $migrationName = basename($file, '.php');
            
            if ($this->hasMigrationRun($migrationName)) {
                $this->logger->info("Migration '{$migrationName}' already run");
                continue;
            }
            
            try {
                $this->connection->beginTransaction();
                
                $migration->up($this->connection);
                $this->markMigrationAsRun($migrationName);
                
                $this->connection->commit();
                $this->logger->info("Migration '{$migrationName}' completed successfully");
            } catch (\Exception $e) {
                $this->connection->rollBack();
                $this->logger->error("Migration '{$migrationName}' failed", ['error' => $e->getMessage()]);
                throw new \RuntimeException("Migration failed: {$e->getMessage()}");
            }
        }
        
        $this->logger->info('All migrations completed');
    }

    public function rollbackMigrations(int $steps = 1): void {
        $this->logger->info('Rolling back migrations');
        
        if ($this->connection === null) {
            $this->connect();
        }
        
        $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            $this->logger->warning('Migrations directory not found');
            return;
        }
        
        $files = glob($migrationsDir . '/*.php');
        rsort($files);
        
        $count = 0;
        foreach ($files as $file) {
            if ($count >= $steps) {
                break;
            }
            
            $migration = $this->loadMigration($file);
            if ($migration === null) {
                continue;
            }
            
            $migrationName = basename($file, '.php');
            
            if (!$this->hasMigrationRun($migrationName)) {
                $this->logger->info("Migration '{$migrationName}' not run");
                continue;
            }
            
            try {
                $this->connection->beginTransaction();
                
                $migration->down($this->connection);
                $this->markMigrationAsNotRun($migrationName);
                
                $this->connection->commit();
                $this->logger->info("Migration '{$migrationName}' rolled back successfully");
                
                $count++;
            } catch (\Exception $e) {
                $this->connection->rollBack();
                $this->logger->error("Rollback of '{$migrationName}' failed", ['error' => $e->getMessage()]);
                throw new \RuntimeException("Migration rollback failed: {$e->getMessage()}");
            }
        }
        
        $this->logger->info('Migrations rollback completed');
    }

    public function runSeeds(): void {
        $this->logger->info('Running database seeds');
        
        if ($this->connection === null) {
            $this->connect();
        }
        
        $seedsDir = dirname(__DIR__, 2) . '/database/seeds';
        if (!is_dir($seedsDir)) {
            $this->logger->warning('Seeds directory not found');
            return;
        }
        
        $files = glob($seedsDir . '/*.php');
        sort($files);
        
        foreach ($files as $file) {
            $seed = $this->loadSeed($file);
            if ($seed === null) {
                continue;
            }
            
            $seedName = basename($file, '.php');
            
            try {
                $this->connection->beginTransaction();
                
                $seed->run($this->connection);
                
                $this->connection->commit();
                $this->logger->info("Seed '{$seedName}' completed successfully");
            } catch (\Exception $e) {
                $this->connection->rollBack();
                $this->logger->error("Seed '{$seedName}' failed", ['error' => $e->getMessage()]);
                throw new \RuntimeException("Seed failed: {$e->getMessage()}");
            }
        }
        
        $this->logger->info('All seeds completed');
    }

    private function buildDsn(array $config = null): string {
        $config = $config ?? $this->config;
        $driver = $config['driver'];
        $host = $config['host'];
        $port = $config['port'] ?? null;
        $database = $config['database'] ?? null;
        
        $dsn = "{$driver}:host={$host}";
        
        if ($port !== null) {
            $dsn .= ";port={$port}";
        }
        
        if ($database !== null) {
            $dsn .= ";dbname={$database}";
        }
        
        if ($driver === 'mysql') {
            $charset = $config['charset'] ?? 'utf8mb4';
            $dsn .= ";charset={$charset}";
        }
        
        return $dsn;
    }

    private function createMigrationsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
    }

    private function loadMigration(string $file): ?object {
        require_once $file;
        
        $className = 'Database\\Migrations\\' . basename($file, '.php');
        if (!class_exists($className)) {
            $this->logger->warning("Migration class '{$className}' not found");
            return null;
        }
        
        return new $className();
    }

    private function loadSeed(string $file): ?object {
        require_once $file;
        
        $className = 'Database\\Seeds\\' . basename($file, '.php');
        if (!class_exists($className)) {
            $this->logger->warning("Seed class '{$className}' not found");
            return null;
        }
        
        return new $className();
    }

    private function hasMigrationRun(string $migration): bool {
        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
        $stmt->execute([$migration]);
        return (bool)$stmt->fetchColumn();
    }

    private function markMigrationAsRun(string $migration): void {
        $batch = $this->getNextBatchNumber();
        $stmt = $this->connection->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
        $stmt->execute([$migration, $batch]);
    }

    private function markMigrationAsNotRun(string $migration): void {
        $stmt = $this->connection->prepare('DELETE FROM migrations WHERE migration = ?');
        $stmt->execute([$migration]);
    }

    private function getNextBatchNumber(): int {
        $stmt = $this->connection->query('SELECT MAX(batch) FROM migrations');
        return (int)$stmt->fetchColumn() + 1;
    }

    public function query(string $sql, array $params = []): \PDOStatement {
        if ($this->connection === null) {
            $this->connect();
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \RuntimeException('Query failed: ' . $e->getMessage());
        }
    }

    public function fetch(string $sql, array $params = []): ?array {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0) {
        return $this->query($sql, $params)->fetchColumn($column);
    }

    public function insert(string $table, array $data): int {
        if ($this->connection === null) {
            $this->connect();
        }

        try {
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";

            $this->query($sql, array_values($data));
            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new \RuntimeException('Insert failed: ' . $e->getMessage());
        }
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        if ($this->connection === null) {
            $this->connect();
        }

        try {
            $set = implode(', ', array_map(function ($column) {
                return "{$column} = ?";
            }, array_keys($data)));

            $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
            $params = array_merge(array_values($data), $whereParams);

            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \RuntimeException('Update failed: ' . $e->getMessage());
        }
    }

    public function delete(string $table, string $where, array $params = []): int {
        if ($this->connection === null) {
            $this->connect();
        }

        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \RuntimeException('Delete failed: ' . $e->getMessage());
        }
    }

    public function beginTransaction(): bool {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        if ($this->connection === null) {
            throw new \RuntimeException('No active transaction');
        }
        return $this->connection->commit();
    }

    public function rollBack(): bool {
        if ($this->connection === null) {
            throw new \RuntimeException('No active transaction');
        }
        return $this->connection->rollBack();
    }

    public function inTransaction(): bool {
        if ($this->connection === null) {
            return false;
        }
        return $this->connection->inTransaction();
    }

    public function getConnection(): ?PDO {
        return $this->connection;
    }

    public function isConnected(): bool {
        return $this->connection !== null;
    }

    public function getLastInsertId(): string {
        if ($this->connection === null) {
            throw new \RuntimeException('No active connection');
        }
        return $this->connection->lastInsertId();
    }

    public function quote(string $value): string {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection->quote($value);
    }

    public function getErrorInfo(): array {
        if ($this->connection === null) {
            return [];
        }
        return $this->connection->errorInfo();
    }

    public function getAttribute(int $attribute) {
        if ($this->connection === null) {
            throw new \RuntimeException('No active connection');
        }
        return $this->connection->getAttribute($attribute);
    }

    public function setAttribute(int $attribute, $value): bool {
        if ($this->connection === null) {
            throw new \RuntimeException('No active connection');
        }
        return $this->connection->setAttribute($attribute, $value);
    }
} 