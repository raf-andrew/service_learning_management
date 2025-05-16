<?php

namespace Tests\MCP\Integration\Api;

use MCP\Exceptions\ServiceException;

class ServiceManagementTest extends ApiIntegrationTest
{
    private string $tenantId;
    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tenant and credentials
        $this->tenantId = $this->createTestTenant();
        $this->credentials = $this->createTestCredentials($this->tenantId);
    }

    public function testServiceRegistration(): void
    {
        $serviceData = [
            'name' => 'TestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => [
                'enabled' => true,
                'timeout' => 30
            ]
        ];

        $response = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('service_id', $response['data']);
        $this->assertEquals($serviceData['name'], $response['data']['name']);
    }

    public function testServiceDiscovery(): void
    {
        // First register a service
        $serviceData = [
            'name' => 'DiscoveryTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $registerResponse['data']['service_id'];

        // Discover services
        $discoveryResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/discover',
            $this->credentials,
            ['type' => 'test']
        );

        $this->assertEquals(200, $discoveryResponse['status']);
        $this->assertIsArray($discoveryResponse['data']);
        $this->assertNotEmpty($discoveryResponse['data']);
        
        // Verify our service is in the results
        $found = false;
        foreach ($discoveryResponse['data'] as $service) {
            if ($service['service_id'] === $serviceId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testServiceHealthCheck(): void
    {
        // Register a service
        $serviceData = [
            'name' => 'HealthTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $registerResponse['data']['service_id'];

        // Check service health
        $healthResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$serviceId}/health",
            $this->credentials
        );

        $this->assertEquals(200, $healthResponse['status']);
        $this->assertArrayHasKey('status', $healthResponse['data']);
        $this->assertEquals('healthy', $healthResponse['data']['status']);
    }

    public function testServiceDependencyManagement(): void
    {
        // Register main service
        $mainServiceData = [
            'name' => 'MainTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $mainServiceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $mainServiceData
        );

        $mainServiceId = $mainServiceResponse['data']['service_id'];

        // Register dependency service
        $dependencyServiceData = [
            'name' => 'DependencyTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $dependencyServiceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $dependencyServiceData
        );

        $dependencyServiceId = $dependencyServiceResponse['data']['service_id'];

        // Add dependency
        $dependencyData = [
            'dependency_id' => $dependencyServiceId,
            'type' => 'required'
        ];

        $dependencyResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$mainServiceId}/dependencies",
            $this->credentials,
            $dependencyData
        );

        $this->assertEquals(200, $dependencyResponse['status']);
        $this->assertArrayHasKey('dependencies', $dependencyResponse['data']);
        $this->assertContains($dependencyServiceId, $dependencyResponse['data']['dependencies']);
    }

    public function testServiceErrorHandling(): void
    {
        // Test invalid service registration
        $invalidServiceData = [
            'name' => '', // Invalid empty name
            'type' => 'test',
            'version' => '1.0.0'
        ];

        $response = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $invalidServiceData
        );

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testServiceAuthorization(): void
    {
        // Create credentials with limited permissions
        $limitedCredentials = $this->accessControl->createCredentials([
            'tenant_id' => $this->tenantId,
            'type' => 'api',
            'permissions' => ['read'] // No write permission
        ]);

        // Attempt to register a service with limited credentials
        $serviceData = [
            'name' => 'AuthTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $response = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $limitedCredentials,
            $serviceData
        );

        $this->assertEquals(403, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }
} 