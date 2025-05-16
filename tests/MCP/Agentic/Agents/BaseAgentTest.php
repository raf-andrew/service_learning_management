<?php

namespace Tests\MCP\Agentic\Agents;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\BaseAgent;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;
use App\MCP\Agentic\Core\Services\TaskManager;
use Illuminate\Support\Facades\Config;
use Mockery;

class BaseAgentTest extends BaseAgenticTestCase
{
    protected BaseAgent $agent;
    protected TaskManager $taskManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->taskManager = Mockery::mock(TaskManager::class);
        
        $this->agent = new class($this->auditLogger, $this->accessControl, $this->taskManager) extends BaseAgent {
            public function getType(): string
            {
                return 'test_agent';
            }
            
            public function getCapabilities(): array
            {
                return ['test_capability'];
            }
        };
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('test_agent', $this->agent->getType());
        $this->assertEquals(['test_capability'], $this->agent->getCapabilities());
    }

    public function test_agent_requires_access_control(): void
    {
        $this->assertAccessControl('agent:execute', 'test_agent', true);
        $this->assertTrue($this->agent->canExecute());
    }

    public function test_agent_logs_actions(): void
    {
        $this->assertAuditLog('Agent test_agent initialized', [
            'agent_type' => 'test_agent',
            'capabilities' => ['test_capability'],
        ]);
        
        $this->agent->initialize();
    }

    public function test_agent_handles_errors(): void
    {
        $this->taskManager->shouldReceive('executeTask')
            ->once()
            ->andThrow(new \Exception('Test error'));
            
        $this->assertErrorLogged('Agent test_agent failed to execute task', [
            'agent_type' => 'test_agent',
            'error' => 'Test error',
        ]);
        
        $this->agent->executeTask('test_task');
    }

    public function test_agent_requires_human_review_for_critical_actions(): void
    {
        $this->assertHumanReviewRequired('agent:critical_action', [
            'agent_type' => 'test_agent',
            'action' => 'critical_action',
        ]);
        
        $this->assertTrue($this->agent->requiresHumanReview('critical_action'));
    }

    public function test_agent_respects_tenant_isolation(): void
    {
        $this->assertTenantIsolation('tenant_1', function() {
            $this->agent->setTenant('tenant_1');
            $this->assertEquals('tenant_1', $this->agent->getTenant());
        });
    }

    public function test_agent_logs_audit_trail(): void
    {
        $this->assertAuditTrail('Agent test_agent executed task', [
            'agent_type' => 'test_agent',
            'task' => 'test_task',
            'result' => 'success',
        ]);
        
        $this->agent->logAudit('executed task', [
            'task' => 'test_task',
            'result' => 'success',
        ]);
    }

    public function test_agent_handles_task_failures(): void
    {
        $this->taskManager->shouldReceive('executeTask')
            ->once()
            ->andReturn(['status' => 'failed', 'reason' => 'Test failure']);
            
        $this->assertFailureLogged('Agent test_agent task failed', [
            'agent_type' => 'test_agent',
            'task' => 'test_task',
            'reason' => 'Test failure',
        ]);
        
        $this->agent->executeTask('test_task');
    }

    public function test_agent_validates_capabilities(): void
    {
        $this->assertTrue($this->agent->hasCapability('test_capability'));
        $this->assertFalse($this->agent->hasCapability('invalid_capability'));
    }

    public function test_agent_respects_environment_restrictions(): void
    {
        Config::set('mcp.agentic.environment', 'production');
        
        $this->assertFalse($this->agent->canExecuteInEnvironment());
        
        Config::set('mcp.agentic.environment', 'testing');
        
        $this->assertTrue($this->agent->canExecuteInEnvironment());
    }

    public function test_agent_manages_lifecycle(): void
    {
        $this->assertAuditLog('Agent test_agent started', [
            'agent_type' => 'test_agent',
        ]);
        
        $this->agent->start();
        
        $this->assertTrue($this->agent->isRunning());
        
        $this->assertAuditLog('Agent test_agent stopped', [
            'agent_type' => 'test_agent',
        ]);
        
        $this->agent->stop();
        
        $this->assertFalse($this->agent->isRunning());
    }
} 