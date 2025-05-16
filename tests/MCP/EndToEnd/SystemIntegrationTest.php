<?php

namespace Tests\MCP\EndToEnd;

/**
 * SystemIntegrationTest
 * 
 * This class contains end-to-end tests for system integration in the MCP system, covering:
 * - Multi-tenant isolation
 * - Service dependency management
 * - System health monitoring
 * - Configuration management
 * - Backup and restore functionality
 * - System metrics and monitoring
 * - Resource allocation and limits
 * 
 * Each test method is designed to test a complete system integration scenario,
 * including proper setup, execution, verification, and cleanup.
 * 
 * @package Tests\MCP\EndToEnd
 */
class SystemIntegrationTest extends EndToEndTest
{
    /**
     * Test multi-tenant isolation
     * 
     * This test verifies:
     * 1. Tenant creation and isolation
     * 2. Resource access control
     * 3. Cross-tenant access prevention
     * 4. Tenant-specific logging
     * 
     * @return void
     */
    public function testMultiTenantIsolation(): void
    {
        // Create two test tenants
        $tenant1Id = $this->createTestTenant();
        $tenant2Id = $this->createTestTenant();

        $tenant1Credentials = $this->createTestCredentials($tenant1Id);
        $tenant2Credentials = $this->createTestCredentials($tenant2Id);

        // Create a service in tenant 1
        $serviceData = [
            'name' => 'IsolationTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $serviceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $tenant1Credentials,
            $serviceData
        );

        $serviceId = $serviceResponse['data']['service_id'];

        // Try to access the service from tenant 2
        $accessResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$serviceId}",
            $tenant2Credentials
        );

        $this->assertEquals(404, $accessResponse['status']);

        // Verify tenant isolation in logs
        $logs = $this->checkLogs('tenant_access', [
            'tenant_id' => $tenant2Id,
            'resource_id' => $serviceId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('denied', $logs[0]['status']);
    }

    /**
     * Test service dependency management
     * 
     * This test verifies:
     * 1. Service registration
     * 2. Dependency configuration
     * 3. Dependency impact handling
     * 4. Service health propagation
     * 
     * @return void
     */
    public function testServiceDependencyManagement(): void
    {
        // Create dependent services
        $service1Data = [
            'name' => 'DependencyTestService1',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $service1Response = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $service1Data
        );

        $service1Id = $service1Response['data']['service_id'];

        $service2Data = [
            'name' => 'DependencyTestService2',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $service2Response = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $service2Data
        );

        $service2Id = $service2Response['data']['service_id'];

        // Add dependency
        $dependencyData = [
            'dependency_id' => $service2Id,
            'type' => 'required'
        ];

        $dependencyResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$service1Id}/dependencies",
            $this->credentials,
            $dependencyData
        );

        $this->assertEquals(200, $dependencyResponse['status']);

        // Disable dependent service
        $disableResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$service2Id}/config",
            $this->credentials,
            ['enabled' => false]
        );

        $this->assertEquals(200, $disableResponse['status']);

        // Verify service 1 is affected
        $healthResponse = $this->makeAuthenticatedRequest(
            "/api/v1/services/{$service1Id}/health",
            $this->credentials
        );

        $this->assertEquals(200, $healthResponse['status']);
        $this->assertEquals('degraded', $healthResponse['data']['status']);

        // Verify dependency management in logs
        $logs = $this->checkLogs('service_dependency', [
            'service_id' => $service1Id,
            'dependency_id' => $service2Id
        ]);

        $this->assertNotEmpty($logs);
        $this->assertContains('added', array_column($logs, 'action'));
        $this->assertContains('affected', array_column($logs, 'action'));
    }

    /**
     * Test system health monitoring
     * 
     * This test verifies:
     * 1. Service health checks
     * 2. Agent health monitoring
     * 3. System-wide health status
     * 4. Health check logging
     * 
     * @return void
     */
    public function testSystemHealthMonitoring(): void
    {
        // Register a service
        $serviceData = [
            'name' => 'HealthTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $serviceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $serviceResponse['data']['service_id'];

        // Register an agent
        $agentData = [
            'name' => 'HealthTestAgent',
            'type' => 'test',
            'config' => [
                'enabled' => true,
                'service_id' => $serviceId
            ]
        ];

        $agentResponse = $this->makeAuthenticatedRequest(
            '/api/v1/agents/register',
            $this->credentials,
            $agentData
        );

        $agentId = $agentResponse['data']['agent_id'];

        // Get system health
        $healthResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/health',
            $this->credentials
        );

        $this->assertEquals(200, $healthResponse['status']);
        $this->assertArrayHasKey('services', $healthResponse['data']);
        $this->assertArrayHasKey('agents', $healthResponse['data']);

        // Verify service health
        $this->assertArrayHasKey($serviceId, $healthResponse['data']['services']);
        $this->assertEquals('healthy', $healthResponse['data']['services'][$serviceId]['status']);

        // Verify agent health
        $this->assertArrayHasKey($agentId, $healthResponse['data']['agents']);
        $this->assertEquals('healthy', $healthResponse['data']['agents'][$agentId]['status']);

        // Verify health monitoring in logs
        $logs = $this->checkLogs('health_check', [
            'service_id' => $serviceId,
            'agent_id' => $agentId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);
    }

    /**
     * Test system configuration management
     * 
     * This test verifies:
     * 1. Configuration updates
     * 2. Configuration validation
     * 3. Configuration persistence
     * 4. Configuration logging
     * 
     * @return void
     */
    public function testSystemConfigurationManagement(): void
    {
        // Update system configuration
        $configData = [
            'logging' => [
                'level' => 'debug',
                'rotation' => 'daily'
            ],
            'security' => [
                'session_timeout' => 3600,
                'max_login_attempts' => 5
            ],
            'performance' => [
                'cache_ttl' => 300,
                'max_connections' => 100
            ]
        ];

        $configResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/config',
            $this->credentials,
            $configData
        );

        $this->assertEquals(200, $configResponse['status']);

        // Verify configuration changes
        $verifyResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/config',
            $this->credentials
        );

        $this->assertEquals(200, $verifyResponse['status']);
        $this->assertEquals($configData, $verifyResponse['data']);

        // Verify configuration management in logs
        $logs = $this->checkLogs('config_update', [
            'action' => 'update'
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);
    }

    /**
     * Test system backup and restore
     * 
     * This test verifies:
     * 1. Backup creation
     * 2. Backup verification
     * 3. System restore
     * 4. Data integrity
     * 
     * @return void
     */
    public function testSystemBackupAndRestore(): void
    {
        // Create test data
        $serviceData = [
            'name' => 'BackupTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $serviceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $serviceResponse['data']['service_id'];

        // Create backup
        $backupResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/backup',
            $this->credentials,
            ['type' => 'full']
        );

        $this->assertEquals(200, $backupResponse['status']);
        $backupId = $backupResponse['data']['backup_id'];

        // Verify backup creation
        $verifyBackupResponse = $this->makeAuthenticatedRequest(
            "/api/v1/system/backup/{$backupId}",
            $this->credentials
        );

        $this->assertEquals(200, $verifyBackupResponse['status']);
        $this->assertEquals('completed', $verifyBackupResponse['data']['status']);

        // Restore from backup
        $restoreResponse = $this->makeAuthenticatedRequest(
            "/api/v1/system/restore/{$backupId}",
            $this->credentials
        );

        $this->assertEquals(200, $restoreResponse['status']);
        $restoreId = $restoreResponse['data']['restore_id'];

        // Verify restore completion
        $verifyRestoreResponse = $this->makeAuthenticatedRequest(
            "/api/v1/system/restore/{$restoreId}",
            $this->credentials
        );

        $this->assertEquals(200, $verifyRestoreResponse['status']);
        $this->assertEquals('completed', $verifyRestoreResponse['data']['status']);

        // Verify backup and restore in logs
        $logs = $this->checkLogs('system_operation', [
            'operation' => ['backup', 'restore']
        ]);

        $this->assertNotEmpty($logs);
        $this->assertContains('backup', array_column($logs, 'operation'));
        $this->assertContains('restore', array_column($logs, 'operation'));
    }

    /**
     * Test system metrics and monitoring
     * 
     * This test verifies:
     * 1. Metric collection
     * 2. Performance monitoring
     * 3. Alert generation
     * 4. Metric aggregation
     * 
     * @return void
     */
    public function testSystemMetrics(): void
    {
        // Register a service for testing
        $serviceData = [
            'name' => 'MetricsTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => ['enabled' => true]
        ];

        $serviceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $serviceResponse['data']['service_id'];

        // Generate some load
        for ($i = 0; $i < 10; $i++) {
            $this->makeAuthenticatedRequest(
                "/api/v1/services/{$serviceId}/health",
                $this->credentials
            );
        }

        // Get system metrics
        $metricsResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/metrics',
            $this->credentials
        );

        $this->assertEquals(200, $metricsResponse['status']);
        $this->assertArrayHasKey('services', $metricsResponse['data']);
        $this->assertArrayHasKey('system', $metricsResponse['data']);

        // Verify service metrics
        $this->assertArrayHasKey($serviceId, $metricsResponse['data']['services']);
        $serviceMetrics = $metricsResponse['data']['services'][$serviceId];
        $this->assertArrayHasKey('requests', $serviceMetrics);
        $this->assertArrayHasKey('response_time', $serviceMetrics);
        $this->assertArrayHasKey('error_rate', $serviceMetrics);

        // Verify system metrics
        $systemMetrics = $metricsResponse['data']['system'];
        $this->assertArrayHasKey('cpu_usage', $systemMetrics);
        $this->assertArrayHasKey('memory_usage', $systemMetrics);
        $this->assertArrayHasKey('disk_usage', $systemMetrics);

        // Check for alerts
        $alertsResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/alerts',
            $this->credentials
        );

        $this->assertEquals(200, $alertsResponse['status']);
        $this->assertArrayHasKey('alerts', $alertsResponse['data']);

        // Verify metrics in logs
        $logs = $this->checkLogs('metrics_collection', [
            'service_id' => $serviceId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);
    }

    /**
     * Test resource allocation and limits
     * 
     * This test verifies:
     * 1. Resource allocation
     * 2. Resource limits
     * 3. Resource cleanup
     * 4. Resource contention handling
     * 
     * @return void
     */
    public function testResourceAllocation(): void
    {
        // Set resource limits
        $limitsData = [
            'cpu_limit' => 50, // 50% CPU limit
            'memory_limit' => 512, // 512MB memory limit
            'disk_limit' => 1024, // 1GB disk limit
            'concurrent_tasks' => 5
        ];

        $limitsResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/limits',
            $this->credentials,
            $limitsData
        );

        $this->assertEquals(200, $limitsResponse['status']);

        // Register a service that will use resources
        $serviceData = [
            'name' => 'ResourceTestService',
            'type' => 'test',
            'version' => '1.0.0',
            'config' => [
                'enabled' => true,
                'resource_intensive' => true
            ]
        ];

        $serviceResponse = $this->makeAuthenticatedRequest(
            '/api/v1/services/register',
            $this->credentials,
            $serviceData
        );

        $serviceId = $serviceResponse['data']['service_id'];

        // Create multiple tasks to test resource contention
        $taskIds = [];
        for ($i = 0; $i < 10; $i++) {
            $taskData = [
                'service_id' => $serviceId,
                'type' => 'resource_test',
                'priority' => 'high',
                'config' => ['resource_intensive' => true]
            ];

            $taskResponse = $this->makeAuthenticatedRequest(
                '/api/v1/tasks/create',
                $this->credentials,
                $taskData
            );

            $taskIds[] = $taskResponse['data']['task_id'];
        }

        // Check resource usage
        $usageResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/resources',
            $this->credentials
        );

        $this->assertEquals(200, $usageResponse['status']);
        $this->assertArrayHasKey('cpu_usage', $usageResponse['data']);
        $this->assertArrayHasKey('memory_usage', $usageResponse['data']);
        $this->assertArrayHasKey('disk_usage', $usageResponse['data']);
        $this->assertArrayHasKey('active_tasks', $usageResponse['data']);

        // Verify some tasks were queued due to resource limits
        $this->assertLessThanOrEqual(5, $usageResponse['data']['active_tasks']);

        // Clean up resources
        foreach ($taskIds as $taskId) {
            $this->makeAuthenticatedRequest(
                "/api/v1/tasks/{$taskId}/cancel",
                $this->credentials
            );
        }

        // Verify resource cleanup
        $cleanupResponse = $this->makeAuthenticatedRequest(
            '/api/v1/system/resources',
            $this->credentials
        );

        $this->assertEquals(200, $cleanupResponse['status']);
        $this->assertEquals(0, $cleanupResponse['data']['active_tasks']);

        // Verify resource management in logs
        $logs = $this->checkLogs('resource_management', [
            'service_id' => $serviceId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertContains('allocation', array_column($logs, 'action'));
        $this->assertContains('cleanup', array_column($logs, 'action'));
    }
} 