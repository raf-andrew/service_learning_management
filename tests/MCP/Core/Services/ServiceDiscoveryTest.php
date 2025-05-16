<?php

namespace Tests\MCP\Core\Services;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\Services\ServiceDiscovery;

class ServiceDiscoveryTest extends BaseTestCase
{
    protected ServiceDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discovery = new ServiceDiscovery();
    }

    public function test_can_register_and_retrieve_service(): void
    {
        $service = new \stdClass();
        $metadata = ['version' => '1.0'];
        $this->discovery->register('test_service', $service, $metadata);

        $result = $this->discovery->get('test_service');
        $this->assertSame($service, $result['service']);
        $this->assertEquals($metadata, $result['metadata']);
        $this->assertEquals('active', $result['status']);
    }

    public function test_can_register_and_retrieve_endpoint(): void
    {
        $service = new \stdClass();
        $this->discovery->register('test_service', $service);
        $this->discovery->registerEndpoint('test_service', '/api/test', 'POST');

        $endpoints = $this->discovery->getEndpoints('test_service');
        $this->assertCount(1, $endpoints);
        $this->assertEquals('/api/test', $endpoints[0]['endpoint']);
        $this->assertEquals('POST', $endpoints[0]['method']);
    }

    public function test_returns_null_for_nonexistent_service(): void
    {
        $this->assertNull($this->discovery->get('nonexistent'));
    }

    public function test_returns_empty_array_for_nonexistent_service_endpoints(): void
    {
        $this->assertEmpty($this->discovery->getEndpoints('nonexistent'));
    }

    public function test_can_get_all_services(): void
    {
        $service1 = new \stdClass();
        $service2 = new \stdClass();
        $this->discovery->register('service1', $service1);
        $this->discovery->register('service2', $service2);

        $services = $this->discovery->getAllServices();
        $this->assertCount(2, $services);
        $this->assertSame($service1, $services['service1']['service']);
        $this->assertSame($service2, $services['service2']['service']);
    }

    public function test_can_deregister_service(): void
    {
        $service = new \stdClass();
        $this->discovery->register('test_service', $service);
        $this->discovery->registerEndpoint('test_service', '/api/test');

        $this->discovery->deregister('test_service');
        $this->assertNull($this->discovery->get('test_service'));
        $this->assertEmpty($this->discovery->getEndpoints('test_service'));
    }

    public function test_can_update_service_status(): void
    {
        $service = new \stdClass();
        $this->discovery->register('test_service', $service);
        $this->discovery->updateStatus('test_service', 'inactive');

        $result = $this->discovery->get('test_service');
        $this->assertEquals('inactive', $result['status']);
    }
} 