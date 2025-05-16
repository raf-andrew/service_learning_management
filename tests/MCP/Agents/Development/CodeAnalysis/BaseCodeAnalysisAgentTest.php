<?php

namespace Tests\MCP\Agents\Development\CodeAnalysis;

use PHPUnit\Framework\TestCase;
use App\MCP\Agents\Development\CodeAnalysis\BaseCodeAnalysisAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

class BaseCodeAnalysisAgentTest extends TestCase
{
    private HealthMonitor $healthMonitor;
    private AgentLifecycleManager $lifecycleManager;
    private LoggerInterface $logger;
    private TestCodeAnalysisAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->agent = new TestCodeAnalysisAgent(
            $this->healthMonitor,
            $this->lifecycleManager,
            $this->logger
        );
    }

    public function testInitialize(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Initializing code analysis agent'));

        $this->healthMonitor->expects($this->once())
            ->method('registerAgent')
            ->with($this->agent);

        $this->lifecycleManager->expects($this->once())
            ->method('registerAgent')
            ->with($this->agent);

        $this->agent->initialize();
    }

    public function testShutdown(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Shutting down code analysis agent'));

        $this->healthMonitor->expects($this->once())
            ->method('unregisterAgent')
            ->with($this->agent);

        $this->lifecycleManager->expects($this->once())
            ->method('unregisterAgent')
            ->with($this->agent);

        $this->agent->shutdown();
    }

    public function testGetHealthStatus(): void
    {
        $status = $this->agent->getHealthStatus();

        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('last_check', $status);
        $this->assertArrayHasKey('metrics', $status);
        $this->assertEquals('healthy', $status['status']);
    }

    public function testAbstractMethods(): void
    {
        $this->assertIsArray($this->agent->getMetrics());
        $this->assertIsArray($this->agent->analyze([]));
        $this->assertIsArray($this->agent->getRecommendations());
        $this->assertIsArray($this->agent->getReport());
    }
}

/**
 * Test implementation of BaseCodeAnalysisAgent
 */
class TestCodeAnalysisAgent extends BaseCodeAnalysisAgent
{
    public function getMetrics(): array
    {
        return ['test' => 1];
    }

    public function analyze(array $files): array
    {
        return ['test' => 'analysis'];
    }

    public function getRecommendations(): array
    {
        return ['test' => 'recommendation'];
    }

    public function getReport(): array
    {
        return ['test' => 'report'];
    }
} 