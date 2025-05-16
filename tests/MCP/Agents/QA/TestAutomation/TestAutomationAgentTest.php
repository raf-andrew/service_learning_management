<?php

namespace Tests\MCP\Agents\QA\TestAutomation;

use PHPUnit\Framework\TestCase;
use App\MCP\Agents\QA\TestAutomation\TestAutomationAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

/**
 * Test case for the Test Automation Agent
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class TestAutomationAgentTest extends TestCase
{
    private TestAutomationAgent $agent;
    private HealthMonitor $healthMonitor;
    private AgentLifecycleManager $lifecycleManager;
    private LoggerInterface $logger;
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->agent = new TestAutomationAgent(
            $this->healthMonitor,
            $this->lifecycleManager,
            $this->logger
        );

        // Create a test file with test cases
        $this->testFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($this->testFile, $this->getTestCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unlink($this->testFile);
    }

    private function getTestCode(): string
    {
        return <<<'EOT'
<?php

namespace Test\Namespace;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testSuccess(): void
    {
        $this->assertTrue(true);
    }

    public function testFailure(): void
    {
        $this->assertTrue(false);
    }

    /**
     * @group skip
     */
    public function testSkipped(): void
    {
        $this->markTestSkipped('This test is skipped');
    }

    public function testError(): void
    {
        throw new \RuntimeException('Test error');
    }
}
EOT;
    }

    public function testAnalyze(): void
    {
        $report = $this->agent->analyze([$this->testFile]);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('test_results', $report);
        $this->assertArrayHasKey('coverage_data', $report);
        $this->assertArrayHasKey('test_suites', $report);
        $this->assertArrayHasKey('summary', $report);

        $this->assertGreaterThan(0, $report['metrics']['tests_run']);
        $this->assertGreaterThan(0, $report['metrics']['tests_failed']);
        $this->assertGreaterThan(0, $report['metrics']['tests_skipped']);
    }

    public function testGetRecommendations(): void
    {
        $this->agent->analyze([$this->testFile]);
        $recommendations = $this->agent->getRecommendations();

        $this->assertNotEmpty($recommendations);
        $this->assertContains('coverage', array_column($recommendations, 'type'));
        $this->assertContains('failures', array_column($recommendations, 'type'));
        $this->assertContains('skipped', array_column($recommendations, 'type'));
    }

    public function testGetReport(): void
    {
        $this->agent->analyze([$this->testFile]);
        $report = $this->agent->getReport();

        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('test_results', $report);
        $this->assertArrayHasKey('coverage_data', $report);
        $this->assertArrayHasKey('test_suites', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('timestamp', $report);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();

        $this->assertArrayHasKey('tests_run', $metrics);
        $this->assertArrayHasKey('tests_passed', $metrics);
        $this->assertArrayHasKey('tests_failed', $metrics);
        $this->assertArrayHasKey('tests_skipped', $metrics);
        $this->assertArrayHasKey('coverage_percentage', $metrics);
        $this->assertArrayHasKey('test_suites_executed', $metrics);
        $this->assertArrayHasKey('total_execution_time', $metrics);
    }

    public function testAnalyzeNonExistentFile(): void
    {
        $report = $this->agent->analyze(['non_existent_file.php']);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(0, $report['metrics']['tests_run']);
    }

    public function testAnalyzeInvalidTestFile(): void
    {
        $invalidFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($invalidFile, 'invalid php code');

        $report = $this->agent->analyze([$invalidFile]);

        unlink($invalidFile);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(0, $report['metrics']['tests_run']);
    }

    public function testErrorLogging(): void
    {
        $errorFile = '.errors/' . basename($this->testFile) . '_' . date('Y-m-d') . '*.log';
        $this->agent->analyze([$this->testFile]);

        $this->assertNotEmpty(glob($errorFile));
    }

    public function testFailureLogging(): void
    {
        $failureFile = '.failures/' . basename($this->testFile) . '_' . date('Y-m-d') . '*.log';
        $this->agent->analyze([$this->testFile]);

        $this->assertNotEmpty(glob($failureFile));
    }
} 