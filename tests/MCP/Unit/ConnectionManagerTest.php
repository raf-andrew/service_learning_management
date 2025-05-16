<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\ConnectionManager;
use PDO;
use Psr\Log\LoggerInterface;

class ConnectionManagerTest extends TestCase
{
    private ConnectionManager $connectionManager;
    private LoggerInterface $logger;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->pdo = MockFactory::createMockPDO();
        
        $this->connectionManager = new ConnectionManager($this->logger);
    }

    public function testConnectionManagerCanBeCreated(): void
    {
        $this->assertInstanceOf(ConnectionManager::class, $this->connectionManager);
    }

    public function testConnectionManagerHasLogger(): void
    {
        $this->assertSame($this->logger, $this->connectionManager->getLogger());
    }

    public function testConnectionManagerCanAddConnection(): void
    {
        $this->connectionManager->addConnection('test', $this->pdo);
        
        $this->assertTrue($this->connectionManager->hasConnection('test'));
        $this->assertSame($this->pdo, $this->connectionManager->getConnection('test'));
    }

    public function testConnectionManagerCanRemoveConnection(): void
    {
        $this->connectionManager->addConnection('test', $this->pdo);
        $this->connectionManager->removeConnection('test');
        
        $this->assertFalse($this->connectionManager->hasConnection('test'));
    }

    public function testConnectionManagerThrowsExceptionForMissingConnection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->connectionManager->getConnection('nonexistent');
    }

    public function testConnectionManagerCanSetDefaultConnection(): void
    {
        $this->connectionManager->addConnection('test', $this->pdo);
        $this->connectionManager->setDefaultConnection('test');
        
        $this->assertEquals('test', $this->connectionManager->getDefaultConnection());
        $this->assertSame($this->pdo, $this->connectionManager->getConnection());
    }

    public function testConnectionManagerThrowsExceptionForInvalidDefaultConnection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->connectionManager->setDefaultConnection('nonexistent');
    }

    public function testConnectionManagerCanListConnections(): void
    {
        $this->connectionManager->addConnection('test1', $this->pdo);
        $this->connectionManager->addConnection('test2', $this->pdo);
        
        $connections = $this->connectionManager->listConnections();
        
        $this->assertIsArray($connections);
        $this->assertCount(2, $connections);
        $this->assertContains('test1', $connections);
        $this->assertContains('test2', $connections);
    }

    public function testConnectionManagerCanCheckConnectionStatus(): void
    {
        $this->connectionManager->addConnection('test', $this->pdo);
        
        $this->assertTrue($this->connectionManager->isConnected('test'));
    }

    public function testConnectionManagerCanReconnect(): void
    {
        $this->connectionManager->addConnection('test', $this->pdo);
        $this->connectionManager->reconnect('test');
        
        $this->assertTrue($this->connectionManager->isConnected('test'));
    }
} 