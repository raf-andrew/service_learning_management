<?php

namespace Tests\Unit\MCP\Core;

use PHPUnit\Framework\TestCase;
use MCP\Core\DeploymentAutomationAgent;

class DeploymentAutomationAgentTest extends TestCase
{
    private DeploymentAutomationAgent $agent;

    protected function setUp(): void
    {
        $this->agent = new DeploymentAutomationAgent();
    }

    public function testDeploySuccessfulDeployment(): void
    {
        $this->assertTrue($this->agent->deploy('production'));
    }

    public function testDeployInvalidEnvironment(): void
    {
        $this->assertFalse($this->agent->deploy(''));
    }

    public function testRollbackSuccessfulRollback(): void
    {
        $this->assertTrue($this->agent->rollback('production'));
    }

    public function testRollbackInvalidEnvironment(): void
    {
        $this->assertFalse($this->agent->rollback(''));
    }

    public function testGetMetrics(): void
    {
        $this->agent->deploy('staging');
        $this->agent->deploy('production');
        $this->agent->rollback('production');
        
        $metrics = $this->agent->getMetrics();
        $this->assertIsArray($metrics);
        $this->assertEquals(3, $metrics['total_operations']);
        $this->assertEquals(2, $metrics['deployments']);
        $this->assertEquals(1, $metrics['rollbacks']);
    }
} 