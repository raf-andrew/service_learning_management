<?php

namespace Tests\MCP\Core\Services;

use PHPUnit\Framework\TestCase;
use App\MCP\Core\Services\HealthMonitor;
use Psr\Log\LoggerInterface;

class HealthMonitorTest extends TestCase
{
    private HealthMonitor $monitor;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->monitor = new HealthMonitor($this->logger);
    }

    public function testRegisterAgent(): void
    {
        $agent = new class {
            public function getHealthStatus(): array
            {
                return ['status' => 'healthy'];
            }
        };

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Registered agent with health monitor'));

        $this->monitor->registerAgent($agent);
        $status = $this->monitor->getHealthStatus();
        $this->assertArrayHasKey(get_class($agent), $status);
    }

    public function testUnregisterAgent(): void
    {
        $agent = new class {
            public function getHealthStatus(): array
            {
                return ['status' => 'healthy'];
            }
        };

        $this->monitor->registerAgent($agent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Unregistered agent from health monitor'));

        $this->monitor->unregisterAgent($agent);
        $status = $this->monitor->getHealthStatus();
        $this->assertArrayNotHasKey(get_class($agent), $status);
    }

    public function testCheckHealth(): void
    {
        $agent = new class {
            public function getHealthStatus(): array
            {
                return ['status' => 'healthy'];
            }
        };

        $this->monitor->registerAgent($agent);
        $results = $this->monitor->checkHealth();

        $this->assertArrayHasKey(get_class($agent), $results);
        $this->assertEquals('healthy', $results[get_class($agent)]['status']);
    }

    public function testCheckHealthWithUnhealthyAgent(): void
    {
        $agent = new class {
            public function getHealthStatus(): array
            {
                throw new \RuntimeException('Agent error');
            }
        };

        $this->monitor->registerAgent($agent);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Health check failed for agent'));

        $results = $this->monitor->checkHealth();

        $this->assertArrayHasKey(get_class($agent), $results);
        $this->assertEquals('unhealthy', $results[get_class($agent)]['status']);
        $this->assertArrayHasKey('error', $results[get_class($agent)]);
    }

    public function testGetHealthStatus(): void
    {
        $agent = new class {
            public function getHealthStatus(): array
            {
                return ['status' => 'healthy'];
            }
        };

        $this->monitor->registerAgent($agent);
        $status = $this->monitor->getHealthStatus();

        $this->assertArrayHasKey(get_class($agent), $status);
        $this->assertArrayHasKey('status', $status[get_class($agent)]);
        $this->assertArrayHasKey('last_check', $status[get_class($agent)]);
    }
} 