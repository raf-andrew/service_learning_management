<?php

namespace Tests\MCP\Agentic\Agents\QA;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\QA\SecurityTestingAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;

class SecurityTestingAgentTest extends BaseAgenticTestCase
{
    private SecurityTestingAgent $agent;
    private TaskManager $taskManager;
    private AuditLogger $auditLogger;
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = $this->createMock(TaskManager::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);

        $this->agent = new SecurityTestingAgent(
            $this->taskManager,
            $this->auditLogger,
            $this->accessControl
        );
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('security_testing', $this->agent->getType());
        $this->assertEquals([
            'vulnerability_scanning',
            'security_validation',
            'compliance_checking',
            'security_reporting',
            'remediation_tracking',
        ], $this->agent->getCapabilities());
    }

    public function test_can_scan_vulnerabilities(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['scan_vulnerabilities', ['path' => 'test/path']],
                ['vulnerability_scanning_complete', $this->anything()]
            );

        $results = $this->agent->scanVulnerabilities('test/path');

        $this->assertArrayHasKey('code_vulnerabilities', $results);
        $this->assertArrayHasKey('dependency_vulnerabilities', $results);
        $this->assertArrayHasKey('configuration_vulnerabilities', $results);
        $this->assertArrayHasKey('api_vulnerabilities', $results);
        $this->assertArrayHasKey('database_vulnerabilities', $results);
    }

    public function test_cannot_scan_vulnerabilities_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Vulnerability scanning not allowed in current environment');

        $this->agent->scanVulnerabilities('test/path');
    }

    public function test_can_validate_security(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['validate_security', ['path' => 'test/path']],
                ['security_validation_complete', $this->anything()]
            );

        $results = $this->agent->validateSecurity('test/path');

        $this->assertArrayHasKey('authentication', $results);
        $this->assertArrayHasKey('authorization', $results);
        $this->assertArrayHasKey('input_validation', $results);
        $this->assertArrayHasKey('output_sanitization', $results);
        $this->assertArrayHasKey('session_management', $results);
    }

    public function test_cannot_validate_security_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Security validation not allowed in current environment');

        $this->agent->validateSecurity('test/path');
    }

    public function test_can_check_compliance(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['check_compliance', ['path' => 'test/path']],
                ['compliance_checking_complete', $this->anything()]
            );

        $results = $this->agent->checkCompliance('test/path');

        $this->assertArrayHasKey('security_standards', $results);
        $this->assertArrayHasKey('data_protection', $results);
        $this->assertArrayHasKey('access_control', $results);
        $this->assertArrayHasKey('audit_trail', $results);
        $this->assertArrayHasKey('policy_enforcement', $results);
    }

    public function test_cannot_check_compliance_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Compliance checking not allowed in current environment');

        $this->agent->checkCompliance('test/path');
    }

    public function test_can_generate_security_report(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['generate_security_report', ['path' => 'test/path']],
                ['security_reporting_complete', $this->anything()]
            );

        $results = $this->agent->generateSecurityReport('test/path');

        $this->assertArrayHasKey('vulnerability_report', $results);
        $this->assertArrayHasKey('compliance_report', $results);
        $this->assertArrayHasKey('risk_assessment', $results);
        $this->assertArrayHasKey('security_metrics', $results);
        $this->assertArrayHasKey('trend_analysis', $results);
    }

    public function test_cannot_generate_security_report_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Security reporting not allowed in current environment');

        $this->agent->generateSecurityReport('test/path');
    }

    public function test_can_track_remediation(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['track_remediation', ['path' => 'test/path']],
                ['remediation_tracking_complete', $this->anything()]
            );

        $results = $this->agent->trackRemediation('test/path');

        $this->assertArrayHasKey('issue_tracking', $results);
        $this->assertArrayHasKey('fix_verification', $results);
        $this->assertArrayHasKey('patch_management', $results);
        $this->assertArrayHasKey('security_updates', $results);
        $this->assertArrayHasKey('resolution_monitoring', $results);
    }

    public function test_cannot_track_remediation_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Remediation tracking not allowed in current environment');

        $this->agent->trackRemediation('test/path');
    }

    private function setupEnvironment(string $environment): void
    {
        $this->accessControl->method('getEnvironment')
            ->willReturn($environment);
    }
} 