<?php

namespace Tests\MCP\Agentic\Core\Services;

use PHPUnit\Framework\TestCase;
use MCP\Agentic\Core\Services\TenantService;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;
use MCP\Agentic\Core\Services\Billing;

class TenantServiceTest extends TestCase
{
    protected TenantService $tenantService;
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Reporting $reporting;
    protected Billing $billing;

    protected function setUp(): void
    {
        $this->accessControl = $this->createMock(AccessControl::class);
        $this->logging = $this->createMock(Logging::class);
        $this->monitoring = $this->createMock(Monitoring::class);
        $this->reporting = $this->createMock(Reporting::class);
        $this->billing = $this->createMock(Billing::class);

        $this->tenantService = new TenantService(
            $this->accessControl,
            $this->logging,
            $this->monitoring,
            $this->reporting,
            $this->billing
        );
    }

    public function testCreateTenant(): void
    {
        $tenantData = [
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'plan' => 'basic',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.create')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Creating tenant', ['data' => $tenantData]],
                ['Tenant created', ['tenant' => $tenantData]]
            );

        $result = $this->tenantService->createTenant($tenantData);
        $this->assertEquals($tenantData, $result);
    }

    public function testCreateTenantWithAccessDenied(): void
    {
        $tenantData = [
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'plan' => 'basic',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.create')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: tenant.create');

        $this->tenantService->createTenant($tenantData);
    }

    public function testGetTenant(): void
    {
        $tenantId = 'test-tenant-123';
        $tenantData = [
            'id' => $tenantId,
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'plan' => 'basic',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.read')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Getting tenant details', ['tenant_id' => $tenantId]],
                ['Tenant details retrieved', ['tenant_id' => $tenantId]]
            );

        $result = $this->tenantService->getTenant($tenantId);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('tenant', $result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('usage', $result);
        $this->assertArrayHasKey('billing', $result);
    }

    public function testUpdateTenant(): void
    {
        $tenantId = 'test-tenant-123';
        $updateData = [
            'name' => 'Updated Tenant',
            'plan' => 'premium',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.update')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating tenant', ['tenant_id' => $tenantId, 'data' => $updateData]],
                ['Tenant updated', ['tenant_id' => $tenantId]]
            );

        $result = $this->tenantService->updateTenant($tenantId, $updateData);
        $this->assertIsArray($result);
    }

    public function testDeleteTenant(): void
    {
        $tenantId = 'test-tenant-123';

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.delete')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Deleting tenant', ['tenant_id' => $tenantId]],
                ['Tenant deleted', ['tenant_id' => $tenantId]]
            );

        $result = $this->tenantService->deleteTenant($tenantId);
        $this->assertTrue($result);
    }

    public function testTrackUsage(): void
    {
        $tenantId = 'test-tenant-123';
        $usageData = [
            'resource' => 'api_calls',
            'amount' => 100,
            'timestamp' => time(),
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.usage.track')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Tracking tenant usage', ['tenant_id' => $tenantId, 'usage' => $usageData]],
                ['Tenant usage tracked', ['tenant_id' => $tenantId]]
            );

        $result = $this->tenantService->trackUsage($tenantId, $usageData);
        $this->assertIsArray($result);
    }

    public function testGenerateReport(): void
    {
        $tenantId = 'test-tenant-123';
        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.report')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Generating tenant report', ['tenant_id' => $tenantId, 'filters' => $filters]],
                ['Tenant report generated', ['tenant_id' => $tenantId]]
            );

        $this->reporting->expects($this->once())
            ->method('generateTenantReport')
            ->willReturn(['report' => 'data']);

        $result = $this->tenantService->generateReport($tenantId, $filters);
        $this->assertIsArray($result);
        $this->assertEquals(['report' => 'data'], $result);
    }

    public function testGenerateReportWithError(): void
    {
        $tenantId = 'test-tenant-123';
        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.report')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Generating tenant report', ['tenant_id' => $tenantId, 'filters' => $filters]]
            );

        $this->logging->expects($this->once())
            ->method('error')
            ->with(
                'Report generation failed',
                [
                    'tenant_id' => $tenantId,
                    'filters' => $filters,
                    'error' => 'Report generation failed',
                ]
            );

        $this->reporting->expects($this->once())
            ->method('generateTenantReport')
            ->willThrowException(new \Exception('Report generation failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Report generation failed');

        $this->tenantService->generateReport($tenantId, $filters);
    }

    public function testTrackUsageWithResourceLimitExceeded(): void
    {
        $tenantId = 'test-tenant-123';
        $usageData = [
            'resource' => 'api_calls',
            'amount' => 1000000, // Exceeds limit
            'timestamp' => time(),
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('tenant.usage.track')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Tracking tenant usage', ['tenant_id' => $tenantId, 'usage' => $usageData]],
                ['Tenant usage tracked', ['tenant_id' => $tenantId]]
            );

        $this->monitoring->expects($this->once())
            ->method('alert')
            ->with(
                'Resource limit exceeded',
                [
                    'tenant_id' => $tenantId,
                    'resource' => 'api_calls',
                    'amount' => 1000000,
                ]
            );

        $result = $this->tenantService->trackUsage($tenantId, $usageData);
        $this->assertIsArray($result);
    }
} 