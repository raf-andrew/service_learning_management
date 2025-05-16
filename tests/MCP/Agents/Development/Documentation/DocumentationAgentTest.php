<?php

namespace Tests\MCP\Agents\Development\Documentation;

use PHPUnit\Framework\TestCase;
use App\MCP\Agents\Development\Documentation\DocumentationAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;

/**
 * Test case for the Documentation Agent
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class DocumentationAgentTest extends TestCase
{
    private DocumentationAgent $agent;
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

        $this->agent = new DocumentationAgent(
            $this->healthMonitor,
            $this->lifecycleManager,
            $this->logger
        );

        // Create a test file with various documentation scenarios
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

/**
 * Test class with complete documentation
 * 
 * This class demonstrates various documentation scenarios
 * 
 * @package Test\Namespace
 */
class TestClass
{
    /**
     * A well-documented property
     * 
     * @var string
     */
    private string $documentedProperty;

    /**
     * A property without proper documentation
     */
    private $undocumentedProperty;

    /**
     * A well-documented method
     * 
     * @param string $param1 First parameter
     * @param int $param2 Second parameter
     * @return bool Result of the operation
     * @throws \RuntimeException When something goes wrong
     */
    public function documentedMethod(string $param1, int $param2): bool
    {
        return true;
    }

    public function undocumentedMethod($param)
    {
        return null;
    }

    /**
     * API endpoint for testing
     * 
     * @api
     * @param string $data Input data
     * @return array Response data
     */
    public function apiEndpoint(string $data): array
    {
        return ['status' => 'success'];
    }

    /**
     * Method with deprecated tag
     * 
     * @deprecated Use newMethod() instead
     */
    public function oldMethod()
    {
    }

    /**
     * Method with proper deprecated tag
     * 
     * @deprecated since 2.0.0 Use newMethod() instead
     */
    public function properDeprecatedMethod()
    {
    }
}
EOT;
    }

    public function testAnalyze(): void
    {
        $report = $this->agent->analyze([$this->testFile]);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('doc_blocks', $report);
        $this->assertArrayHasKey('api_endpoints', $report);
        $this->assertArrayHasKey('usage_examples', $report);
        $this->assertArrayHasKey('summary', $report);

        $this->assertGreaterThan(0, $report['metrics']['doc_blocks_analyzed']);
        $this->assertGreaterThan(0, $report['metrics']['doc_blocks_validated']);
        $this->assertGreaterThan(0, $report['metrics']['api_endpoints_documented']);
    }

    public function testGetRecommendations(): void
    {
        $this->agent->analyze([$this->testFile]);
        $recommendations = $this->agent->getRecommendations();

        $this->assertNotEmpty($recommendations);
        $this->assertContains('documentation', array_column($recommendations, 'type'));
        $this->assertContains('warning', array_column($recommendations, 'severity'));
    }

    public function testGetReport(): void
    {
        $this->agent->analyze([$this->testFile]);
        $report = $this->agent->getReport();

        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('doc_blocks', $report);
        $this->assertArrayHasKey('api_endpoints', $report);
        $this->assertArrayHasKey('usage_examples', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('timestamp', $report);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();

        $this->assertArrayHasKey('doc_blocks_analyzed', $metrics);
        $this->assertArrayHasKey('doc_blocks_generated', $metrics);
        $this->assertArrayHasKey('doc_blocks_validated', $metrics);
        $this->assertArrayHasKey('api_endpoints_documented', $metrics);
        $this->assertArrayHasKey('examples_generated', $metrics);
    }

    public function testAnalyzeNonExistentFile(): void
    {
        $report = $this->agent->analyze(['non_existent_file.php']);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(0, array_sum($report['metrics']));
    }

    public function testAnalyzeInvalidPhpFile(): void
    {
        $invalidFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($invalidFile, 'invalid php code');

        $report = $this->agent->analyze([$invalidFile]);

        unlink($invalidFile);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(0, array_sum($report['metrics']));
    }

    public function testValidateDocumentation(): void
    {
        $issues = $this->agent->validateDocumentation($this->testFile);

        $this->assertIsArray($issues);
        $this->assertNotEmpty($issues);

        $issueTypes = array_column($issues, 'type');
        $this->assertContains('missing_doc', $issueTypes);
        $this->assertContains('invalid_doc', $issueTypes);

        $elements = array_column($issues, 'element');
        $this->assertContains('method undocumentedMethod', $elements);
    }

    public function testGenerateDocumentation(): void
    {
        $documentation = $this->agent->generateDocumentation($this->testFile);

        $this->assertStringContainsString('# ' . basename($this->testFile), $documentation);
        $this->assertStringContainsString('## Class: TestClass', $documentation);
        $this->assertStringContainsString('### Properties', $documentation);
        $this->assertStringContainsString('### Methods', $documentation);
        $this->assertStringContainsString('documentedMethod', $documentation);
        $this->assertStringContainsString('apiEndpoint', $documentation);
    }
} 