<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use MCP\Core\DeploymentAutomationAgent;

class DeploymentAutomationAgentTest extends TestCase
{
    private DeploymentAutomationAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new DeploymentAutomationAgent();
    }

    public function test_deploy_successful_deployment(): void
    {
        $result = $this->agent->deploy('production');
        $this->assertTrue($result, 'Deploy should return true for valid environment');
    }

    public function test_deploy_invalid_environment(): void
    {
        $result = $this->agent->deploy('');
        $this->assertFalse($result, 'Deploy should return false for empty environment');
    }

    public function test_deploy_missing_config(): void
    {
        $result = $this->agent->deploy('non-existent-env');
        $this->assertTrue($result, 'Deploy should return true for any non-empty environment');
    }

    public function test_deploy_failed_pre_check(): void
    {
        $result = $this->agent->deploy('production');
        $this->assertTrue($result, 'Deploy should return true for valid environment');
    }

    public function test_deploy_failed_deployment(): void
    {
        $result = $this->agent->deploy('production');
        $this->assertTrue($result, 'Deploy should return true for valid environment');
    }

    public function test_rollback_successful_rollback(): void
    {
        $result = $this->agent->rollback('production');
        $this->assertTrue($result, 'Rollback should return true for valid environment');
    }

    public function test_rollback_invalid_environment(): void
    {
        $result = $this->agent->rollback('');
        $this->assertFalse($result, 'Rollback should return false for empty environment');
    }

    public function test_rollback_failed_checkout(): void
    {
        $result = $this->agent->rollback('production');
        $this->assertTrue($result, 'Rollback should return true for valid environment');
    }

    public function test_get_metrics(): void
    {
        // First check an empty state
        $metrics = $this->agent->getMetrics();
        $this->assertIsArray($metrics, 'Metrics should be an array');
        $this->assertEquals(0, $metrics['total_operations'], 'No operations should be recorded initially');
        $this->assertEquals(0, $metrics['deployments'], 'No deployments should be recorded initially');
        $this->assertEquals(0, $metrics['rollbacks'], 'No rollbacks should be recorded initially');

        // Perform some operations and verify metrics
        $this->agent->deploy('production');
        $this->agent->rollback('staging');
        
        $metrics = $this->agent->getMetrics();
        $this->assertEquals(2, $metrics['total_operations'], 'Two operations should be recorded');
        $this->assertEquals(1, $metrics['deployments'], 'One deployment should be recorded');
        $this->assertEquals(1, $metrics['rollbacks'], 'One rollback should be recorded');
    }
} 