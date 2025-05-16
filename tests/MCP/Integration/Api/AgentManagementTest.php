<?php

namespace Tests\MCP\Integration\Api;

use MCP\Agentic\Agents\BaseAgent;
use MCP\Exceptions\AgentException;

class AgentManagementTest extends ApiIntegrationTest
{
    private string $tenantId;
    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tenant and credentials
        $this->tenantId = $this->createTestTenant();
        $this->credentials = $this->createTestCredentials($this->tenantId);
    }

    public function testAgentRegistration(): void
    {
        $agentData = [
            'name' => 'TestAgent',
            'type' => 'test',
            'config' => [
                'enabled' => true,
                'priority' => 'high'
            ]
        ];

        $response = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('agent_id', $response['data']);
        $this->assertEquals($agentData['name'], $response['data']['name']);
    }

    public function testAgentHealthCheck(): void
    {
        // First register an agent
        $agentData = [
            'name' => 'HealthTestAgent',
            'type' => 'test',
            'config' => ['enabled' => true]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $agentId = $registerResponse['data']['agent_id'];

        // Check agent health
        $healthResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/health",
            $this->credentials
        );

        $this->assertEquals(200, $healthResponse['status']);
        $this->assertArrayHasKey('status', $healthResponse['data']);
        $this->assertEquals('healthy', $healthResponse['data']['status']);
    }

    public function testAgentConfiguration(): void
    {
        // Register an agent
        $agentData = [
            'name' => 'ConfigTestAgent',
            'type' => 'test',
            'config' => ['enabled' => true]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $agentId = $registerResponse['data']['agent_id'];

        // Update agent configuration
        $newConfig = [
            'enabled' => false,
            'priority' => 'low',
            'custom_setting' => 'value'
        ];

        $configResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/config",
            $this->credentials,
            $newConfig
        );

        $this->assertEquals(200, $configResponse['status']);
        $this->assertEquals($newConfig, $configResponse['data']['config']);
    }

    public function testAgentTaskManagement(): void
    {
        // Register an agent
        $agentData = [
            'name' => 'TaskTestAgent',
            'type' => 'test',
            'config' => ['enabled' => true]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $agentId = $registerResponse['data']['agent_id'];

        // Create a task
        $taskData = [
            'type' => 'test_task',
            'priority' => 'high',
            'parameters' => ['test' => 'value']
        ];

        $taskResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/tasks",
            $this->credentials,
            $taskData
        );

        $this->assertEquals(200, $taskResponse['status']);
        $this->assertArrayHasKey('task_id', $taskResponse['data']);

        // Check task status
        $taskId = $taskResponse['data']['task_id'];
        $statusResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/tasks/{$taskId}",
            $this->credentials
        );

        $this->assertEquals(200, $statusResponse['status']);
        $this->assertArrayHasKey('status', $statusResponse['data']);
    }

    public function testAgentErrorHandling(): void
    {
        // Test invalid agent registration
        $invalidAgentData = [
            'name' => '', // Invalid empty name
            'type' => 'test'
        ];

        $response = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $invalidAgentData
        );

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testAgentAuthorization(): void
    {
        // Create credentials with limited permissions
        $limitedCredentials = $this->accessControl->createCredentials([
            'tenant_id' => $this->tenantId,
            'type' => 'api',
            'permissions' => ['read'] // No write permission
        ]);

        // Attempt to register an agent with limited credentials
        $agentData = [
            'name' => 'AuthTestAgent',
            'type' => 'test',
            'config' => ['enabled' => true]
        ];

        $response = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $limitedCredentials,
            $agentData
        );

        $this->assertEquals(403, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }
} 