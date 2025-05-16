<?php

namespace Tests\Unit\MCP\Core;

use PHPUnit\Framework\TestCase;
use MCP\Core\ServiceHealthAgent;

class ServiceHealthAgentTest extends TestCase
{
    private ServiceHealthAgent $agent;

    protected function setUp(): void
    {
        $this->agent = new ServiceHealthAgent();
    }

    public function testCheckHealthSuccessfulCheck(): void
    {
        $this->assertTrue($this->agent->checkHealth('test-service'));
    }

    public function testCheckHealthInvalidService(): void
    {
        $this->assertFalse($this->agent->checkHealth(''));
    }

    public function testGetMetrics(): void
    {
        $this->agent->checkHealth('service1');
        $this->agent->checkHealth('service2');
        
        $metrics = $this->agent->getMetrics();
        $this->assertIsArray($metrics);
        $this->assertEquals(2, $metrics['services_checked']);
        $this->assertEquals(2, $metrics['healthy_services']);
    }
} 