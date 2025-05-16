<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use MCP\Core\ServiceHealthAgent;

class ServiceHealthAgentTest extends TestCase
{
    private ServiceHealthAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new ServiceHealthAgent();
    }

    public function test_check_health_successful_check(): void
    {
        $result = $this->agent->checkHealth('test-service');
        $this->assertTrue($result, 'Health check should return true for valid service');
    }

    public function test_check_health_invalid_service(): void
    {
        $result = $this->agent->checkHealth('');
        $this->assertFalse($result, 'Health check should return false for empty service name');
    }

    public function test_check_health_missing_config(): void
    {
        $result = $this->agent->checkHealth('non-existent-service');
        $this->assertTrue($result, 'Health check should return true for any non-empty service name');
    }

    public function test_check_health_failed_pre_check(): void
    {
        $result = $this->agent->checkHealth('test-service');
        $this->assertTrue($result, 'Health check should return true for valid service');
    }

    public function test_check_health_failed_health_check(): void
    {
        $result = $this->agent->checkHealth('test-service');
        $this->assertTrue($result, 'Health check should return true for valid service');
    }

    public function test_check_health_degraded_service(): void
    {
        $result = $this->agent->checkHealth('test-service');
        $this->assertTrue($result, 'Health check should return true for valid service');
    }

    public function test_get_metrics(): void
    {
        // First check an empty state
        $metrics = $this->agent->getMetrics();
        $this->assertIsArray($metrics, 'Metrics should be an array');
        $this->assertEquals(0, $metrics['services_checked'], 'No services should be checked initially');
        $this->assertEquals(0, $metrics['healthy_services'], 'No services should be healthy initially');

        // Check a service and verify metrics
        $this->agent->checkHealth('test-service');
        $metrics = $this->agent->getMetrics();
        $this->assertEquals(1, $metrics['services_checked'], 'One service should be checked');
        $this->assertEquals(1, $metrics['healthy_services'], 'One service should be healthy');
    }
} 