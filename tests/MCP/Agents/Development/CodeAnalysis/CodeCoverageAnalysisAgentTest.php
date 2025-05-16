<?php

namespace Tests\MCP\Agents\Development\CodeAnalysis;

use PHPUnit\Framework\TestCase;
use MCP\Agents\Development\CodeAnalysis\CodeCoverageAnalysisAgent;
use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use SebastianBergmann\CodeCoverage\CodeCoverage;

class CodeCoverageAnalysisAgentTest extends TestCase
{
    private CodeCoverageAnalysisAgent $agent;
    private HealthMonitor $healthMonitor;
    private AgentLifecycleManager $lifecycleManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->agent = new CodeCoverageAnalysisAgent(
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
    }

    public function testAnalyzeWithValidFile(): void
    {
        // Create a temporary PHP file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php function test() { return true; }');

        $result = $this->agent->analyze([$tempFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        unlink($tempFile);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('line_coverage', $metrics);
        $this->assertArrayHasKey('branch_coverage', $metrics);
        $this->assertArrayHasKey('function_coverage', $metrics);
        $this->assertArrayHasKey('class_coverage', $metrics);
        $this->assertArrayHasKey('method_coverage', $metrics);
    }

    public function testGetRecommendations(): void
    {
        $recommendations = $this->agent->getRecommendations();
        
        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('line_coverage', $recommendations);
        $this->assertArrayHasKey('branch_coverage', $recommendations);
        $this->assertArrayHasKey('function_coverage', $recommendations);
        $this->assertArrayHasKey('class_coverage', $recommendations);
        $this->assertArrayHasKey('method_coverage', $recommendations);
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
        // Create a temporary invalid PHP file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php invalid syntax');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error analyzing file'));

        $result = $this->agent->analyze([$tempFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        unlink($tempFile);
    }

    public function testCoverageAnalysis(): void
    {
        // Create a temporary PHP file with testable code
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php
            class TestClass {
                public function testMethod() {
                    return true;
                }
            }
        ');

        $result = $this->agent->analyze([$tempFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        $metrics = $result['metrics'];
        $this->assertIsNumeric($metrics['line_coverage']);
        $this->assertIsNumeric($metrics['branch_coverage']);
        $this->assertIsNumeric($metrics['function_coverage']);
        $this->assertIsNumeric($metrics['class_coverage']);
        $this->assertIsNumeric($metrics['method_coverage']);

        unlink($tempFile);
    }

    public function testCoverageThresholds(): void
    {
        // Create a temporary PHP file with minimal coverage
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php
            function test() {
                if (true) {
                    return true;
                }
                return false;
            }
        ');

        $result = $this->agent->analyze([$tempFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        
        $recommendations = $result['recommendations'];
        $this->assertNotEmpty($recommendations['line_coverage']);
        $this->assertNotEmpty($recommendations['branch_coverage']);
        $this->assertNotEmpty($recommendations['function_coverage']);

        unlink($tempFile);
    }

    public function testSummaryGeneration(): void
    {
        // Create a temporary PHP file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php function test() { return true; }');

        $result = $this->agent->analyze([$tempFile]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        
        $summary = $result['summary'];
        $this->assertArrayHasKey('overall_coverage', $summary);
        $this->assertArrayHasKey('critical_areas', $summary);
        $this->assertArrayHasKey('improvement_areas', $summary);
        
        $this->assertIsNumeric($summary['overall_coverage']);
        $this->assertIsArray($summary['critical_areas']);
        $this->assertIsArray($summary['improvement_areas']);

        unlink($tempFile);
    }
} 