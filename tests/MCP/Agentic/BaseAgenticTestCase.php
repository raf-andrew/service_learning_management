<?php

namespace Tests\MCP\Agentic;

use Tests\MCP\BaseTestCase;
use App\MCP\Agentic\Core\Server\AgenticServer;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use Illuminate\Support\Facades\Config;
use Mockery;

class BaseAgenticTestCase extends BaseTestCase
{
    protected AgenticServer $server;
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock services
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        $this->accessControl = Mockery::mock(AccessControl::class);
        
        // Configure test environment
        Config::set('mcp.agentic.enabled', true);
        Config::set('mcp.agentic.environment', 'testing');
        
        // Initialize server
        $this->server = new AgenticServer(
            $this->auditLogger,
            $this->accessControl
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function assertAuditLog(string $action, array $context = []): void
    {
        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('agentic', $action, $context);
    }

    protected function assertAccessControl(string $action, string $resource, bool $allowed): void
    {
        $this->accessControl->shouldReceive('check')
            ->once()
            ->with($action, $resource)
            ->andReturn($allowed);
    }

    protected function assertHumanReviewRequired(string $action, array $context = []): void
    {
        $this->accessControl->shouldReceive('requiresHumanReview')
            ->once()
            ->with($action, $context)
            ->andReturn(true);
    }

    protected function assertNoHumanReviewRequired(string $action, array $context = []): void
    {
        $this->accessControl->shouldReceive('requiresHumanReview')
            ->once()
            ->with($action, $context)
            ->andReturn(false);
    }

    protected function assertTenantIsolation(string $tenantId, callable $callback): void
    {
        $this->accessControl->shouldReceive('getCurrentTenant')
            ->once()
            ->andReturn($tenantId);
            
        $callback();
        
        $this->accessControl->shouldReceive('validateTenantAccess')
            ->once()
            ->with($tenantId)
            ->andReturn(true);
    }

    protected function assertAuditTrail(string $action, array $context = []): void
    {
        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('audit', $action, array_merge($context, [
                'timestamp' => Mockery::type('string'),
                'user_id' => Mockery::type('string'),
                'tenant_id' => Mockery::type('string'),
            ]));
    }

    protected function assertErrorLogged(string $message, array $context = []): void
    {
        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('error', $message, array_merge($context, [
                'timestamp' => Mockery::type('string'),
                'stack_trace' => Mockery::type('string'),
            ]));
    }

    protected function assertFailureLogged(string $message, array $context = []): void
    {
        $this->auditLogger->shouldReceive('log')
            ->once()
            ->with('failure', $message, array_merge($context, [
                'timestamp' => Mockery::type('string'),
                'details' => Mockery::type('array'),
            ]));
    }
} 