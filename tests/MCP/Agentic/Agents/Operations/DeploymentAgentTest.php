<?php

namespace Tests\MCP\Agentic\Agents\Operations;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\Operations\DeploymentAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;

class DeploymentAgentTest extends BaseAgenticTestCase
{
    private DeploymentAgent $agent;
    private TaskManager $taskManager;
    private AuditLogger $auditLogger;
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = $this->createMock(TaskManager::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);

        $this->agent = new DeploymentAgent(
            $this->taskManager,
            $this->auditLogger,
            $this->accessControl
        );
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('deployment', $this->agent->getType());
        $this->assertEquals([
            'deployment_validation',
            'rollback_system',
            'environment_management',
            'deployment_reporting',
            'change_tracking',
        ], $this->agent->getCapabilities());
    }

    public function test_can_validate_deployment(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['validate_deployment', ['path' => 'test/path']],
                ['deployment_validation_complete', $this->anything()]
            );

        $results = $this->agent->validateDeployment('test/path');

        $this->assertArrayHasKey('configuration_validation', $results);
        $this->assertArrayHasKey('dependency_verification', $results);
        $this->assertArrayHasKey('environment_compatibility', $results);
        $this->assertArrayHasKey('security_validation', $results);
        $this->assertArrayHasKey('performance_impact', $results);
    }

    public function test_cannot_validate_deployment_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Deployment validation not allowed in current environment');

        $this->agent->validateDeployment('test/path');
    }

    public function test_can_manage_rollback(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['manage_rollback', ['path' => 'test/path']],
                ['rollback_management_complete', $this->anything()]
            );

        $results = $this->agent->manageRollback('test/path');

        $this->assertArrayHasKey('state_tracking', $results);
        $this->assertArrayHasKey('rollback_point', $results);
        $this->assertArrayHasKey('state_restoration', $results);
        $this->assertArrayHasKey('data_consistency', $results);
        $this->assertArrayHasKey('rollback_report', $results);
    }

    public function test_cannot_manage_rollback_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rollback management not allowed in current environment');

        $this->agent->manageRollback('test/path');
    }

    public function test_can_manage_environment(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['manage_environment', ['path' => 'test/path']],
                ['environment_management_complete', $this->anything()]
            );

        $results = $this->agent->manageEnvironment('test/path');

        $this->assertArrayHasKey('environment_config', $results);
        $this->assertArrayHasKey('resource_allocation', $results);
        $this->assertArrayHasKey('service_orchestration', $results);
        $this->assertArrayHasKey('environment_sync', $results);
        $this->assertArrayHasKey('config_management', $results);
    }

    public function test_cannot_manage_environment_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment management not allowed in current environment');

        $this->agent->manageEnvironment('test/path');
    }

    public function test_can_generate_deployment_report(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['generate_deployment_report', ['path' => 'test/path']],
                ['deployment_report_complete', $this->anything()]
            );

        $results = $this->agent->generateDeploymentReport('test/path');

        $this->assertArrayHasKey('deployment_status', $results);
        $this->assertArrayHasKey('change_documentation', $results);
        $this->assertArrayHasKey('performance_impact', $results);
        $this->assertArrayHasKey('error_report', $results);
        $this->assertArrayHasKey('success_metrics', $results);
    }

    public function test_cannot_generate_deployment_report_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Deployment reporting not allowed in current environment');

        $this->agent->generateDeploymentReport('test/path');
    }

    public function test_can_track_changes(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['track_changes', ['path' => 'test/path']],
                ['change_tracking_complete', $this->anything()]
            );

        $results = $this->agent->trackChanges('test/path');

        $this->assertArrayHasKey('change_identification', $results);
        $this->assertArrayHasKey('impact_analysis', $results);
        $this->assertArrayHasKey('dependency_tracking', $results);
        $this->assertArrayHasKey('change_documentation', $results);
        $this->assertArrayHasKey('change_verification', $results);
    }

    public function test_cannot_track_changes_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Change tracking not allowed in current environment');

        $this->agent->trackChanges('test/path');
    }

    private function setupEnvironment(string $environment): void
    {
        $this->accessControl->method('getEnvironment')
            ->willReturn($environment);
    }
} 