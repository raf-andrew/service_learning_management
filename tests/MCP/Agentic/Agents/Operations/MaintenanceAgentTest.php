<?php

namespace Tests\MCP\Agentic\Agents\Operations;

use PHPUnit\Framework\TestCase;
use MCP\Agentic\Agents\Operations\MaintenanceAgent;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Scheduling;
use MCP\Agentic\Core\Services\Reporting;
use MCP\Agentic\Core\Services\IssueTracking;

class MaintenanceAgentTest extends TestCase
{
    protected MaintenanceAgent $agent;
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Scheduling $scheduling;
    protected Reporting $reporting;
    protected IssueTracking $issueTracking;

    protected function setUp(): void
    {
        $this->accessControl = $this->createMock(AccessControl::class);
        $this->logging = $this->createMock(Logging::class);
        $this->monitoring = $this->createMock(Monitoring::class);
        $this->scheduling = $this->createMock(Scheduling::class);
        $this->reporting = $this->createMock(Reporting::class);
        $this->issueTracking = $this->createMock(IssueTracking::class);

        $this->agent = new MaintenanceAgent(
            $this->accessControl,
            $this->logging,
            $this->monitoring,
            $this->scheduling,
            $this->reporting,
            $this->issueTracking
        );
    }

    public function testScheduleMaintenance(): void
    {
        $tasks = [
            [
                'name' => 'Database Backup',
                'priority' => 'high',
                'schedule' => 'daily',
                'steps' => [
                    [
                        'type' => 'database',
                        'action' => 'backup',
                    ],
                ],
            ],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('maintenance.schedule')
            ->willReturn(true);

        $this->scheduling->expects($this->once())
            ->method('scheduleTask')
            ->with($this->callback(function ($task) {
                return $task['type'] === 'maintenance' &&
                    $task['priority'] === 'high' &&
                    $task['schedule'] === 'daily';
            }))
            ->willReturn(['id' => 1, 'status' => 'scheduled']);

        $this->logging->expects($this->once())
            ->method('info')
            ->with('Maintenance task scheduled', $this->anything());

        $result = $this->agent->scheduleMaintenance($tasks);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1, 'status' => 'scheduled'], $result[0]);
    }

    public function testExecuteTask(): void
    {
        $task = [
            'name' => 'System Cleanup',
            'steps' => [
                [
                    'type' => 'cache',
                    'action' => 'clear',
                ],
            ],
        ];

        $preHealth = [
            'cpu' => ['status' => 'healthy', 'value' => 50],
            'memory' => ['status' => 'healthy', 'value' => 60],
            'disk' => ['status' => 'healthy', 'value' => 70],
            'network' => ['status' => 'healthy', 'value' => 80],
            'services' => ['status' => 'healthy', 'value' => 0],
        ];

        $postHealth = [
            'cpu' => ['status' => 'healthy', 'value' => 45],
            'memory' => ['status' => 'healthy', 'value' => 55],
            'disk' => ['status' => 'healthy', 'value' => 65],
            'network' => ['status' => 'healthy', 'value' => 75],
            'services' => ['status' => 'healthy', 'value' => 0],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['maintenance.execute'],
                ['maintenance.execute.step']
            )
            ->willReturn(true);

        $this->monitoring->expects($this->exactly(2))
            ->method('getSystemHealth')
            ->willReturnOnConsecutiveCalls($preHealth, $postHealth);

        $this->logging->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Starting maintenance task execution', $this->anything()],
                ['Executing maintenance step', $this->anything()],
                ['Maintenance task completed', $this->anything()]
            );

        $this->reporting->expects($this->once())
            ->method('generateMaintenanceReport')
            ->with($this->anything())
            ->willReturn(['report' => 'Maintenance Report']);

        $result = $this->agent->executeTask($task);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('healthValidation', $result);
        $this->assertArrayHasKey('report', $result);
    }

    public function testExecuteTaskWithHealthIssues(): void
    {
        $task = [
            'name' => 'System Update',
            'steps' => [
                [
                    'type' => 'service',
                    'action' => 'update',
                ],
            ],
        ];

        $preHealth = [
            'cpu' => ['status' => 'healthy', 'value' => 50],
            'memory' => ['status' => 'healthy', 'value' => 60],
            'disk' => ['status' => 'healthy', 'value' => 70],
            'network' => ['status' => 'healthy', 'value' => 80],
            'services' => ['status' => 'healthy', 'value' => 0],
        ];

        $postHealth = [
            'cpu' => ['status' => 'warning', 'value' => 85],
            'memory' => ['status' => 'healthy', 'value' => 55],
            'disk' => ['status' => 'healthy', 'value' => 65],
            'network' => ['status' => 'healthy', 'value' => 75],
            'services' => ['status' => 'critical', 'value' => 2],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['maintenance.execute'],
                ['maintenance.execute.step']
            )
            ->willReturn(true);

        $this->monitoring->expects($this->exactly(2))
            ->method('getSystemHealth')
            ->willReturnOnConsecutiveCalls($preHealth, $postHealth);

        $this->logging->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Starting maintenance task execution', $this->anything()],
                ['Executing maintenance step', $this->anything()],
                ['Maintenance task completed', $this->anything()]
            );

        $this->reporting->expects($this->once())
            ->method('generateMaintenanceReport')
            ->with($this->anything())
            ->willReturn(['report' => 'Maintenance Report']);

        $this->issueTracking->expects($this->exactly(2))
            ->method('trackIssue')
            ->withConsecutive(
                [
                    $this->callback(function ($issue) {
                        return $issue['type'] === 'maintenance' &&
                            $issue['issue']['metric'] === 'cpu' &&
                            $issue['severity'] === 'warning';
                    }),
                ],
                [
                    $this->callback(function ($issue) {
                        return $issue['type'] === 'maintenance' &&
                            $issue['issue']['metric'] === 'services' &&
                            $issue['severity'] === 'critical';
                    }),
                ]
            );

        $result = $this->agent->executeTask($task);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('healthValidation', $result);
        $this->assertArrayHasKey('report', $result);
        $this->assertFalse($result['healthValidation']['healthy']);
        $this->assertCount(2, $result['healthValidation']['issues']);
    }

    public function testExecuteTaskWithAccessDenied(): void
    {
        $task = [
            'name' => 'System Cleanup',
            'steps' => [
                [
                    'type' => 'cache',
                    'action' => 'clear',
                ],
            ],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('maintenance.execute')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: maintenance.execute');

        $this->agent->executeTask($task);
    }

    public function testExecuteTaskWithInvalidStepType(): void
    {
        $task = [
            'name' => 'System Cleanup',
            'steps' => [
                [
                    'type' => 'invalid',
                    'action' => 'clear',
                ],
            ],
        ];

        $preHealth = [
            'cpu' => ['status' => 'healthy', 'value' => 50],
            'memory' => ['status' => 'healthy', 'value' => 60],
            'disk' => ['status' => 'healthy', 'value' => 70],
            'network' => ['status' => 'healthy', 'value' => 80],
            'services' => ['status' => 'healthy', 'value' => 0],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['maintenance.execute'],
                ['maintenance.execute.step']
            )
            ->willReturn(true);

        $this->monitoring->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($preHealth);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting maintenance task execution', $this->anything()],
                ['Executing maintenance step', $this->anything()]
            );

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Maintenance task failed', $this->anything());

        $this->issueTracking->expects($this->once())
            ->method('trackIssue')
            ->with($this->callback(function ($issue) {
                return $issue['type'] === 'maintenance' &&
                    $issue['severity'] === 'high' &&
                    $issue['status'] === 'open';
            }));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown maintenance step type: invalid');

        $this->agent->executeTask($task);
    }

    public function testExecuteTaskWithStepFailure(): void
    {
        $task = [
            'name' => 'Database Maintenance',
            'steps' => [
                [
                    'type' => 'database',
                    'action' => 'optimize',
                ],
            ],
        ];

        $preHealth = [
            'cpu' => ['status' => 'healthy', 'value' => 50],
            'memory' => ['status' => 'healthy', 'value' => 60],
            'disk' => ['status' => 'healthy', 'value' => 70],
            'network' => ['status' => 'healthy', 'value' => 80],
            'services' => ['status' => 'healthy', 'value' => 0],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['maintenance.execute'],
                ['maintenance.execute.step']
            )
            ->willReturn(true);

        $this->monitoring->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($preHealth);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting maintenance task execution', $this->anything()],
                ['Executing maintenance step', $this->anything()]
            );

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Maintenance task failed', $this->anything());

        $this->issueTracking->expects($this->once())
            ->method('trackIssue')
            ->with($this->callback(function ($issue) {
                return $issue['type'] === 'maintenance' &&
                    $issue['severity'] === 'high' &&
                    $issue['status'] === 'open';
            }));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database optimization failed');

        $this->agent->executeTask($task);
    }
} 