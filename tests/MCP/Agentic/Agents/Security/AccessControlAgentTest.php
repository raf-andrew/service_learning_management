<?php

namespace Tests\MCP\Agentic\Agents\Security;

use PHPUnit\Framework\TestCase;
use MCP\Agentic\Agents\Security\AccessControlAgent;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;
use MCP\Agentic\Core\Services\Alerting;

class AccessControlAgentTest extends TestCase
{
    protected AccessControlAgent $agent;
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

        $this->agent = new AccessControlAgent(
            $this->accessControl,
            $this->logging,
            $this->monitoring,
            $this->reporting,
            $this->alerting
        );
    }

    public function testManagePermissions(): void
    {
        $permissions = [
            [
                'action' => 'create',
                'permission' => 'user.create',
                'roles' => ['admin'],
                'users' => ['user1'],
                'resources' => ['users'],
                'conditions' => ['time' => '9-5'],
            ],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('access.manage')
            ->willReturn(true);

        $this->accessControl->expects($this->once())
            ->method('managePermission')
            ->with($this->callback(function ($permission) {
                return $permission['action'] === 'create' &&
                    $permission['permission'] === 'user.create' &&
                    $permission['roles'] === ['admin'] &&
                    $permission['users'] === ['user1'] &&
                    $permission['resources'] === ['users'] &&
                    $permission['conditions'] === ['time' => '9-5'];
            }))
            ->willReturn(['success' => true]);

        $this->logging->expects($this->once())
            ->method('info')
            ->with('Permission managed', $this->anything());

        $result = $this->agent->managePermissions($permissions);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['success']);
    }

    public function testValidateAccess(): void
    {
        $request = [
            'user' => 'user1',
            'permission' => 'user.create',
            'resource' => 'users',
            'context' => ['time' => '10:00'],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['access.validate'],
                ['access.validate']
            )
            ->willReturn(true);

        $this->accessControl->expects($this->once())
            ->method('validateAccess')
            ->with($this->callback(function ($request) {
                return $request['user'] === 'user1' &&
                    $request['permission'] === 'user.create' &&
                    $request['resource'] === 'users' &&
                    $request['context'] === ['time' => '10:00'];
            }))
            ->willReturn(['allowed' => true]);

        $this->monitoring->expects($this->once())
            ->method('trackAccessPattern')
            ->with($this->callback(function ($pattern) {
                return $pattern['user'] === 'user1' &&
                    $pattern['permission'] === 'user.create' &&
                    $pattern['resource'] === 'users' &&
                    $pattern['result'] === true;
            }));

        $this->monitoring->expects($this->once())
            ->method('detectSuspiciousPattern')
            ->willReturn(false);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating access request', $this->anything()],
                ['Access request validated', $this->anything()]
            );

        $result = $this->agent->validateAccess($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['allowed']);
    }

    public function testValidateAccessWithViolation(): void
    {
        $request = [
            'user' => 'user1',
            'permission' => 'user.create',
            'resource' => 'users',
            'context' => ['time' => '10:00'],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['access.validate'],
                ['access.validate']
            )
            ->willReturn(true);

        $this->accessControl->expects($this->once())
            ->method('validateAccess')
            ->willReturn([
                'allowed' => false,
                'reason' => 'Insufficient permissions',
            ]);

        $this->monitoring->expects($this->once())
            ->method('trackAccessPattern')
            ->with($this->callback(function ($pattern) {
                return $pattern['user'] === 'user1' &&
                    $pattern['permission'] === 'user.create' &&
                    $pattern['resource'] === 'users' &&
                    $pattern['result'] === false;
            }));

        $this->monitoring->expects($this->once())
            ->method('detectSuspiciousPattern')
            ->willReturn(false);

        $this->monitoring->expects($this->once())
            ->method('trackViolation')
            ->with($this->callback(function ($violation) {
                return $violation['user'] === 'user1' &&
                    $violation['permission'] === 'user.create' &&
                    $violation['resource'] === 'users' &&
                    $violation['reason'] === 'Insufficient permissions';
            }));

        $this->alerting->expects($this->once())
            ->method('alert')
            ->with('Access violation detected', $this->anything());

        $this->reporting->expects($this->once())
            ->method('generateViolationReport')
            ->with($this->anything());

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating access request', $this->anything()],
                ['Access request validated', $this->anything()]
            );

        $result = $this->agent->validateAccess($request);

        $this->assertIsArray($result);
        $this->assertFalse($result['allowed']);
        $this->assertEquals('Insufficient permissions', $result['reason']);
    }

    public function testValidateAccessWithSuspiciousPattern(): void
    {
        $request = [
            'user' => 'user1',
            'permission' => 'user.create',
            'resource' => 'users',
            'context' => ['time' => '10:00'],
        ];

        $this->accessControl->expects($this->exactly(2))
            ->method('hasPermission')
            ->withConsecutive(
                ['access.validate'],
                ['access.validate']
            )
            ->willReturn(true);

        $this->accessControl->expects($this->once())
            ->method('validateAccess')
            ->willReturn(['allowed' => true]);

        $this->monitoring->expects($this->once())
            ->method('trackAccessPattern')
            ->with($this->anything());

        $this->monitoring->expects($this->once())
            ->method('detectSuspiciousPattern')
            ->willReturn([
                'suspicious' => true,
                'reason' => 'Multiple failed attempts',
            ]);

        $this->alerting->expects($this->once())
            ->method('alert')
            ->with('Suspicious access pattern detected', $this->anything());

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating access request', $this->anything()],
                ['Access request validated', $this->anything()]
            );

        $result = $this->agent->validateAccess($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['allowed']);
    }

    public function testGenerateAccessReport(): void
    {
        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'user' => 'user1',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('access.report')
            ->willReturn(true);

        $this->reporting->expects($this->once())
            ->method('generateAccessReport')
            ->with($this->callback(function ($options) {
                return $options['filters'] === [
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-12-31',
                    'user' => 'user1',
                ] &&
                $options['include_patterns'] === true &&
                $options['include_violations'] === true &&
                $options['include_metrics'] === true;
            }))
            ->willReturn(['report' => 'Access Report']);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Generating access report', $this->anything()],
                ['Access report generated', $this->anything()]
            );

        $result = $this->agent->generateAccessReport($filters);

        $this->assertIsArray($result);
        $this->assertEquals(['report' => 'Access Report'], $result);
    }

    public function testValidateAccessWithAccessDenied(): void
    {
        $request = [
            'user' => 'user1',
            'permission' => 'user.create',
            'resource' => 'users',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('access.validate')
            ->willReturn(false);

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Access validation failed', $this->anything());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: access.validate');

        $this->agent->validateAccess($request);
    }

    public function testGenerateAccessReportWithAccessDenied(): void
    {
        $filters = [];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('access.report')
            ->willReturn(false);

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Access report generation failed', $this->anything());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: access.report');

        $this->agent->generateAccessReport($filters);
    }
} 