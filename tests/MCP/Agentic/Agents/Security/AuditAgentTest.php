<?php

namespace Tests\MCP\Agentic\Agents\Security;

use PHPUnit\Framework\TestCase;
use MCP\Agentic\Agents\Security\AuditAgent;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;
use MCP\Agentic\Core\Services\Alerting;

class AuditAgentTest extends TestCase
{
    protected AuditAgent $auditAgent;
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Reporting $reporting;
    protected Alerting $alerting;

    protected function setUp(): void
    {
        $this->accessControl = $this->createMock(AccessControl::class);
        $this->logging = $this->createMock(Logging::class);
        $this->monitoring = $this->createMock(Monitoring::class);
        $this->reporting = $this->createMock(Reporting::class);
        $this->alerting = $this->createMock(Alerting::class);

        $this->auditAgent = new AuditAgent(
            $this->accessControl,
            $this->logging,
            $this->monitoring,
            $this->reporting,
            $this->alerting
        );
    }

    public function testMonitorActivity(): void
    {
        $filters = ['type' => 'user_action'];
        $activities = [
            ['id' => 1, 'type' => 'user_action', 'user' => 'test_user'],
            ['id' => 2, 'type' => 'user_action', 'user' => 'test_user'],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.monitor')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info');

        $this->monitoring->expects($this->once())
            ->method('trackActivities')
            ->with([
                'filters' => $filters,
                'include_metadata' => true,
                'include_context' => true,
            ])
            ->willReturn($activities);

        $this->monitoring->expects($this->exactly(2))
            ->method('detectUnusualPattern')
            ->willReturn(false);

        $this->monitoring->expects($this->exactly(2))
            ->method('detectSecurityViolation')
            ->willReturn(false);

        $this->monitoring->expects($this->exactly(2))
            ->method('detectComplianceViolation')
            ->willReturn(false);

        $result = $this->auditAgent->monitorActivity($filters);
        $this->assertEquals($activities, $result);
    }

    public function testMonitorActivityWithSuspiciousActivity(): void
    {
        $filters = ['type' => 'user_action'];
        $activities = [
            ['id' => 1, 'type' => 'user_action', 'user' => 'test_user'],
            ['id' => 2, 'type' => 'user_action', 'user' => 'test_user'],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.monitor')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info');

        $this->monitoring->expects($this->once())
            ->method('trackActivities')
            ->willReturn($activities);

        $this->monitoring->expects($this->exactly(2))
            ->method('detectUnusualPattern')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->alerting->expects($this->once())
            ->method('alert')
            ->with('Suspicious activity detected', [
                'activities' => [$activities[1]],
            ]);

        $result = $this->auditAgent->monitorActivity($filters);
        $this->assertEquals($activities, $result);
    }

    public function testValidateCompliance(): void
    {
        $requirements = [
            ['id' => 'req1', 'type' => 'security'],
            ['id' => 'req2', 'type' => 'privacy'],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.compliance')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info');

        $this->monitoring->expects($this->exactly(2))
            ->method('validateCompliance')
            ->willReturnOnConsecutiveCalls(
                ['compliant' => true],
                ['compliant' => false, 'violation' => 'Privacy policy not met']
            );

        $this->alerting->expects($this->once())
            ->method('alert')
            ->with('Compliance violation detected', [
                'requirement' => $requirements[1],
                'violation' => 'Privacy policy not met',
            ]);

        $result = $this->auditAgent->validateCompliance($requirements);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0]['compliant']);
        $this->assertFalse($result[1]['compliant']);
    }

    public function testGenerateAuditReport(): void
    {
        $filters = ['period' => 'last_week'];
        $report = [
            'activities' => [],
            'compliance' => [],
            'alerts' => [],
            'metrics' => [],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.report')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info');

        $this->reporting->expects($this->once())
            ->method('generateAuditReport')
            ->with([
                'filters' => $filters,
                'include_activities' => true,
                'include_compliance' => true,
                'include_alerts' => true,
                'include_metrics' => true,
            ])
            ->willReturn($report);

        $result = $this->auditAgent->generateAuditReport($filters);
        $this->assertEquals($report, $result);
    }

    public function testInvestigateActivity(): void
    {
        $criteria = ['user' => 'test_user'];
        $investigation = [
            'timeline' => [],
            'evidence' => [],
            'analysis' => [],
        ];
        $report = ['summary' => 'Investigation complete'];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.investigate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info');

        $this->monitoring->expects($this->once())
            ->method('investigateActivity')
            ->with([
                'criteria' => $criteria,
                'include_timeline' => true,
                'include_evidence' => true,
                'include_analysis' => true,
            ])
            ->willReturn($investigation);

        $this->reporting->expects($this->once())
            ->method('generateInvestigationReport')
            ->with($investigation)
            ->willReturn($report);

        $result = $this->auditAgent->investigateActivity($criteria);
        $this->assertEquals([
            'investigation' => $investigation,
            'report' => $report,
        ], $result);
    }

    public function testMonitorActivityWithAccessDenied(): void
    {
        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.monitor')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: audit.monitor');

        $this->auditAgent->monitorActivity();
    }

    public function testValidateComplianceWithAccessDenied(): void
    {
        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.compliance')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: audit.compliance');

        $this->auditAgent->validateCompliance([]);
    }

    public function testGenerateAuditReportWithAccessDenied(): void
    {
        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.report')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: audit.report');

        $this->auditAgent->generateAuditReport();
    }

    public function testInvestigateActivityWithAccessDenied(): void
    {
        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.investigate')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: audit.investigate');

        $this->auditAgent->investigateActivity([]);
    }

    public function testMonitorActivityWithError(): void
    {
        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('audit.monitor')
            ->willReturn(true);

        $this->logging->expects($this->once())
            ->method('info');

        $this->monitoring->expects($this->once())
            ->method('trackActivities')
            ->willThrowException(new \Exception('Monitoring error'));

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Activity monitoring failed', [
                'filters' => [],
                'error' => 'Monitoring error',
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Monitoring error');

        $this->auditAgent->monitorActivity();
    }
} 