<?php

namespace Tests\MCP\Agentic\Agents\Development;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\Development\CodeAnalysisAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use Symfony\Component\Process\Process;
use Mockery;

class CodeAnalysisAgentTest extends BaseAgenticTestCase
{
    protected CodeAnalysisAgent $agent;
    protected TaskManager $taskManager;
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = Mockery::mock(TaskManager::class);
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        $this->accessControl = Mockery::mock(AccessControl::class);

        $this->agent = new CodeAnalysisAgent(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager
        );
    }

    public function test_agent_initialization()
    {
        $this->assertEquals('code_analysis', $this->agent->getType());
        $this->assertContains('static_analysis', $this->agent->getCapabilities());
        $this->assertContains('complexity_metrics', $this->agent->getCapabilities());
        $this->assertContains('code_smell_detection', $this->agent->getCapabilities());
        $this->assertContains('best_practice_validation', $this->agent->getCapabilities());
        $this->assertContains('documentation_generation', $this->agent->getCapabilities());
    }

    public function test_can_analyze_code()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')
            ->with('analyze_code', ['path' => $path])
            ->once();

        $this->auditLogger->shouldReceive('log')
            ->with('analysis_complete', Mockery::any())
            ->once();

        $results = $this->agent->analyzeCode($path);

        $this->assertArrayHasKey('static_analysis', $results);
        $this->assertArrayHasKey('complexity_metrics', $results);
        $this->assertArrayHasKey('code_smells', $results);
        $this->assertArrayHasKey('best_practices', $results);
    }

    public function test_cannot_analyze_code_in_production()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Code analysis not allowed in current environment');

        $this->agent->analyzeCode($path);
    }

    public function test_can_generate_documentation()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')
            ->with('generate_documentation', ['path' => $path])
            ->once();

        $this->auditLogger->shouldReceive('log')
            ->with('documentation_complete', Mockery::any())
            ->once();

        $results = $this->agent->generateDocumentation($path);

        $this->assertArrayHasKey('phpdoc', $results);
        $this->assertArrayHasKey('api_docs', $results);
        $this->assertArrayHasKey('architecture', $results);
        $this->assertArrayHasKey('dependencies', $results);
        $this->assertArrayHasKey('changelog', $results);
    }

    public function test_cannot_generate_documentation_in_production()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Documentation generation not allowed in current environment');

        $this->agent->generateDocumentation($path);
    }

    public function test_static_analysis_runs_tools()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')->times(2);

        $results = $this->agent->analyzeCode($path);

        $this->assertArrayHasKey('phpstan', $results['static_analysis']);
        $this->assertArrayHasKey('phpcs', $results['static_analysis']);
    }

    public function test_complexity_metrics_calculation()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')->times(2);

        $results = $this->agent->analyzeCode($path);

        $this->assertArrayHasKey('phploc', $results['complexity_metrics']);
    }

    public function test_code_smell_detection()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')->times(2);

        $results = $this->agent->analyzeCode($path);

        $this->assertArrayHasKey('phpmd', $results['code_smells']);
    }

    public function test_best_practices_validation()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')->times(2);

        $results = $this->agent->analyzeCode($path);

        $this->assertArrayHasKey('solid_principles', $results['best_practices']);
        $this->assertArrayHasKey('design_patterns', $results['best_practices']);
        $this->assertArrayHasKey('naming_conventions', $results['best_practices']);
        $this->assertArrayHasKey('documentation', $results['best_practices']);
        $this->assertArrayHasKey('error_handling', $results['best_practices']);
        $this->assertArrayHasKey('security', $results['best_practices']);
    }

    protected function setupEnvironment(string $environment): void
    {
        $this->agent->setEnvironment($environment);
    }
} 