<?php

namespace Tests\MCP\Core\Services;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\Services\AgentRegistry;

class AgentRegistryTest extends BaseTestCase
{
    protected AgentRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new AgentRegistry();
    }

    public function test_can_register_and_retrieve_agent(): void
    {
        $agent = new \stdClass();
        $this->registry->register('test', 'test_agent', $agent);
        $this->assertSame($agent, $this->registry->get('test', 'test_agent'));
    }

    public function test_returns_null_for_nonexistent_agent(): void
    {
        $this->assertNull($this->registry->get('nonexistent', 'agent'));
    }

    public function test_can_get_all_agents_in_category(): void
    {
        $agent1 = new \stdClass();
        $agent2 = new \stdClass();
        $this->registry->register('test', 'agent1', $agent1);
        $this->registry->register('test', 'agent2', $agent2);

        $agents = $this->registry->getAll('test');
        $this->assertCount(2, $agents);
        $this->assertSame($agent1, $agents['agent1']);
        $this->assertSame($agent2, $agents['agent2']);
    }

    public function test_can_get_all_agents(): void
    {
        $agent1 = new \stdClass();
        $agent2 = new \stdClass();
        $this->registry->register('test1', 'agent1', $agent1);
        $this->registry->register('test2', 'agent2', $agent2);

        $agents = $this->registry->getAll();
        $this->assertCount(2, $agents);
        $this->assertSame($agent1, $agents['test1']['agent1']);
        $this->assertSame($agent2, $agents['test2']['agent2']);
    }

    public function test_can_deregister_agent(): void
    {
        $agent = new \stdClass();
        $this->registry->register('test', 'test_agent', $agent);
        $this->registry->deregister('test', 'test_agent');
        $this->assertNull($this->registry->get('test', 'test_agent'));
    }

    public function test_returns_empty_array_for_nonexistent_category(): void
    {
        $this->assertEmpty($this->registry->getAll('nonexistent'));
    }
} 