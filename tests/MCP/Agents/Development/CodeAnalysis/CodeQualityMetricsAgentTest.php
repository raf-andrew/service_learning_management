<?php

namespace Tests\MCP\Agents\Development\CodeAnalysis;

use Tests\MCP\BaseTestCase;
use App\MCP\Agents\Development\CodeAnalysis\CodeQualityMetricsAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

class CodeQualityMetricsAgentTest extends BaseTestCase
{
    protected CodeQualityMetricsAgent $agent;
    protected HealthMonitor $healthMonitor;
    protected AgentLifecycleManager $lifecycleManager;
    protected LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->agent = new CodeQualityMetricsAgent(
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
        $this->assertArrayHasKey('last_check', $status);
        $this->assertArrayHasKey('metrics', $status);
        $this->assertEquals('healthy', $status['status']);
    }

    public function testAnalyzeWithNonExistentFile(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('File not found: non_existent.php');
            
        $results = $this->agent->analyze(['non_existent.php']);
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testAnalyzeWithValidFile(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php class Test {}');
        
        $results = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey($testFile, $results);
        $this->assertArrayHasKey('complexity', $results[$testFile]);
        $this->assertArrayHasKey('maintainability', $results[$testFile]);
        $this->assertArrayHasKey('documentation', $results[$testFile]);
        $this->assertArrayHasKey('style', $results[$testFile]);
        
        unlink($testFile);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('complexity', $metrics);
        $this->assertArrayHasKey('maintainability', $metrics);
        $this->assertArrayHasKey('documentation', $metrics);
        $this->assertArrayHasKey('test_coverage', $metrics);
        $this->assertArrayHasKey('code_style', $metrics);
    }

    public function testGetRecommendations(): void
    {
        $recommendations = $this->agent->getRecommendations();
        
        $this->assertIsArray($recommendations);
    }

    public function testGetReport(): void
    {
        $report = $this->agent->getReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('recommendations', $report);
    }

    public function testAnalyzeWithInvalidPhpFile(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php invalid php code');
        
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error analyzing file'));
            
        $results = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey($testFile, $results);
        $this->assertArrayHasKey('error', $results[$testFile]);
        
        unlink($testFile);
    }

    public function testStylePatterns(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
            class TestClass {
                public function testMethod() {
                    $variable = "test";
                    return $variable;
                }
            }
        ');
        
        $results = $this->agent->analyze([$testFile]);
        
        $this->assertIsArray($results[$testFile]['style']);
        $this->assertArrayHasKey('psr_compliance', $results[$testFile]['style']);
        $this->assertArrayHasKey('naming_conventions', $results[$testFile]['style']);
        $this->assertArrayHasKey('code_formatting', $results[$testFile]['style']);
        
        unlink($testFile);
    }

    public function testOverallStyleScore(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
            class TestClass {
                public function testMethod() {
                    return true;
                }
            }
        ');
        
        $this->agent->analyze([$testFile]);
        $report = $this->agent->getReport();
        
        $this->assertIsFloat($report['summary']['overall_quality']);
        $this->assertGreaterThanOrEqual(0, $report['summary']['overall_quality']);
        $this->assertLessThanOrEqual(1, $report['summary']['overall_quality']);
        
        unlink($testFile);
    }

    public function testCriticalIssues(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
            class TestClass {
                public function complexMethod() {
                    if (true) {
                        if (true) {
                            if (true) {
                                if (true) {
                                    if (true) {
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ');
        
        $this->agent->analyze([$testFile]);
        $report = $this->agent->getReport();
        
        $this->assertIsArray($report['summary']['critical_issues']);
        $this->assertNotEmpty($report['summary']['critical_issues']);
        
        unlink($testFile);
    }

    public function testImprovementAreas(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile, '<?php
            class TestClass {
                public function testMethod() {
                    return true;
                }
            }
        ');
        
        $this->agent->analyze([$testFile]);
        $report = $this->agent->getReport();
        
        $this->assertIsArray($report['summary']['improvement_areas']);
        
        unlink($testFile);
    }
} 