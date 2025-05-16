<?php

namespace Tests\MCP\Integration\Api;

use PHPUnit\Framework\TestCase;
use MCP\Core\Server\AgenticServer;
use MCP\Core\Services\AccessControl;
use MCP\Core\Services\TenantService;

class ApiIntegrationTest extends TestCase
{
    protected AgenticServer $server;
    protected AccessControl $accessControl;
    protected TenantService $tenantService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize the MCP server
        $this->server = new AgenticServer();
        
        // Initialize core services
        $this->accessControl = new AccessControl();
        $this->tenantService = new TenantService();
        
        // Configure test environment
        $this->server->configure([
            'environment' => 'testing',
            'debug' => true,
            'log_errors' => true,
            'error_reporting' => E_ALL
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        $this->server->shutdown();
        
        parent::tearDown();
    }

    /**
     * Helper method to create a test tenant
     */
    protected function createTestTenant(): string
    {
        return $this->tenantService->create([
            'name' => 'Test Tenant ' . uniqid(),
            'status' => 'active',
            'plan' => 'test'
        ]);
    }

    /**
     * Helper method to create test credentials
     */
    protected function createTestCredentials(string $tenantId): array
    {
        return $this->accessControl->createCredentials([
            'tenant_id' => $tenantId,
            'type' => 'api',
            'permissions' => ['read', 'write']
        ]);
    }

    /**
     * Helper method to make authenticated API requests
     */
    protected function makeAuthenticatedRequest(string $endpoint, array $credentials, array $data = []): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $credentials['token'],
            'Content-Type' => 'application/json',
            'X-Tenant-ID' => $credentials['tenant_id']
        ];

        return $this->server->request($endpoint, $data, $headers);
    }
} 