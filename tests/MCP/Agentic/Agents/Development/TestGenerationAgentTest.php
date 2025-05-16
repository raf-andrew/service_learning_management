<?php

namespace Tests\MCP\Agentic\Agents\Development;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\Development\TestGenerationAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use Mockery;

class TestGenerationAgentTest extends BaseAgenticTestCase
{
    protected TestGenerationAgent $agent;
    protected TaskManager $taskManager;
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = Mockery::mock(TaskManager::class);
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        $this->accessControl = Mockery::mock(AccessControl::class);

        $this->agent = new TestGenerationAgent(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager
        );
    }

    public function test_agent_initialization()
    {
        $this->assertEquals('test_generation', $this->agent->getType());
        $this->assertContains('unit_test_generation', $this->agent->getCapabilities());
        $this->assertContains('integration_test_generation', $this->agent->getCapabilities());
        $this->assertContains('edge_case_identification', $this->agent->getCapabilities());
        $this->assertContains('coverage_analysis', $this->agent->getCapabilities());
        $this->assertContains('test_optimization', $this->agent->getCapabilities());
    }

    public function test_can_generate_unit_tests()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')
            ->with('generate_unit_tests', ['path' => $path])
            ->once();

        $this->auditLogger->shouldReceive('log')
            ->with('unit_tests_complete', Mockery::any())
            ->once();

        $results = $this->agent->generateUnitTests($path);

        $this->assertArrayHasKey('method_tests', $results);
        $this->assertArrayHasKey('property_tests', $results);
        $this->assertArrayHasKey('edge_cases', $results);
        $this->assertArrayHasKey('mocks', $results);
        $this->assertArrayHasKey('assertions', $results);
    }

    public function test_cannot_generate_unit_tests_in_production()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test generation not allowed in current environment');

        $this->agent->generateUnitTests($path);
    }

    public function test_can_generate_integration_tests()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')
            ->with('generate_integration_tests', ['path' => $path])
            ->once();

        $this->auditLogger->shouldReceive('log')
            ->with('integration_tests_complete', Mockery::any())
            ->once();

        $results = $this->agent->generateIntegrationTests($path);

        $this->assertArrayHasKey('service_tests', $results);
        $this->assertArrayHasKey('api_tests', $results);
        $this->assertArrayHasKey('database_tests', $results);
        $this->assertArrayHasKey('external_service_tests', $results);
        $this->assertArrayHasKey('state_tests', $results);
    }

    public function test_cannot_generate_integration_tests_in_production()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test generation not allowed in current environment');

        $this->agent->generateIntegrationTests($path);
    }

    public function test_can_analyze_coverage()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')
            ->with('analyze_coverage', ['path' => $path])
            ->once();

        $this->auditLogger->shouldReceive('log')
            ->with('coverage_analysis_complete', Mockery::any())
            ->once();

        $results = $this->agent->analyzeCoverage($path);

        $this->assertArrayHasKey('line_coverage', $results);
        $this->assertArrayHasKey('branch_coverage', $results);
        $this->assertArrayHasKey('path_coverage', $results);
        $this->assertArrayHasKey('dead_code', $results);
        $this->assertArrayHasKey('coverage_report', $results);
    }

    public function test_cannot_analyze_coverage_in_production()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Coverage analysis not allowed in current environment');

        $this->agent->analyzeCoverage($path);
    }

    public function test_can_optimize_tests()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('testing');

        $this->auditLogger->shouldReceive('log')
            ->with('optimize_tests', ['path' => $path])
            ->once();

        $this->auditLogger->shouldReceive('log')
            ->with('test_optimization_complete', Mockery::any())
            ->once();

        $results = $this->agent->optimizeTests($path);

        $this->assertArrayHasKey('suite_optimization', $results);
        $this->assertArrayHasKey('case_prioritization', $results);
        $this->assertArrayHasKey('redundant_removal', $results);
        $this->assertArrayHasKey('execution_optimization', $results);
        $this->assertArrayHasKey('resource_optimization', $results);
    }

    public function test_cannot_optimize_tests_in_production()
    {
        $path = 'tests/fixtures/code';
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test optimization not allowed in current environment');

        $this->agent->optimizeTests($path);
    }

    protected function setupEnvironment(string $environment): void
    {
        $this->agent->setEnvironment($environment);
    }
} 