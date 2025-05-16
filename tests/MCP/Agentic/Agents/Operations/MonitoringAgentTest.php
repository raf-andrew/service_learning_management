<?php

namespace Tests\MCP\Agentic\Agents\Operations;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\Operations\MonitoringAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;

class MonitoringAgentTest extends BaseAgenticTestCase
{
    private MonitoringAgent $agent;
    private TaskManager $taskManager;
    private AuditLogger $auditLogger;
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = $this->createMock(TaskManager::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);

        $this->agent = new MonitoringAgent(
            $this->taskManager,
            $this->auditLogger,
            $this->accessControl
        );
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('monitoring', $this->agent->getType());
        $this->assertEquals([
            'system_monitoring',
            'performance_monitoring',
            'error_tracking',
            'alert_management',
            'health_checks',
        ], $this->agent->getCapabilities());
    }

    public function test_can_monitor_system(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['monitor_system', ['path' => 'test/path']],
                ['system_monitoring_complete', $this->anything()]
            );

        $results = $this->agent->monitorSystem('test/path');

        $this->assertArrayHasKey('system_resources', $results);
        $this->assertArrayHasKey('process_status', $results);
        $this->assertArrayHasKey('service_status', $results);
        $this->assertArrayHasKey('network_status', $results);
        $this->assertArrayHasKey('storage_status', $results);
    }

    public function test_cannot_monitor_system_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('System monitoring not allowed in current environment');

        $this->agent->monitorSystem('test/path');
    }

    public function test_can_monitor_performance(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['monitor_performance', ['path' => 'test/path']],
                ['performance_monitoring_complete', $this->anything()]
            );

        $results = $this->agent->monitorPerformance('test/path');

        $this->assertArrayHasKey('response_times', $results);
        $this->assertArrayHasKey('throughput', $results);
        $this->assertArrayHasKey('resource_utilization', $results);
        $this->assertArrayHasKey('performance_metrics', $results);
        $this->assertArrayHasKey('performance_trends', $results);
    }

    public function test_cannot_monitor_performance_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Performance monitoring not allowed in current environment');

        $this->agent->monitorPerformance('test/path');
    }

    public function test_can_track_errors(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['track_errors', ['path' => 'test/path']],
                ['error_tracking_complete', $this->anything()]
            );

        $results = $this->agent->trackErrors('test/path');

        $this->assertArrayHasKey('error_detection', $results);
        $this->assertArrayHasKey('error_classification', $results);
        $this->assertArrayHasKey('error_reporting', $results);
        $this->assertArrayHasKey('error_trends', $results);
        $this->assertArrayHasKey('error_resolution', $results);
    }

    public function test_cannot_track_errors_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error tracking not allowed in current environment');

        $this->agent->trackErrors('test/path');
    }

    public function test_can_manage_alerts(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['manage_alerts', ['path' => 'test/path']],
                ['alert_management_complete', $this->anything()]
            );

        $results = $this->agent->manageAlerts('test/path');

        $this->assertArrayHasKey('alert_generation', $results);
        $this->assertArrayHasKey('alert_classification', $results);
        $this->assertArrayHasKey('alert_routing', $results);
        $this->assertArrayHasKey('alert_escalation', $results);
        $this->assertArrayHasKey('alert_resolution', $results);
    }

    public function test_cannot_manage_alerts_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Alert management not allowed in current environment');

        $this->agent->manageAlerts('test/path');
    }

    public function test_can_check_health(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['check_health', ['path' => 'test/path']],
                ['health_check_complete', $this->anything()]
            );

        $results = $this->agent->checkHealth('test/path');

        $this->assertArrayHasKey('service_health', $results);
        $this->assertArrayHasKey('dependency_health', $results);
        $this->assertArrayHasKey('system_health', $results);
        $this->assertArrayHasKey('health_status', $results);
        $this->assertArrayHasKey('health_trends', $results);
    }

    public function test_cannot_check_health_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Health checks not allowed in current environment');

        $this->agent->checkHealth('test/path');
    }

    private function setupEnvironment(string $environment): void
    {
        $this->accessControl->method('getEnvironment')
            ->willReturn($environment);
    }
} 