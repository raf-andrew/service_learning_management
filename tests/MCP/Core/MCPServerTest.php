<?php

namespace Tests\MCP\Core;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\MCPServer;
use App\MCP\Core\Services\AuditLogger;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Core\Service;
use App\MCP\Core\Agent;
use Illuminate\Support\Facades\Config;

class MCPServerTest extends BaseTestCase
{
    protected MCPServer $server;
    protected AuditLogger $auditLogger;
    protected AccessControl $accessControl;
    protected TaskManager $taskManager;
    protected HealthMonitor $healthMonitor;
    protected object $mockService;
    protected object $mockAgent;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock services
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);
        $this->taskManager = $this->createMock(TaskManager::class);
        $this->healthMonitor = $this->createMock(HealthMonitor::class);
        
        // Configure test environment
        Config::set('app.env', 'testing');
        
        // Initialize server
        $this->server = new MCPServer(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager,
            $this->healthMonitor
        );

        // Create mock service and agent
        $this->mockService = $this->createMock(Service::class);
        $this->mockService->method('getName')->willReturn('TestService');
        $this->mockService->method('isHealthy')->willReturn(true);

        $this->mockAgent = $this->createMock(Agent::class);
        $this->mockAgent->method('getName')->willReturn('TestAgent');
        $this->mockAgent->method('isRunning')->willReturn(true);
    }

    public function testServerIsEnabledInNonProductionEnvironment(): void
    {
        $this->assertTrue($this->server->isEnabled());
    }

    public function testServerIsDisabledInProductionEnvironment(): void
    {
        Config::set('app.env', 'production');
        $server = new MCPServer(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager,
            $this->healthMonitor
        );
        $this->assertFalse($server->isEnabled());
    }

    public function testCoreServicesAreRegistered(): void
    {
        $this->assertTrue($this->server->hasService('audit_logger'));
        $this->assertTrue($this->server->hasService('access_control'));
        $this->assertTrue($this->server->hasService('task_manager'));
        $this->assertTrue($this->server->hasService('health_monitor'));
    }

    public function testAgentRegistration(): void
    {
        $this->healthMonitor->expects($this->once())
            ->method('checkAgentHealth')
            ->with('test_agent', $this->mockAgent)
            ->willReturn(['status' => 'healthy']);

        $this->server->registerAgent('test_agent', $this->mockAgent);
        $this->assertTrue($this->server->hasAgent('test_agent'));
    }

    public function testServiceRegistration(): void
    {
        $this->healthMonitor->expects($this->once())
            ->method('checkServiceHealth')
            ->with('test_service', $this->mockService)
            ->willReturn(['status' => 'healthy']);

        $this->server->registerService('test_service', $this->mockService);
        $this->assertTrue($this->server->hasService('test_service'));
    }

    public function testDefaultRolesAreCreated(): void
    {
        $this->accessControl->method('getCapability')
            ->willReturn(['permissions' => ['manage_agents']]);
        $this->assertTrue($this->server->hasRole('admin'));
        $this->assertTrue($this->server->hasRole('user'));
    }

    public function testDefaultPermissionsAreAssigned(): void
    {
        $this->accessControl->method('getCapability')
            ->willReturn(['permissions' => ['manage_agents']]);
        $this->assertTrue($this->server->hasPermission('admin', 'manage_agents'));
        $this->assertTrue($this->server->hasPermission('user', 'use_agents'));
    }

    public function testPermissionChecking(): void
    {
        $this->accessControl->method('check')
            ->willReturnMap([
                ['manage_agents', 'admin', true],
                ['manage_agents', 'user', false]
            ]);

        $this->assertTrue($this->server->checkPermission('admin', 'manage_agents'));
        $this->assertFalse($this->server->checkPermission('user', 'manage_agents'));
    }

    public function testRoleChecking(): void
    {
        $this->assertTrue($this->server->checkRole('admin', 'admin'));
        $this->assertFalse($this->server->checkRole('user', 'admin'));
    }

    public function testAgentRegistrationIsDisabledInProduction(): void
    {
        Config::set('app.env', 'production');
        $server = new MCPServer(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager,
            $this->healthMonitor
        );
        $this->expectException(\RuntimeException::class);
        $server->registerAgent('test_agent', $this->mockAgent);
    }

    public function testServiceRegistrationIsDisabledInProduction(): void
    {
        Config::set('app.env', 'production');
        $server = new MCPServer(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager,
            $this->healthMonitor
        );
        $this->expectException(\RuntimeException::class);
        $server->registerService('test_service', $this->mockService);
    }

    public function testSystemHealthCheck(): void
    {
        $expectedHealth = [
            'status' => true,
            'services' => ['test_service' => ['status' => 'healthy']],
            'agents' => ['test_agent' => ['status' => 'healthy']],
            'metrics' => [
                'memory_usage' => 50,
                'cpu_usage' => 30
            ]
        ];

        $this->healthMonitor->method('getSystemHealth')
            ->willReturn($expectedHealth);

        $health = $this->server->getSystemHealth();
        $this->assertEquals($expectedHealth, $health);
    }

    public function testAgentHealthCheck(): void
    {
        $this->server->registerAgent('test_agent', $this->mockAgent);

        $expectedHealth = [
            'status' => 'healthy',
            'metrics' => ['tasks_completed' => 100]
        ];

        $this->healthMonitor->method('checkAgentHealth')
            ->with('test_agent', $this->mockAgent)
            ->willReturn($expectedHealth);

        $health = $this->server->checkAgentHealth('test_agent');
        $this->assertEquals($expectedHealth, $health);
    }

    public function testServiceHealthCheck(): void
    {
        $this->server->registerService('test_service', $this->mockService);

        $expectedHealth = [
            'status' => 'healthy',
            'metrics' => ['memory_usage' => 50]
        ];

        $this->healthMonitor->method('checkServiceHealth')
            ->with('test_service', $this->mockService)
            ->willReturn($expectedHealth);

        $health = $this->server->checkServiceHealth('test_service');
        $this->assertEquals($expectedHealth, $health);
    }

    public function testHealthCheckForNonexistentAgent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->server->checkAgentHealth('nonexistent_agent');
    }

    public function testHealthCheckForNonexistentService(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->server->checkServiceHealth('nonexistent_service');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Check for test failures
        if ($this->hasFailed()) {
            file_put_contents(
                '.failures',
                $this->getName() . ' failed: ' . implode(', ', $this->getFailures()),
                FILE_APPEND
            );
        }
    }
} 