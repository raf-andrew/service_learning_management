<?php

namespace Tests\MCP\EndToEnd;

use PHPUnit\Framework\TestCase;
use MCP\Core\Server\AgenticServer;
use MCP\Core\Services\AccessControl;
use MCP\Core\Services\TenantService;
use MCP\Core\Services\LoggingService;

class EndToEndTest extends TestCase
{
    protected AgenticServer $server;
    protected AccessControl $accessControl;
    protected TenantService $tenantService;
    protected LoggingService $loggingService;
    protected string $tenantId;
    protected array $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize the MCP server
        $this->server = new AgenticServer();
        
        // Initialize core services
        $this->accessControl = new AccessControl();
        $this->tenantService = new TenantService();
        $this->loggingService = new LoggingService();
        
        // Configure test environment
        $this->server->configure([
            'environment' => 'testing',
            'debug' => true,
            'log_errors' => true,
            'error_reporting' => E_ALL
        ]);

        // Create test tenant and credentials
        $this->tenantId = $this->createTestTenant();
        $this->credentials = $this->createTestCredentials($this->tenantId);
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

    /**
     * Helper method to wait for async operations
     */
    protected function waitForAsyncOperation(string $operationId, int $timeout = 30): array
    {
        $startTime = time();
        $status = null;

        while (time() - $startTime < $timeout) {
            $response = $this->makeAuthenticatedRequest(
                "/api/v1/operations/{$operationId}",
                $this->credentials
            );

            if ($response['status'] === 200) {
                $status = $response['data']['status'];
                if ($status === 'completed' || $status === 'failed') {
                    break;
                }
            }

            sleep(1);
        }

        if ($status === null) {
            throw new \RuntimeException("Operation timed out after {$timeout} seconds");
        }

        return $response['data'];
    }

    /**
     * Helper method to verify system state
     */
    protected function verifySystemState(array $expectedState): void
    {
        $response = $this->makeAuthenticatedRequest(
            '/api/v1/system/state',
            $this->credentials
        );

        $this->assertEquals(200, $response['status']);
        $this->assertEquals($expectedState, $response['data']);
    }

    /**
     * Helper method to check logs
     */
    protected function checkLogs(string $type, array $criteria): array
    {
        return $this->loggingService->query([
            'tenant_id' => $this->tenantId,
            'type' => $type,
            'criteria' => $criteria
        ]);
    }
} 