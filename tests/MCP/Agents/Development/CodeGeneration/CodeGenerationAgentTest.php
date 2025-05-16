<?php

namespace Tests\MCP\Agents\Development\CodeGeneration;

use PHPUnit\Framework\TestCase;
use MCP\Agents\Development\CodeGeneration\CodeGenerationAgent;
use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use PhpParser\Node;

/**
 * Test case for the Code Generation Agent
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class CodeGenerationAgentTest extends TestCase
{
    private CodeGenerationAgent $agent;
    private HealthMonitor $healthMonitor;
    private AgentLifecycleManager $lifecycleManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        $this->lifecycleManager = $this->createMock(AgentLifecycleManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->agent = new CodeGenerationAgent(
            $this->healthMonitor,
            $this->lifecycleManager,
            $this->logger
        );
    }

    public function testGenerateInterface(): void
    {
        $className = 'TestClass';
        $methods = [
            [
                'name' => 'testMethod',
                'params' => [
                    new Node\Param(new Node\Expr\Variable('param1'), null, 'string'),
                    new Node\Param(new Node\Expr\Variable('param2'), null, 'int')
                ],
                'return' => 'bool'
            ]
        ];

        $code = $this->agent->generateInterface($className, $methods);

        $this->assertStringContainsString('interface ITestClass', $code);
        $this->assertStringContainsString('public function testMethod', $code);
        $this->assertStringContainsString('string $param1', $code);
        $this->assertStringContainsString('int $param2', $code);
        $this->assertStringContainsString(': bool', $code);
    }

    public function testGenerateTest(): void
    {
        $className = 'TestClass';
        $methods = [
            [
                'name' => 'testMethod',
                'params' => [],
                'return' => 'void'
            ]
        ];

        $code = $this->agent->generateTest($className, $methods);

        $this->assertStringContainsString('class TestClassTest extends TestCase', $code);
        $this->assertStringContainsString('protected TestClass $testClass', $code);
        $this->assertStringContainsString('public function testTestMethod', $code);
        $this->assertStringContainsString('markTestIncomplete', $code);
    }

    public function testGenerateDocumentation(): void
    {
        $className = self::class;
        $documentation = $this->agent->generateDocumentation($className);

        $this->assertStringContainsString('# CodeGenerationAgentTest', $documentation);
        $this->assertStringContainsString('## Properties', $documentation);
        $this->assertStringContainsString('## Methods', $documentation);
        $this->assertStringContainsString('### testGenerateInterface', $documentation);
    }

    public function testGenerateBoilerplate(): void
    {
        $type = 'class';
        $config = [
            'namespace' => 'Test\\Namespace',
            'name' => 'TestClass',
            'properties' => 'private string $property;',
            'methods' => 'public function method(): void {}'
        ];

        $code = $this->agent->generateBoilerplate($type, $config);

        $this->assertStringContainsString('namespace Test\\Namespace;', $code);
        $this->assertStringContainsString('class TestClass', $code);
        $this->assertStringContainsString('private string $property;', $code);
        $this->assertStringContainsString('public function method(): void {}', $code);
    }

    public function testAnalyze(): void
    {
        $files = [__FILE__];
        $report = $this->agent->analyze($files);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('timestamp', $report);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->agent->getMetrics();

        $this->assertArrayHasKey('interfaces_generated', $metrics);
        $this->assertArrayHasKey('tests_generated', $metrics);
        $this->assertArrayHasKey('docs_generated', $metrics);
        $this->assertArrayHasKey('boilerplate_generated', $metrics);
        $this->assertArrayHasKey('templates_used', $metrics);
    }

    public function testGetReport(): void
    {
        $report = $this->agent->getReport();

        $this->assertIsArray($report);
    }

    public function testInvalidTemplateType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown template type: invalid');

        $this->agent->generateBoilerplate('invalid', []);
    }

    public function testInvalidClassName(): void
    {
        $this->expectException(\ReflectionException::class);

        $this->agent->generateDocumentation('NonExistentClass');
    }

    public function testAnalyzeNonExistentFile(): void
    {
        $files = ['non_existent_file.php'];
        $report = $this->agent->analyze($files);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(0, array_sum($report['metrics']));
    }

    public function testAnalyzeInvalidPhpFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'invalid php code');

        $files = [$tempFile];
        $report = $this->agent->analyze($files);

        unlink($tempFile);

        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(0, array_sum($report['metrics']));
    }
} 