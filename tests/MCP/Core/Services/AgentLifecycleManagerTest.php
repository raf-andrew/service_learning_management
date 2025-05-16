<?php

namespace Tests\MCP\Core\Services;

use PHPUnit\Framework\TestCase;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

class AgentLifecycleManagerTest extends TestCase
{
    private AgentLifecycleManager $manager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = new AgentLifecycleManager($this->logger);
    }

    public function testRegisterAgent(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Registered agent with lifecycle manager'));

        $this->manager->registerAgent($agent);
        $status = $this->manager->getAgentStatus();
        $this->assertArrayHasKey(get_class($agent), $status);
    }

    public function testUnregisterAgent(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Unregistered agent from lifecycle manager'));

        $this->manager->unregisterAgent($agent);
        $status = $this->manager->getAgentStatus();
        $this->assertArrayNotHasKey(get_class($agent), $status);
    }

    public function testStartAgent(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Started agent'));

        $this->manager->startAgent($agent);
        $status = $this->manager->getAgentStatusByClass(get_class($agent));
        $this->assertEquals('running', $status['status']);
        $this->assertNotNull($status['started_at']);
    }

    public function testStopAgent(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);
        $this->manager->startAgent($agent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Stopped agent'));

        $this->manager->stopAgent($agent);
        $status = $this->manager->getAgentStatusByClass(get_class($agent));
        $this->assertEquals('stopped', $status['status']);
        $this->assertNotNull($status['stopped_at']);
    }

    public function testStartAgentWithError(): void
    {
        $agent = new class {
            public function initialize(): void
            {
                throw new \RuntimeException('Initialization error');
            }
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to start agent'));

        $this->expectException(\RuntimeException::class);
        $this->manager->startAgent($agent);
    }

    public function testStopAgentWithError(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void
            {
                throw new \RuntimeException('Shutdown error');
            }
        };

        $this->manager->registerAgent($agent);
        $this->manager->startAgent($agent);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to stop agent'));

        $this->expectException(\RuntimeException::class);
        $this->manager->stopAgent($agent);
    }

    public function testIsAgentRunning(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);
        $this->assertFalse($this->manager->isAgentRunning(get_class($agent)));

        $this->manager->startAgent($agent);
        $this->assertTrue($this->manager->isAgentRunning(get_class($agent)));

        $this->manager->stopAgent($agent);
        $this->assertFalse($this->manager->isAgentRunning(get_class($agent)));
    }

    public function testGetAgentStatus(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);
        $this->manager->startAgent($agent);

        $status = $this->manager->getAgentStatus();
        $this->assertArrayHasKey(get_class($agent), $status);
        $this->assertEquals('running', $status[get_class($agent)]['status']);
        $this->assertNotNull($status[get_class($agent)]['started_at']);
    }

    public function testGetAgentStatusByClass(): void
    {
        $agent = new class {
            public function initialize(): void {}
            public function shutdown(): void {}
        };

        $this->manager->registerAgent($agent);
        $this->manager->startAgent($agent);

        $status = $this->manager->getAgentStatusByClass(get_class($agent));
        $this->assertNotNull($status);
        $this->assertEquals('running', $status['status']);
        $this->assertNotNull($status['started_at']);

        $this->assertNull($this->manager->getAgentStatusByClass('NonExistentAgent'));
    }
} 