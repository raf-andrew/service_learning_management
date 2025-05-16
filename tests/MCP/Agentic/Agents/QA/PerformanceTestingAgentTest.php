<?php

namespace Tests\MCP\Agentic\Agents\QA;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\QA\PerformanceTestingAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;

class PerformanceTestingAgentTest extends BaseAgenticTestCase
{
    private PerformanceTestingAgent $agent;
    private TaskManager $taskManager;
    private AuditLogger $auditLogger;
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = $this->createMock(TaskManager::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);

        $this->agent = new PerformanceTestingAgent(
            $this->taskManager,
            $this->auditLogger,
            $this->accessControl
        );
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('performance_testing', $this->agent->getType());
        $this->assertEquals([
            'load_testing',
            'stress_testing',
            'bottleneck_identification',
            'performance_reporting',
            'optimization_suggestions',
        ], $this->agent->getCapabilities());
    }

    public function test_can_run_load_tests(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['run_load_tests', ['path' => 'test/path']],
                ['load_testing_complete', $this->anything()]
            );

        $results = $this->agent->runLoadTests('test/path');

        $this->assertArrayHasKey('concurrent_users', $results);
        $this->assertArrayHasKey('request_rates', $results);
        $this->assertArrayHasKey('response_times', $results);
        $this->assertArrayHasKey('resource_utilization', $results);
        $this->assertArrayHasKey('performance_degradation', $results);
    }

    public function test_cannot_run_load_tests_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Load testing not allowed in current environment');

        $this->agent->runLoadTests('test/path');
    }

    public function test_can_run_stress_tests(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['run_stress_tests', ['path' => 'test/path']],
                ['stress_testing_complete', $this->anything()]
            );

        $results = $this->agent->runStressTests('test/path');

        $this->assertArrayHasKey('system_capacity', $results);
        $this->assertArrayHasKey('breaking_point', $results);
        $this->assertArrayHasKey('recovery_testing', $results);
        $this->assertArrayHasKey('resource_exhaustion', $results);
        $this->assertArrayHasKey('error_handling', $results);
    }

    public function test_cannot_run_stress_tests_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stress testing not allowed in current environment');

        $this->agent->runStressTests('test/path');
    }

    public function test_can_identify_bottlenecks(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['identify_bottlenecks', ['path' => 'test/path']],
                ['bottleneck_identification_complete', $this->anything()]
            );

        $results = $this->agent->identifyBottlenecks('test/path');

        $this->assertArrayHasKey('cpu_usage', $results);
        $this->assertArrayHasKey('memory_utilization', $results);
        $this->assertArrayHasKey('io_performance', $results);
        $this->assertArrayHasKey('network_latency', $results);
        $this->assertArrayHasKey('database_performance', $results);
    }

    public function test_cannot_identify_bottlenecks_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bottleneck identification not allowed in current environment');

        $this->agent->identifyBottlenecks('test/path');
    }

    public function test_can_generate_performance_report(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['generate_performance_report', ['path' => 'test/path']],
                ['performance_reporting_complete', $this->anything()]
            );

        $results = $this->agent->generatePerformanceReport('test/path');

        $this->assertArrayHasKey('real_time_metrics', $results);
        $this->assertArrayHasKey('historical_trends', $results);
        $this->assertArrayHasKey('performance_comparison', $results);
        $this->assertArrayHasKey('resource_usage', $results);
        $this->assertArrayHasKey('response_time_analysis', $results);
    }

    public function test_cannot_generate_performance_report_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Performance reporting not allowed in current environment');

        $this->agent->generatePerformanceReport('test/path');
    }

    public function test_can_suggest_optimizations(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['suggest_optimizations', ['path' => 'test/path']],
                ['optimization_suggestions_complete', $this->anything()]
            );

        $results = $this->agent->suggestOptimizations('test/path');

        $this->assertArrayHasKey('code_optimizations', $results);
        $this->assertArrayHasKey('resource_allocation', $results);
        $this->assertArrayHasKey('cache_optimizations', $results);
        $this->assertArrayHasKey('query_optimizations', $results);
        $this->assertArrayHasKey('configuration_tuning', $results);
    }

    public function test_cannot_suggest_optimizations_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Optimization suggestions not allowed in current environment');

        $this->agent->suggestOptimizations('test/path');
    }

    private function setupEnvironment(string $environment): void
    {
        $this->accessControl->method('getEnvironment')
            ->willReturn($environment);
    }
} 