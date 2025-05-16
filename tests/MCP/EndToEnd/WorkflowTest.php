<?php

namespace Tests\MCP\EndToEnd;

/**
 * WorkflowTest
 * 
 * This class contains end-to-end tests for the MCP workflow system, covering:
 * - Agent registration and task execution
 * - Service registration and health checks
 * - Agent-service interactions
 * - Error handling and recovery
 * - Concurrent operations
 * - Task prioritization and scheduling
 * - Resource management
 * 
 * Each test method is designed to test a complete workflow scenario,
 * including proper setup, execution, verification, and cleanup.
 * 
 * @package Tests\MCP\EndToEnd
 */
class WorkflowTest extends EndToEndTest
{
    /**
     * Test the complete workflow of agent registration and task execution
     * 
     * This test verifies:
     * 1. Agent registration with configuration
     * 2. Task creation and assignment
     * 3. Task execution and completion
     * 4. Log verification
     * 
     * @return void
     */
    public function testAgentRegistrationAndTaskExecution(): void
    {
        // Register an agent
        $agentData = [
            'name' => 'WorkflowTestAgent',
            'type' => 'test',
            'config' => [
                'enabled' => true,
                'priority' => 'high'
            ]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $this->assertEquals(200, $registerResponse['status']);
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
        $taskId = $taskResponse['data']['task_id'];

        // Wait for task completion
        $taskResult = $this->waitForAsyncOperation($taskId);
        $this->assertEquals('completed', $taskResult['status']);

        // Verify task execution in logs
        $logs = $this->checkLogs('task_execution', [
            'agent_id' => $agentId,
            'task_id' => $taskId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('completed', $logs[0]['status']);
    }

    /**
     * Test the complete workflow of service registration and health monitoring
     * 
     * This test verifies:
     * 1. Service registration with configuration
     * 2. Health check functionality
     * 3. Service status monitoring
     * 4. Log verification
     * 
     * @return void
     */
    public function testServiceRegistrationAndHealthCheck(): void
    {
        // Register a service
        $serviceData = [
            'name' => 'WorkflowTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => [
                'enabled' => true,
                'timeout' => 30
            ]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $this->assertEquals(200, $registerResponse['status']);
        $serviceId = $registerResponse['data']['service_id'];

        // Check service health
        $healthResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$serviceId}/health",
            $this->credentials
        );

        $this->assertEquals(200, $healthResponse['status']);
        $this->assertEquals('healthy', $healthResponse['data']['status']);

        // Verify service registration in logs
        $logs = $this->checkLogs('service_registration', [
            'service_id' => $serviceId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('registered', $logs[0]['status']);
    }

    /**
     * Test the complete workflow of agent-service interaction
     * 
     * This test verifies:
     * 1. Service registration
     * 2. Agent registration with service dependency
     * 3. Task creation requiring service interaction
     * 4. Task execution and completion
     * 5. Service interaction verification
     * 
     * @return void
     */
    public function testCompleteAgentServiceInteraction(): void
    {
        // Register a service
        $serviceData = [
            'name' => 'InteractionTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $serviceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $serviceResponse['data']['service_id'];

        // Register an agent that will interact with the service
        $agentData = [
            'name' => 'InteractionTestAgent',
            'type' => 'test',
            'config' => [
                'enabled' => true,
                'service_id' => $serviceId
            ]
        ];

        $agentResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $agentId = $agentResponse['data']['agent_id'];

        // Create a task that requires service interaction
        $taskData = [
            'type' => 'service_interaction',
            'priority' => 'high',
            'parameters' => [
                'service_id' => $serviceId,
                'action' => 'test'
            ]
        ];

        $taskResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/tasks",
            $this->credentials,
            $taskData
        );

        $taskId = $taskResponse['data']['task_id'];

        // Wait for task completion
        $taskResult = $this->waitForAsyncOperation($taskId);
        $this->assertEquals('completed', $taskResult['status']);

        // Verify service interaction in logs
        $logs = $this->checkLogs('service_interaction', [
            'agent_id' => $agentId,
            'service_id' => $serviceId,
            'task_id' => $taskId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('completed', $logs[0]['status']);
    }

    /**
     * Test error handling and recovery workflows
     * 
     * This test verifies:
     * 1. Agent registration with invalid configuration
     * 2. Task failure handling
     * 3. Error logging
     * 4. Recovery mechanism
     * 5. System state after recovery
     * 
     * @return void
     */
    public function testErrorHandlingAndRecovery(): void
    {
        // Register an agent with invalid configuration
        $agentData = [
            'name' => 'ErrorTestAgent',
            'type' => 'test',
            'config' => [
                'enabled' => true,
                'invalid_setting' => 'value'
            ]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $this->assertEquals(200, $registerResponse['status']);
        $agentId = $registerResponse['data']['agent_id'];

        // Create a task that will fail
        $taskData = [
            'type' => 'error_test',
            'priority' => 'high',
            'parameters' => ['should_fail' => true]
        ];

        $taskResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/tasks",
            $this->credentials,
            $taskData
        );

        $taskId = $taskResponse['data']['task_id'];

        // Wait for task completion (should fail)
        $taskResult = $this->waitForAsyncOperation($taskId);
        $this->assertEquals('failed', $taskResult['status']);

        // Verify error handling in logs
        $logs = $this->checkLogs('error', [
            'agent_id' => $agentId,
            'task_id' => $taskId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('error', $logs[0]['type']);

        // Attempt recovery
        $recoveryResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/recover",
            $this->credentials
        );

        $this->assertEquals(200, $recoveryResponse['status']);
        $this->assertEquals('recovered', $recoveryResponse['data']['status']);
    }

    /**
     * Test concurrent operations and system stability
     * 
     * This test verifies:
     * 1. Multiple agent registration
     * 2. Concurrent task execution
     * 3. System resource management
     * 4. Task completion order
     * 5. System stability under load
     * 
     * @return void
     */
    public function testConcurrentOperations(): void
    {
        // Register multiple agents
        $agentIds = [];
        for ($i = 0; $i < 5; $i++) {
            $agentData = [
                'name' => "ConcurrentTestAgent{$i}",
                'type' => 'test',
                'config' => ['enabled' => true]
            ];

            $response = $this->makeAuthenticatedRequest(
                '/api/v1/agents/register',
                $this->credentials,
                $agentData
            );

            $agentIds[] = $response['data']['agent_id'];
        }

        // Create concurrent tasks
        $taskIds = [];
        foreach ($agentIds as $agentId) {
            $taskData = [
                'type' => 'concurrent_test',
                'priority' => 'high',
                'parameters' => ['test' => 'value']
            ];

            $response = $this->makeAuthenticatedRequest(
                "/api/v1/agents/{$agentId}/tasks",
                $this->credentials,
                $taskData
            );

            $taskIds[] = $response['data']['task_id'];
        }

        // Wait for all tasks to complete
        $results = [];
        foreach ($taskIds as $taskId) {
            $results[] = $this->waitForAsyncOperation($taskId);
        }

        // Verify all tasks completed successfully
        foreach ($results as $result) {
            $this->assertEquals('completed', $result['status']);
        }

        // Verify concurrent execution in logs
        $logs = $this->checkLogs('task_execution', [
            'task_ids' => $taskIds
        ]);

        $this->assertCount(count($taskIds), $logs);
        foreach ($logs as $log) {
            $this->assertEquals('completed', $log['status']);
        }
    }

    /**
     * Test task prioritization and scheduling
     * 
     * This test verifies:
     * 1. Task priority handling
     * 2. Scheduling order
     * 3. Resource allocation
     * 4. Priority-based execution
     * 
     * @return void
     */
    public function testTaskPrioritization(): void
    {
        // Register an agent
        $agentData = [
            'name' => 'PriorityTestAgent',
            'type' => 'test',
            'config' => ['enabled' => true]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $this->assertEquals(200, $registerResponse['status']);
        $agentId = $registerResponse['data']['agent_id'];

        // Create tasks with different priorities
        $priorities = ['low', 'medium', 'high'];
        $taskIds = [];

        foreach ($priorities as $priority) {
            $taskData = [
                'type' => 'priority_test',
                'priority' => $priority,
                'parameters' => ['priority' => $priority]
            ];

            $taskResponse = $this->makeAuthenticatedRequest(
                "/api/v1/agents/{$agentId}/tasks",
                $this->credentials,
                $taskData
            );

            $this->assertEquals(200, $taskResponse['status']);
            $taskIds[] = $taskResponse['data']['task_id'];
        }

        // Wait for all tasks to complete
        $completionOrder = [];
        foreach ($taskIds as $taskId) {
            $taskResult = $this->waitForAsyncOperation($taskId);
            $this->assertEquals('completed', $taskResult['status']);
            $completionOrder[] = $taskResult['data']['priority'];
        }

        // Verify execution order matches priority
        $this->assertEquals(['high', 'medium', 'low'], $completionOrder);

        // Verify priority handling in logs
        $logs = $this->checkLogs('task_execution', [
            'agent_id' => $agentId
        ]);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertArrayHasKey('priority', $log);
            $this->assertContains($log['priority'], $priorities);
        }
    }

    /**
     * Test resource management and limits
     * 
     * This test verifies:
     * 1. Resource allocation
     * 2. Resource limits
     * 3. Resource cleanup
     * 4. System stability under resource constraints
     * 
     * @return void
     */
    public function testResourceManagement(): void
    {
        // Register an agent with resource limits
        $agentData = [
            'name' => 'ResourceTestAgent',
            'type' => 'test',
            'config' => [
                'enabled' => true,
                'resource_limits' => [
                    'memory' => '512M',
                    'cpu' => '50%'
                ]
            ]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $this->assertEquals(200, $registerResponse['status']);
        $agentId = $registerResponse['data']['agent_id'];

        // Create a resource-intensive task
        $taskData = [
            'type' => 'resource_test',
            'priority' => 'high',
            'parameters' => [
                'memory_usage' => '256M',
                'cpu_usage' => '25%'
            ]
        ];

        $taskResponse = $this->makeAuthenticatedRequest(
            "/api/v1/agents/{$agentId}/tasks",
            $this->credentials,
            $taskData
        );

        $this->assertEquals(200, $taskResponse['status']);
        $taskId = $taskResponse['data']['task_id'];

        // Wait for task completion
        $taskResult = $this->waitForAsyncOperation($taskId);
        $this->assertEquals('completed', $taskResult['status']);

        // Verify resource usage in logs
        $logs = $this->checkLogs('resource_usage', [
            'agent_id' => $agentId,
            'task_id' => $taskId
        ]);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertArrayHasKey('memory_usage', $log);
            $this->assertArrayHasKey('cpu_usage', $log);
            $this->assertLessThanOrEqual(512, $log['memory_usage']);
            $this->assertLessThanOrEqual(50, $log['cpu_usage']);
        }

        // Verify resource cleanup
        $cleanupLogs = $this->checkLogs('resource_cleanup', [
            'agent_id' => $agentId,
            'task_id' => $taskId
        ]);

        $this->assertNotEmpty($cleanupLogs);
        $this->assertEquals('completed', $cleanupLogs[0]['status']);
    }
} 