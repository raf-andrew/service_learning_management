<?php

namespace Tests\MCP\Agents\Development\CodeAnalysis;

use PHPUnit\Framework\TestCase;
use MCP\Agents\Development\CodeAnalysis\CodeStyleAnalysisAgent;
use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

class CodeStyleAnalysisAgentTest extends TestCase
{
    private CodeStyleAnalysisAgent $agent;
    private HealthMonitor $healthMonitor;
    private AgentLifecycleManager $lifecycleManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->agent = new CodeStyleAnalysisAgent(
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

        $this->agent->shutdown();
    }

    public function testGetHealthStatus(): void
    {
        $status = $this->agent->getHealthStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('timestamp', $status);
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
    }

    public function testAnalyzeWithValidFile(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php class TestClass {}');

        $result = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('timestamp', $result);

        unlink($testFile);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('psr_compliance', $metrics);
        $this->assertArrayHasKey('naming_conventions', $metrics);
        $this->assertArrayHasKey('code_formatting', $metrics);
        $this->assertArrayHasKey('best_practices', $metrics);
    }

    public function testGetRecommendations(): void
    {
        $recommendations = $this->agent->getRecommendations();
        
        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('psr_compliance', $recommendations);
        $this->assertArrayHasKey('naming_conventions', $recommendations);
        $this->assertArrayHasKey('code_formatting', $recommendations);
        $this->assertArrayHasKey('best_practices', $recommendations);
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
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php invalid php code');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Failed to parse file'));

        $result = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        unlink($testFile);
    }

    public function testStylePatterns(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
class TestClass {
    public function testMethod() {
        $variable = 1;
        const TEST_CONSTANT = 2;
    }
}');

        $result = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        unlink($testFile);
    }

    public function testOverallStyleScore(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
class TestClass {
    public function testMethod() {
        $variable = 1;
        const TEST_CONSTANT = 2;
    }
}');

        $result = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('overall_style_score', $result['summary']);
        $this->assertIsInt($result['summary']['overall_style_score']);
        $this->assertGreaterThanOrEqual(0, $result['summary']['overall_style_score']);
        $this->assertLessThanOrEqual(100, $result['summary']['overall_style_score']);

        unlink($testFile);
    }

    public function testCriticalIssues(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
class test_class {
    function test_method() {
        $Variable = 1;
        const test_constant = 2;
    }
}');

        $result = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('critical_issues', $result['summary']);
        $this->assertIsArray($result['summary']['critical_issues']);

        unlink($testFile);
    }

    public function testImprovementAreas(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
class TestClass {
    function testMethod() {
        $variable = 1;
        const TEST_CONSTANT = 2;
    }
}');

        $result = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('improvement_areas', $result['summary']);
        $this->assertIsArray($result['summary']['improvement_areas']);

        unlink($testFile);
    }
} 