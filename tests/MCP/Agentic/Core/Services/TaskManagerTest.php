<?php

namespace Tests\MCP\Agentic\Core\Services;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use Mockery;

class TaskManagerTest extends BaseAgenticTestCase
{
    protected TaskManager $taskManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskManager = new TaskManager($this->auditLogger, $this->accessControl);
    }

    public function test_can_register_task(): void
    {
        $handler = function($params) { return $params; };
        
        $this->assertAuditLog('Task test_task registered', [
            'task' => 'test_task',
            'capabilities' => ['test_capability'],
        ]);
        
        $this->taskManager->registerTask('test_task', $handler, ['test_capability']);
        
        $task = $this->taskManager->getTask('test_task');
        $this->assertNotNull($task);
        $this->assertEquals(['test_capability'], $task['capabilities']);
    }

    public function test_cannot_register_duplicate_task(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('test_task', $handler);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task test_task already registered');
        
        $this->taskManager->registerTask('test_task', $handler);
    }

    public function test_can_execute_task(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('test_task', $handler);
        
        $this->assertAccessControl('capability:test_capability', 'test_task', true);
        $this->assertNoHumanReviewRequired('task:execute', [
            'task' => 'test_task',
            'parameters' => ['test' => 'value'],
        ]);
        
        $result = $this->taskManager->executeTask('test_task', ['test' => 'value']);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(['test' => 'value'], $result['result']);
    }

    public function test_task_execution_requires_capabilities(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('test_task', $handler, ['required_capability']);
        
        $this->assertAccessControl('capability:required_capability', 'test_task', false);
        
        $result = $this->taskManager->executeTask('test_task');
        
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Missing capability: required_capability', $result['reason']);
    }

    public function test_task_execution_requires_human_review(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('test_task', $handler);
        
        $this->assertHumanReviewRequired('task:execute', [
            'task' => 'test_task',
            'parameters' => ['sensitive' => 'data'],
        ]);
        
        $result = $this->taskManager->executeTask('test_task', ['sensitive' => 'data']);
        
        $this->assertEquals('pending_review', $result['status']);
        $this->assertEquals('Task requires human review', $result['message']);
    }

    public function test_task_execution_handles_errors(): void
    {
        $handler = function($params) { throw new \Exception('Test error'); };
        
        $this->taskManager->registerTask('test_task', $handler);
        
        $this->assertAccessControl('capability:test_capability', 'test_task', true);
        $this->assertNoHumanReviewRequired('task:execute', [
            'task' => 'test_task',
            'parameters' => [],
        ]);
        
        $this->assertErrorLogged('Task test_task failed', [
            'task' => 'test_task',
            'parameters' => [],
            'error' => 'Test error',
        ]);
        
        $result = $this->taskManager->executeTask('test_task');
        
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Test error', $result['reason']);
    }

    public function test_can_remove_task(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('test_task', $handler);
        
        $this->assertAuditLog('Task test_task removed', [
            'task' => 'test_task',
        ]);
        
        $this->taskManager->removeTask('test_task');
        
        $this->assertNull($this->taskManager->getTask('test_task'));
    }

    public function test_can_update_task(): void
    {
        $handler1 = function($params) { return $params; };
        $handler2 = function($params) { return array_reverse($params); };
        
        $this->taskManager->registerTask('test_task', $handler1, ['capability1']);
        
        $this->assertAuditLog('Task test_task updated', [
            'task' => 'test_task',
            'capabilities' => ['capability2'],
        ]);
        
        $this->taskManager->updateTask('test_task', $handler2, ['capability2']);
        
        $task = $this->taskManager->getTask('test_task');
        $this->assertEquals(['capability2'], $task['capabilities']);
    }

    public function test_can_validate_task(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('test_task', $handler, ['required_capability']);
        
        $this->assertAccessControl('capability:required_capability', 'test_task', false);
        
        $validation = $this->taskManager->validateTask('test_task');
        
        $this->assertFalse($validation['valid']);
        $this->assertEquals('Missing capability: required_capability', $validation['reason']);
    }

    public function test_can_get_all_tasks(): void
    {
        $handler = function($params) { return $params; };
        
        $this->taskManager->registerTask('task1', $handler);
        $this->taskManager->registerTask('task2', $handler);
        
        $tasks = $this->taskManager->getAllTasks();
        
        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->has('task1'));
        $this->assertTrue($tasks->has('task2'));
    }
} 