<?php

namespace MCP\Tests\Core\Database;

use PHPUnit\Framework\TestCase;
use MCP\Core\Database\ConnectionManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PDO;
use PDOException;

class ConnectionManagerTest extends TestCase
{
    private $config;
    private $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $this->config = [
            'default' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'service_learning_test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]
        ];
    }

    public function testGetInstance()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        $instance = ConnectionManager::getInstance('default');
        
        $this->assertInstanceOf(ConnectionManager::class, $instance);
    }

    public function testGetConnection()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        $connection = $manager->getConnection();
        
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testInvalidConnectionConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        ConnectionManager::getInstance('invalid');
    }

    public function testTransactionManagement()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        
        // Start transaction
        $this->assertTrue($manager->beginTransaction());
        
        // Create test table
        $manager->query("CREATE TEMPORARY TABLE test (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        
        // Insert data
        $manager->query("INSERT INTO test (name) VALUES (?)", ['Test 1']);
        
        // Verify data exists
        $result = $manager->query("SELECT * FROM test WHERE name = ?", ['Test 1'])->fetch();
        $this->assertEquals('Test 1', $result['name']);
        
        // Rollback transaction
        $this->assertTrue($manager->rollBack());
        
        // Verify data was rolled back
        try {
            $manager->query("SELECT COUNT(*) as count FROM test")->fetch();
            $this->fail('Table should not exist after rollback');
        } catch (PDOException $e) {
            $this->assertStringContainsString("doesn't exist", $e->getMessage());
        }
    }

    public function testNestedTransactions()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        
        $manager->beginTransaction(); // Level 1
        $manager->beginTransaction(); // Level 2
        
        $this->assertTrue($manager->commit()); // Level 1
        $this->assertTrue($manager->commit()); // Complete
    }

    public function testQueryExecution()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        
        // Create test table
        $manager->query("CREATE TEMPORARY TABLE test (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        
        // Insert data
        $manager->query("INSERT INTO test (name) VALUES (?)", ['Test 1']);
        
        // Select data
        $result = $manager->query("SELECT * FROM test WHERE name = ?", ['Test 1'])->fetch();
        
        $this->assertEquals('Test 1', $result['name']);
    }

    public function testLastInsertId()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        
        // Create test table
        $manager->query("CREATE TEMPORARY TABLE test (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        
        // Insert data
        $manager->query("INSERT INTO test (name) VALUES (?)", ['Test 1']);
        
        $this->assertEquals('1', $manager->lastInsertId());
    }

    public function testConnectionClose()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        $manager->getConnection();
        $manager->close();
        
        // Verify new connection is created after close
        $this->assertInstanceOf(PDO::class, $manager->getConnection());
    }

    public function testInvalidQuery()
    {
        $manager = new ConnectionManager($this->config['default'], $this->logger);
        
        $this->expectException(PDOException::class);
        $manager->query("INVALID SQL");
    }
} 