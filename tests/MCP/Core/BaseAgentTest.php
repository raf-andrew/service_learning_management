<?php

namespace Tests\MCP\Core;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\BaseAgent;
use Illuminate\Support\Facades\Log;

class TestAgent extends BaseAgent
{
    protected function initialize(): void
    {
        $this->category = 'test';
        $this->capabilities = ['test_action'];
    }

    protected function executeAction(string $action, array $params): array
    {
        return match($action) {
            'test_action' => ['result' => 'success'],
            default => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };
    }
}

class BaseAgentTest extends BaseTestCase
{
    protected TestAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new TestAgent();
    }

    public function test_agent_has_correct_category(): void
    {
        $this->assertEquals('test', $this->agent->getCategory());
    }

    public function test_agent_has_capabilities(): void
    {
        $this->assertTrue($this->agent->hasCapability('test_action'));
        $this->assertFalse($this->agent->hasCapability('nonexistent_action'));
    }

    public function test_can_get_all_capabilities(): void
    {
        $capabilities = $this->agent->getCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertEquals('test_action', $capabilities[0]);
    }

    public function test_can_set_and_get_config(): void
    {
        $config = ['key' => 'value'];
        $this->agent->setConfig($config);
        $this->assertEquals($config, $this->agent->getConfig());
    }

    public function test_can_record_and_get_metrics(): void
    {
        $this->agent->recordMetric('test_metric', 42);
        $metrics = $this->agent->getMetrics();
        
        $this->assertArrayHasKey('test_metric', $metrics);
        $this->assertEquals(42, $metrics['test_metric']['value']);
    }

    public function test_can_perform_valid_action(): void
    {
        $result = $this->agent->performAction('test_action');
        $this->assertEquals(['result' => 'success'], $result);
    }

    public function test_throws_exception_for_invalid_action(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('invalid_action');
    }

    public function test_records_metrics_for_successful_action(): void
    {
        $this->agent->performAction('test_action');
        $metrics = $this->agent->getMetrics();
        
        $this->assertArrayHasKey('test_action', $metrics);
        $this->assertEquals('success', $metrics['test_action']['value']['status']);
    }

    public function test_records_metrics_for_failed_action(): void
    {
        $agent = new class extends BaseAgent {
            protected function initialize(): void
            {
                $this->category = 'test';
                $this->capabilities = ['failing_action'];
            }

            protected function executeAction(string $action, array $params): array
            {
                throw new \Exception('Test failure');
            }
        };

        try {
            $agent->performAction('failing_action');
        } catch (\Exception $e) {
            $metrics = $agent->getMetrics();
            $this->assertArrayHasKey('failing_action', $metrics);
            $this->assertEquals('error', $metrics['failing_action']['value']['status']);
            $this->assertEquals('Test failure', $metrics['failing_action']['value']['error']);
        }
    }

    public function test_validates_required_params(): void
    {
        $agent = new class extends BaseAgent {
            protected function initialize(): void
            {
                $this->category = 'test';
                $this->capabilities = ['test_action'];
            }

            protected function executeAction(string $action, array $params): array
            {
                $this->validateParams($params, ['required_param']);
                return [];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $agent->performAction('test_action', []);
    }

    public function test_logs_error_on_action_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Agent action failed: failing_action', \Mockery::any());

        $agent = new class extends BaseAgent {
            protected function initialize(): void
            {
                $this->category = 'test';
                $this->capabilities = ['failing_action'];
            }

            protected function executeAction(string $action, array $params): array
            {
                throw new \Exception('Test failure');
            }
        };

        try {
            $agent->performAction('failing_action');
        } catch (\Exception $e) {
            // Expected exception
        }
    }
} 