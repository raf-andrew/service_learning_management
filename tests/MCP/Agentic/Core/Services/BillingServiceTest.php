<?php

namespace Tests\MCP\Agentic\Core\Services;

use PHPUnit\Framework\TestCase;
use MCP\Agentic\Core\Services\BillingService;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;

class BillingServiceTest extends TestCase
{
    protected BillingService $billingService;
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Reporting $reporting;

    protected function setUp(): void
    {
        $this->accessControl = $this->createMock(AccessControl::class);
        $this->logging = $this->createMock(Logging::class);
        $this->monitoring = $this->createMock(Monitoring::class);
        $this->reporting = $this->createMock(Reporting::class);

        $this->billingService = new BillingService(
            $this->accessControl,
            $this->logging,
            $this->monitoring,
            $this->reporting
        );
    }

    public function testTrackUsage(): void
    {
        $tenantId = 'test-tenant-1';
        $usageData = [
            'resource' => 'api_calls',
            'quantity' => 100,
            'timestamp' => time(),
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.usage.track')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Tracking tenant usage', ['tenant_id' => $tenantId, 'usage_data' => $usageData]],
                ['Usage tracked successfully', ['tenant_id' => $tenantId, 'tracking_id' => 'tracking-1']]
            );

        $result = $this->billingService->trackUsage($tenantId, $usageData);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testTrackUsageWithAccessDenied(): void
    {
        $tenantId = 'test-tenant-1';
        $usageData = [
            'resource' => 'api_calls',
            'quantity' => 100,
            'timestamp' => time(),
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.usage.track')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: billing.usage.track');

        $this->billingService->trackUsage($tenantId, $usageData);
    }

    public function testGenerateBilling(): void
    {
        $tenantId = 'test-tenant-1';
        $options = [
            'period' => '2024-01',
            'currency' => 'USD',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.statement.generate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Generating billing statement', ['tenant_id' => $tenantId, 'options' => $options]],
                ['Billing statement generated', ['tenant_id' => $tenantId, 'statement_id' => 'statement-1']]
            );

        $result = $this->billingService->generateBilling($tenantId, $options);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testProcessPayment(): void
    {
        $tenantId = 'test-tenant-1';
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'method' => 'credit_card',
            'card_number' => '4111111111111111',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.payment.process')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Processing payment', ['tenant_id' => $tenantId, 'payment_data' => $paymentData]],
                ['Payment processed successfully', ['tenant_id' => $tenantId, 'payment_id' => 'payment-1']]
            );

        $result = $this->billingService->processPayment($tenantId, $paymentData);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testHandleDispute(): void
    {
        $tenantId = 'test-tenant-1';
        $disputeData = [
            'statement_id' => 'statement-1',
            'reason' => 'Incorrect charges',
            'details' => 'Charged for unused services',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.dispute.handle')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Handling billing dispute', ['tenant_id' => $tenantId, 'dispute_data' => $disputeData]],
                ['Dispute handled successfully', [
                    'tenant_id' => $tenantId,
                    'dispute_id' => 'dispute-1',
                    'resolution' => 'resolved',
                ]]
            );

        $result = $this->billingService->handleDispute($tenantId, $disputeData);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testGenerateReport(): void
    {
        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'tenant_id' => 'test-tenant-1',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.report.generate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Generating billing report', ['filters' => $filters]],
                ['Billing report generated', ['report_id' => 'report-1']]
            );

        $result = $this->billingService->generateReport($filters);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testTrackUsageWithError(): void
    {
        $tenantId = 'test-tenant-1';
        $usageData = [
            'resource' => 'api_calls',
            'quantity' => -100, // Invalid quantity
            'timestamp' => time(),
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.usage.track')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->with('Tracking tenant usage', ['tenant_id' => $tenantId, 'usage_data' => $usageData]);

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Usage tracking failed', [
                'tenant_id' => $tenantId,
                'usage_data' => $usageData,
                'error' => 'Invalid quantity: must be positive',
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid quantity: must be positive');

        $this->billingService->trackUsage($tenantId, $usageData);
    }

    public function testProcessPaymentWithError(): void
    {
        $tenantId = 'test-tenant-1';
        $paymentData = [
            'amount' => -100.00, // Invalid amount
            'currency' => 'USD',
            'method' => 'credit_card',
            'card_number' => '4111111111111111',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.payment.process')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->with('Processing payment', ['tenant_id' => $tenantId, 'payment_data' => $paymentData]);

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Payment processing failed', [
                'tenant_id' => $tenantId,
                'payment_data' => $paymentData,
                'error' => 'Invalid amount: must be positive',
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid amount: must be positive');

        $this->billingService->processPayment($tenantId, $paymentData);
    }

    public function testHandleDisputeWithError(): void
    {
        $tenantId = 'test-tenant-1';
        $disputeData = [
            'statement_id' => 'invalid-statement',
            'reason' => 'Incorrect charges',
            'details' => 'Charged for unused services',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.dispute.handle')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->with('Handling billing dispute', ['tenant_id' => $tenantId, 'dispute_data' => $disputeData]);

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Dispute handling failed', [
                'tenant_id' => $tenantId,
                'dispute_data' => $disputeData,
                'error' => 'Invalid statement ID',
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid statement ID');

        $this->billingService->handleDispute($tenantId, $disputeData);
    }

    public function testGenerateReportWithError(): void
    {
        $filters = [
            'start_date' => '2024-01-31',
            'end_date' => '2024-01-01', // Invalid date range
            'tenant_id' => 'test-tenant-1',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('billing.report.generate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->with('Generating billing report', ['filters' => $filters]);

        $this->logging->expects($this->once())
            ->method('error')
            ->with('Report generation failed', [
                'filters' => $filters,
                'error' => 'Invalid date range: end date must be after start date',
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid date range: end date must be after start date');

        $this->billingService->generateReport($filters);
    }
} 