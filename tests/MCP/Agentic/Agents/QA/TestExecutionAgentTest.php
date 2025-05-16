<?php

namespace Tests\MCP\Agentic\Agents\QA;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\QA\TestExecutionAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;

class TestExecutionAgentTest extends BaseAgenticTestCase
{
    private TestExecutionAgent $agent;
    private TaskManager $taskManager;
    private AuditLogger $auditLogger;
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = $this->createMock(TaskManager::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);

        $this->agent = new TestExecutionAgent(
            $this->taskManager,
            $this->auditLogger,
            $this->accessControl
        );
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('test_execution', $this->agent->getType());
        $this->assertEquals([
            'test_scheduling',
            'test_execution',
            'failure_reporting',
            'coverage_tracking',
            'test_optimization',
        ], $this->agent->getCapabilities());
    }

    public function test_can_schedule_tests(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['schedule_tests', ['path' => 'test/path']],
                ['test_scheduling_complete', $this->anything()]
            );

        $results = $this->agent->scheduleTests('test/path');

        $this->assertArrayHasKey('suite_schedule', $results);
        $this->assertArrayHasKey('priority_execution', $results);
        $this->assertArrayHasKey('resource_allocation', $results);
        $this->assertArrayHasKey('dependency_management', $results);
        $this->assertArrayHasKey('schedule_optimization', $results);
    }

    public function test_cannot_schedule_tests_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test scheduling not allowed in current environment');

        $this->agent->scheduleTests('test/path');
    }

    public function test_can_execute_tests(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['execute_tests', ['path' => 'test/path']],
                ['test_execution_complete', $this->anything()]
            );

        $results = $this->agent->executeTests('test/path');

        $this->assertArrayHasKey('unit_tests', $results);
        $this->assertArrayHasKey('integration_tests', $results);
        $this->assertArrayHasKey('e2e_tests', $results);
        $this->assertArrayHasKey('performance_tests', $results);
        $this->assertArrayHasKey('security_tests', $results);
    }

    public function test_cannot_execute_tests_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test execution not allowed in current environment');

        $this->agent->executeTests('test/path');
    }

    public function test_can_report_failures(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['report_failures', ['path' => 'test/path']],
                ['failure_reporting_complete', $this->anything()]
            );

        $results = $this->agent->reportFailures('test/path');

        $this->assertArrayHasKey('failure_analysis', $results);
        $this->assertArrayHasKey('error_categorization', $results);
        $this->assertArrayHasKey('stack_trace', $results);
        $this->assertArrayHasKey('environment_state', $results);
        $this->assertArrayHasKey('reproduction_steps', $results);
    }

    public function test_cannot_report_failures_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failure reporting not allowed in current environment');

        $this->agent->reportFailures('test/path');
    }

    public function test_can_track_coverage(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['track_coverage', ['path' => 'test/path']],
                ['coverage_tracking_complete', $this->anything()]
            );

        $results = $this->agent->trackCoverage('test/path');

        $this->assertArrayHasKey('line_coverage', $results);
        $this->assertArrayHasKey('branch_coverage', $results);
        $this->assertArrayHasKey('path_coverage', $results);
        $this->assertArrayHasKey('dead_code', $results);
        $this->assertArrayHasKey('coverage_trends', $results);
    }

    public function test_cannot_track_coverage_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Coverage tracking not allowed in current environment');

        $this->agent->trackCoverage('test/path');
    }

    public function test_can_optimize_tests(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['optimize_tests', ['path' => 'test/path']],
                ['test_optimization_complete', $this->anything()]
            );

        $results = $this->agent->optimizeTests('test/path');

        $this->assertArrayHasKey('suite_optimization', $results);
        $this->assertArrayHasKey('execution_optimization', $results);
        $this->assertArrayHasKey('resource_optimization', $results);
        $this->assertArrayHasKey('case_prioritization', $results);
        $this->assertArrayHasKey('redundant_removal', $results);
    }

    public function test_cannot_optimize_tests_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test optimization not allowed in current environment');

        $this->agent->optimizeTests('test/path');
    }

    private function setupEnvironment(string $environment): void
    {
        $this->accessControl->method('getEnvironment')
            ->willReturn($environment);
    }
} 