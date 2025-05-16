<?php

declare(strict_types=1);

namespace MCP\Tests\Unit\Core\Database;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Core\Database\ConnectionManager;
use MCP\Core\Logger\Logger;
use MCP\Core\Config\Config;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;

class ConnectionManagerTest extends TestCase
{
    private ConnectionManager $manager;
    private Config|MockObject $config;
    private Logger|MockObject $logger;
    private PDO $pdo;
    private PDOStatement $statement;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->pdo = MockFactory::createMockPDO();
        $this->statement = MockFactory::createMockPDOStatement();

        $this->manager = new ConnectionManager($this->config, $this->logger);
    }

    public function testManagerCanBeCreated(): void
    {
        $this->assertInstanceOf(ConnectionManager::class, $this->manager);
    }

    public function testManagerGetsDefaultConnection(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.default', 'mysql')
            ->willReturn('mysql');

        $this->assertEquals('mysql', $this->manager->getDefaultConnection());
    }

    public function testManagerCanSetDefaultConnection(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.connections.sqlite')
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $this->manager->setDefaultConnection('sqlite');
        $this->assertEquals('sqlite', $this->manager->getDefaultConnection());
    }

    public function testManagerCreatesConnection(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.connections.mysql')
            ->willReturn([
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Database connection \'mysql\' established');

        $connection = $this->manager->getConnection('mysql');
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testManagerReusesExistingConnection(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.connections.mysql')
            ->willReturn([
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Database connection \'mysql\' established');

        $connection1 = $this->manager->getConnection('mysql');
        $connection2 = $this->manager->getConnection('mysql');

        $this->assertSame($connection1, $connection2);
    }

    public function testManagerThrowsExceptionForInvalidConnection(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.connections.invalid')
            ->willReturn(null);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("Database connection 'invalid' not configured");

        $this->manager->getConnection('invalid');
    }

    public function testManagerCanDisconnectConnection(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.connections.mysql')
            ->willReturn([
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]);

        $this->manager->getConnection('mysql');
        $this->manager->disconnect('mysql');

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('database.connections.mysql')
            ->willReturn([
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]);

        $this->manager->getConnection('mysql');
    }

    public function testManagerCanDisconnectAllConnections(): void
    {
        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['database.connections.mysql', null, [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => 'test',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8mb4'
                ]],
                ['database.connections.sqlite', null, [
                    'driver' => 'sqlite',
                    'database' => ':memory:'
                ]]
            ]);

        $this->manager->getConnection('mysql');
        $this->manager->getConnection('sqlite');
        $this->manager->disconnectAll();

        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['database.connections.mysql', null, [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => 'test',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8mb4'
                ]],
                ['database.connections.sqlite', null, [
                    'driver' => 'sqlite',
                    'database' => ':memory:'
                ]]
            ]);

        $this->manager->getConnection('mysql');
        $this->manager->getConnection('sqlite');
    }

    public function testConnectionManagerCanGetConnection(): void
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ];

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn($config);

        $this->pdo->expects($this->exactly(3))
            ->method('setAttribute')
            ->withConsecutive(
                [PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION],
                [PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC],
                [PDO::ATTR_EMULATE_PREPARES, false]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Database connection established', [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test'
            ]);

        $connection = $this->manager->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testConnectionManagerHandlesConnectionError(): void
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'invalid_host',
            'database' => 'test',
            'username' => 'root',
            'password' => ''
        ];

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn($config);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Database connection failed', $this->callback(function ($context) {
                return isset($context['error']) && isset($context['code']);
            }));

        $this->expectException(PDOException::class);
        $this->manager->getConnection();
    }

    public function testConnectionManagerCanDisconnect(): void
    {
        $this->assertFalse($this->manager->isConnected());
        
        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $this->manager->getConnection();
        $this->assertTrue($this->manager->isConnected());
        
        $this->manager->disconnect();
        $this->assertFalse($this->manager->isConnected());
    }

    public function testConnectionManagerCanBeginTransaction(): void
    {
        $this->pdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->beginTransaction();
        $this->assertTrue($result);
    }

    public function testConnectionManagerCanCommitTransaction(): void
    {
        $this->pdo->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->commit();
        $this->assertTrue($result);
    }

    public function testConnectionManagerCanRollBackTransaction(): void
    {
        $this->pdo->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->rollBack();
        $this->assertTrue($result);
    }

    public function testConnectionManagerCanCheckTransactionStatus(): void
    {
        $this->pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->inTransaction();
        $this->assertTrue($result);
    }

    public function testConnectionManagerCanGetLastInsertId(): void
    {
        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->with(null)
            ->willReturn('1');

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->lastInsertId();
        $this->assertEquals('1', $result);
    }

    public function testConnectionManagerCanPrepareStatement(): void
    {
        $query = 'SELECT * FROM test';
        
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($query)
            ->willReturn($this->statement);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->prepare($query);
        $this->assertInstanceOf(PDOStatement::class, $result);
    }

    public function testConnectionManagerCanExecuteQuery(): void
    {
        $query = 'SELECT * FROM test';
        
        $this->pdo->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($this->statement);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->query($query);
        $this->assertInstanceOf(PDOStatement::class, $result);
    }

    public function testConnectionManagerCanExecuteStatement(): void
    {
        $query = 'DELETE FROM test';
        
        $this->pdo->expects($this->once())
            ->method('exec')
            ->with($query)
            ->willReturn(1);

        $this->config->expects($this->once())
            ->method('get')
            ->with('database', [])
            ->willReturn([
                'driver' => 'sqlite',
                'database' => ':memory:'
            ]);

        $result = $this->manager->exec($query);
        $this->assertEquals(1, $result);
    }
} 