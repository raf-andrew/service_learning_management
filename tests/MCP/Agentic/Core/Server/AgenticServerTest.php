<?php

namespace Tests\MCP\Agentic\Core\Server;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Core\Server\AgenticServer;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;
use Illuminate\Support\Facades\Config;

class AgenticServerTest extends BaseAgenticTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure test environment
        Config::set('mcp.agentic.enabled', true);
        Config::set('mcp.agentic.environment', 'testing');
    }

    public function testServerInitialization(): void
    {
        $this->assertFalse($this->server->isRunning());
        $this->assertEquals('testing', $this->server->getEnvironment());
        $this->assertEmpty($this->server->getServices());
        $this->assertEmpty($this->server->getAgents());
    }

    public function testServiceRegistration(): void
    {
        $service = $this->createMock(\App\MCP\Agentic\Core\Service::class);
        $this->server->registerService('test_service', $service);
        $this->assertTrue($this->server->hasService('test_service'));
    }

    public function testServiceRegistrationPreventsDuplicates(): void
    {
        $service = $this->createMock(\App\MCP\Agentic\Core\Service::class);
        $this->server->registerService('test_service', $service);
        $this->expectException(\RuntimeException::class);
        $this->server->registerService('test_service', $service);
    }

    public function testAgentRegistration(): void
    {
        $agent = $this->createMock(\App\MCP\Agentic\Core\Agent::class);
        $this->server->registerAgent('test_agent', $agent);
        $this->assertTrue($this->server->hasAgent('test_agent'));
    }

    public function testAgentRegistrationPreventsDuplicates(): void
    {
        $agent = $this->createMock(\App\MCP\Agentic\Core\Agent::class);
        $this->server->registerAgent('test_agent', $agent);
        $this->expectException(\RuntimeException::class);
        $this->server->registerAgent('test_agent', $agent);
    }

    public function testServerStart(): void
    {
        $this->server->start();
        $this->assertTrue($this->server->isRunning());
    }

    public function testServerStop(): void
    {
        $this->server->start();
        $this->server->stop();
        $this->assertFalse($this->server->isRunning());
    }

    public function testHealthCheck(): void
    {
        $service = $this->createMock(\App\MCP\Agentic\Core\Service::class);
        $service->method('isHealthy')->willReturn(true);
        $this->server->registerService('test_service', $service);

        $agent = $this->createMock(\App\MCP\Agentic\Core\Agent::class);
        $agent->method('isRunning')->willReturn(true);
        $this->server->registerAgent('test_agent', $agent);

        $health = $this->server->checkHealth();
        $this->assertTrue($health['status']);
        $this->assertTrue($health['services']['test_service']);
        $this->assertTrue($health['agents']['test_agent']);
    }

    public function testHealthCheckWithUnhealthyService(): void
    {
        $service = $this->createMock(\App\MCP\Agentic\Core\Service::class);
        $service->method('isHealthy')->willReturn(false);
        $this->server->registerService('test_service', $service);

        $health = $this->server->checkHealth();
        $this->assertFalse($health['status']);
        $this->assertFalse($health['services']['test_service']);
    }

    public function testHealthCheckWithStoppedAgent(): void
    {
        $agent = $this->createMock(\App\MCP\Agentic\Core\Agent::class);
        $agent->method('isRunning')->willReturn(false);
        $this->server->registerAgent('test_agent', $agent);

        $health = $this->server->checkHealth();
        $this->assertFalse($health['status']);
        $this->assertFalse($health['agents']['test_agent']);
    }

    public function testEnvironmentConfiguration(): void
    {
        Config::set('mcp.agentic.environment', 'production');
        $server = new AgenticServer(
            $this->auditLogger,
            $this->accessControl,
            $this->taskManager
        );
        $this->assertEquals('production', $server->getEnvironment());
    }
} 