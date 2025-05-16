<?php

declare(strict_types=1);

namespace MCP\Tests\Helpers;

use MCP\Core\Database\ConnectionManager;
use PDO;
use PDOException;

abstract class DatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestDatabase();
        parent::tearDown();
    }

    protected function setupTestDatabase(): void
    {
        $db = $this->getDb()->getConnection();
        
        // Create test tables
        $db->exec("
            CREATE TABLE IF NOT EXISTS test_models (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS test_relations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                test_model_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (test_model_id) REFERENCES test_models(id)
            )
        ");
    }

    protected function cleanupTestDatabase(): void
    {
        $db = $this->getDb()->getConnection();
        
        // Drop test tables
        $db->exec("DROP TABLE IF EXISTS test_relations");
        $db->exec("DROP TABLE IF EXISTS test_models");
    }

    protected function insertTestData(array $data): int
    {
        $db = $this->getDb()->getConnection();
        
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            "INSERT INTO test_models (%s) VALUES (%s)",
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        return (int) $db->lastInsertId();
    }

    protected function getTestData(int $id): ?array
    {
        $db = $this->getDb()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM test_models WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
} 