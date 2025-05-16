<?php

namespace Tests\MCP\Agents\Development\CodeAnalysis;

use PHPUnit\Framework\TestCase;
use MCP\Agents\Development\CodeAnalysis\DependencyAnalysisAgent;
use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class DependencyAnalysisAgentTest extends TestCase
{
    private DependencyAnalysisAgent $agent;
    private HealthMonitor|MockObject $healthMonitor;
    private AgentLifecycleManager|MockObject $lifecycleManager;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->agent = new DependencyAnalysisAgent(
            $this->healthMonitor,
            $this->lifecycleManager,
            $this->logger
        );
    }

    public function testInitialization(): void
    {
        $this->healthMonitor->expects($this->once())
            ->method('registerAgent')
            ->with($this->agent);

        $this->lifecycleManager->expects($this->once())
            ->method('registerAgent')
            ->with($this->agent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Initializing code analysis agent'));

        $this->agent->initialize();
    }

    public function testShutdown(): void
    {
        $this->healthMonitor->expects($this->once())
            ->method('unregisterAgent')
            ->with($this->agent);

        $this->lifecycleManager->expects($this->once())
            ->method('unregisterAgent')
            ->with($this->agent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Shutting down code analysis agent'));

        $this->agent->shutdown();
    }

    public function testGetHealthStatus(): void
    {
        $status = $this->agent->getHealthStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('last_check', $status);
        $this->assertArrayHasKey('metrics', $status);
        $this->assertEquals('healthy', $status['status']);
    }

    public function testAnalyzeWithNonExistentFile(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('File not found'));

        $result = $this->agent->analyze(['non_existent_file.php']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('timestamp', $result);
    }

    public function testAnalyzeWithValidFile(): void
    {
        // Create a temporary test file with some dependencies
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php
use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;

class Test {
    public function test() {
        $health = new HealthMonitor();
        AgentLifecycleManager::getInstance();
    }
}');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Starting dependency analysis'));

        $result = $this->agent->analyze([$tempFile]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('timestamp', $result);

        // Clean up
        unlink($tempFile);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_dependencies', $metrics);
        $this->assertArrayHasKey('direct_dependencies', $metrics);
        $this->assertArrayHasKey('indirect_dependencies', $metrics);
        $this->assertArrayHasKey('circular_dependencies', $metrics);
        $this->assertArrayHasKey('version_conflicts', $metrics);
        $this->assertArrayHasKey('security_vulnerabilities', $metrics);
    }

    public function testGetRecommendations(): void
    {
        $recommendations = $this->agent->getRecommendations();

        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('dependencies', $recommendations);
        $this->assertArrayHasKey('circular_dependencies', $recommendations);
        $this->assertArrayHasKey('version_conflicts', $recommendations);
        $this->assertArrayHasKey('security_vulnerabilities', $recommendations);
    }

    public function testGetReport(): void
    {
        $report = $this->agent->getReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('recommendations', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('timestamp', $report);
    }

    public function testAnalyzeWithInvalidPhpFile(): void
    {
        // Create a temporary test file with invalid PHP
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php invalid php code');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error analyzing file'));

        $result = $this->agent->analyze([$tempFile]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('timestamp', $result);

        // Clean up
        unlink($tempFile);
    }

    public function testDependencyAnalysis(): void
    {
        // Create a temporary test file with dependencies
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php
use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;

class Test {
    public function test() {
        $health = new HealthMonitor();
        AgentLifecycleManager::getInstance();
    }
}');

        $result = $this->agent->analyze([$tempFile]);

        $this->assertIsArray($result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['direct_dependencies']);

        // Clean up
        unlink($tempFile);
    }

    public function testCircularDependencyDetection(): void
    {
        // Create temporary test files with circular dependencies
        $tempDir = sys_get_temp_dir();
        $file1 = $tempDir . '/test1.php';
        $file2 = $tempDir . '/test2.php';

        file_put_contents($file1, '<?php
use Test2;
class Test1 {
    public function test() {
        new Test2();
    }
}');

        file_put_contents($file2, '<?php
use Test1;
class Test2 {
    public function test() {
        new Test1();
    }
}');

        $result = $this->agent->analyze([$file1, $file2]);

        $this->assertIsArray($result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['circular_dependencies']);

        // Clean up
        unlink($file1);
        unlink($file2);
    }

    public function testVersionConflictDetection(): void
    {
        // Create a temporary composer.json file
        $tempFile = tempnam(sys_get_temp_dir(), 'composer');
        file_put_contents($tempFile, '{
            "require": {
                "package1": "^1.0",
                "package2": "^2.0"
            }
        }');

        $result = $this->agent->analyze(['test.php']);

        $this->assertIsArray($result['metrics']);
        $this->assertArrayHasKey('version_conflicts', $result['metrics']);

        // Clean up
        unlink($tempFile);
    }

    public function testSecurityVulnerabilityDetection(): void
    {
        // Create a temporary composer.lock file
        $tempFile = tempnam(sys_get_temp_dir(), 'composer');
        file_put_contents($tempFile, '{
            "packages": [
                {
                    "name": "package1",
                    "version": "1.0.0"
                }
            ]
        }');

        $result = $this->agent->analyze(['test.php']);

        $this->assertIsArray($result['metrics']);
        $this->assertArrayHasKey('security_vulnerabilities', $result['metrics']);

        // Clean up
        unlink($tempFile);
    }
} 