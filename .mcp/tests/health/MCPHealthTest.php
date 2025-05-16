<?php

namespace Tests\MCP\Health;

use Tests\MCP\BaseTestCase;
use MCP\Core\Services\MCPService;
use MCP\Core\Services\DatabaseService;
use MCP\Core\Services\CacheService;
use MCP\Core\Services\MailService;
use MCP\Core\Services\QueueService;
use MCP\Core\Services\StorageService;

class MCPHealthTest extends BaseTestCase
{
    private MCPService $mcpService;
    private DatabaseService $dbService;
    private CacheService $cacheService;
    private MailService $mailService;
    private QueueService $queueService;
    private StorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcpService = new MCPService($this->serviceManager->getConnection('mcp'));
        $this->dbService = new DatabaseService(DB::connection());
        $this->cacheService = new CacheService(Redis::connection());
        $this->mailService = new MailService(Mail::mailer());
        $this->queueService = new QueueService(Queue::connection());
        $this->storageService = new StorageService(Storage::disk());
    }

    public function testServiceAvailability(): void
    {
        // Test MCP API availability
        $mcpHealth = $this->mcpService->checkHealth();
        $this->assertTrue($mcpHealth['status'] === 'healthy');
        $this->assertArrayHasKey('uptime', $mcpHealth);
        $this->assertArrayHasKey('version', $mcpHealth);
        $this->assertArrayHasKey('services', $mcpHealth);

        // Test database availability
        $dbHealth = $this->dbService->checkHealth();
        $this->assertTrue($dbHealth['status'] === 'healthy');
        $this->assertArrayHasKey('connections', $dbHealth);
        $this->assertArrayHasKey('performance', $dbHealth);
        $this->assertArrayHasKey('replication', $dbHealth);

        // Test cache availability
        $cacheHealth = $this->cacheService->checkHealth();
        $this->assertTrue($cacheHealth['status'] === 'healthy');
        $this->assertArrayHasKey('memory_usage', $cacheHealth);
        $this->assertArrayHasKey('connected_clients', $cacheHealth);
        $this->assertArrayHasKey('keyspace', $cacheHealth);

        // Test mail service availability
        $mailHealth = $this->mailService->checkHealth();
        $this->assertTrue($mailHealth['status'] === 'healthy');
        $this->assertArrayHasKey('queue_size', $mailHealth);
        $this->assertArrayHasKey('last_success', $mailHealth);
        $this->assertArrayHasKey('error_rate', $mailHealth);

        // Test queue service availability
        $queueHealth = $this->queueService->checkHealth();
        $this->assertTrue($queueHealth['status'] === 'healthy');
        $this->assertArrayHasKey('queue_size', $queueHealth);
        $this->assertArrayHasKey('processing_rate', $queueHealth);
        $this->assertArrayHasKey('error_rate', $queueHealth);

        // Test storage service availability
        $storageHealth = $this->storageService->checkHealth();
        $this->assertTrue($storageHealth['status'] === 'healthy');
        $this->assertArrayHasKey('disk_usage', $storageHealth);
        $this->assertArrayHasKey('write_performance', $storageHealth);
        $this->assertArrayHasKey('read_performance', $storageHealth);
    }

    public function testServiceMetrics(): void
    {
        // Test MCP API metrics
        $mcpMetrics = $this->mcpService->getMetrics();
        $this->assertArrayHasKey('request_rate', $mcpMetrics);
        $this->assertArrayHasKey('response_time', $mcpMetrics);
        $this->assertArrayHasKey('error_rate', $mcpMetrics);
        $this->assertArrayHasKey('active_connections', $mcpMetrics);

        // Test database metrics
        $dbMetrics = $this->dbService->getMetrics();
        $this->assertArrayHasKey('query_rate', $dbMetrics);
        $this->assertArrayHasKey('slow_queries', $dbMetrics);
        $this->assertArrayHasKey('connection_pool', $dbMetrics);
        $this->assertArrayHasKey('replication_lag', $dbMetrics);

        // Test cache metrics
        $cacheMetrics = $this->cacheService->getMetrics();
        $this->assertArrayHasKey('hit_rate', $cacheMetrics);
        $this->assertArrayHasKey('memory_usage', $cacheMetrics);
        $this->assertArrayHasKey('eviction_rate', $cacheMetrics);
        $this->assertArrayHasKey('connected_clients', $cacheMetrics);

        // Test mail metrics
        $mailMetrics = $this->mailService->getMetrics();
        $this->assertArrayHasKey('send_rate', $mailMetrics);
        $this->assertArrayHasKey('delivery_rate', $mailMetrics);
        $this->assertArrayHasKey('bounce_rate', $mailMetrics);
        $this->assertArrayHasKey('queue_size', $mailMetrics);

        // Test queue metrics
        $queueMetrics = $this->queueService->getMetrics();
        $this->assertArrayHasKey('job_rate', $queueMetrics);
        $this->assertArrayHasKey('processing_time', $queueMetrics);
        $this->assertArrayHasKey('failure_rate', $queueMetrics);
        $this->assertArrayHasKey('queue_size', $queueMetrics);

        // Test storage metrics
        $storageMetrics = $this->storageService->getMetrics();
        $this->assertArrayHasKey('read_rate', $storageMetrics);
        $this->assertArrayHasKey('write_rate', $storageMetrics);
        $this->assertArrayHasKey('disk_usage', $storageMetrics);
        $this->assertArrayHasKey('error_rate', $storageMetrics);
    }

    public function testServiceAlerts(): void
    {
        // Test MCP API alerts
        $mcpAlerts = $this->mcpService->getAlerts();
        $this->assertIsArray($mcpAlerts);
        foreach ($mcpAlerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }

        // Test database alerts
        $dbAlerts = $this->dbService->getAlerts();
        $this->assertIsArray($dbAlerts);
        foreach ($dbAlerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }

        // Test cache alerts
        $cacheAlerts = $this->cacheService->getAlerts();
        $this->assertIsArray($cacheAlerts);
        foreach ($cacheAlerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }

        // Test mail alerts
        $mailAlerts = $this->mailService->getAlerts();
        $this->assertIsArray($mailAlerts);
        foreach ($mailAlerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }

        // Test queue alerts
        $queueAlerts = $this->queueService->getAlerts();
        $this->assertIsArray($queueAlerts);
        foreach ($queueAlerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }

        // Test storage alerts
        $storageAlerts = $this->storageService->getAlerts();
        $this->assertIsArray($storageAlerts);
        foreach ($storageAlerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }
    }

    public function testServiceDependencies(): void
    {
        // Test MCP API dependencies
        $mcpDeps = $this->mcpService->getDependencies();
        $this->assertIsArray($mcpDeps);
        foreach ($mcpDeps as $dep) {
            $this->assertArrayHasKey('service', $dep);
            $this->assertArrayHasKey('status', $dep);
            $this->assertArrayHasKey('latency', $dep);
        }

        // Test database dependencies
        $dbDeps = $this->dbService->getDependencies();
        $this->assertIsArray($dbDeps);
        foreach ($dbDeps as $dep) {
            $this->assertArrayHasKey('service', $dep);
            $this->assertArrayHasKey('status', $dep);
            $this->assertArrayHasKey('latency', $dep);
        }

        // Test cache dependencies
        $cacheDeps = $this->cacheService->getDependencies();
        $this->assertIsArray($cacheDeps);
        foreach ($cacheDeps as $dep) {
            $this->assertArrayHasKey('service', $dep);
            $this->assertArrayHasKey('status', $dep);
            $this->assertArrayHasKey('latency', $dep);
        }

        // Test mail dependencies
        $mailDeps = $this->mailService->getDependencies();
        $this->assertIsArray($mailDeps);
        foreach ($mailDeps as $dep) {
            $this->assertArrayHasKey('service', $dep);
            $this->assertArrayHasKey('status', $dep);
            $this->assertArrayHasKey('latency', $dep);
        }

        // Test queue dependencies
        $queueDeps = $this->queueService->getDependencies();
        $this->assertIsArray($queueDeps);
        foreach ($queueDeps as $dep) {
            $this->assertArrayHasKey('service', $dep);
            $this->assertArrayHasKey('status', $dep);
            $this->assertArrayHasKey('latency', $dep);
        }

        // Test storage dependencies
        $storageDeps = $this->storageService->getDependencies();
        $this->assertIsArray($storageDeps);
        foreach ($storageDeps as $dep) {
            $this->assertArrayHasKey('service', $dep);
            $this->assertArrayHasKey('status', $dep);
            $this->assertArrayHasKey('latency', $dep);
        }
    }

    public function testServiceConfiguration(): void
    {
        // Test MCP API configuration
        $mcpConfig = $this->mcpService->getConfiguration();
        $this->assertArrayHasKey('version', $mcpConfig);
        $this->assertArrayHasKey('environment', $mcpConfig);
        $this->assertArrayHasKey('features', $mcpConfig);
        $this->assertArrayHasKey('limits', $mcpConfig);

        // Test database configuration
        $dbConfig = $this->dbService->getConfiguration();
        $this->assertArrayHasKey('version', $dbConfig);
        $this->assertArrayHasKey('environment', $dbConfig);
        $this->assertArrayHasKey('features', $dbConfig);
        $this->assertArrayHasKey('limits', $dbConfig);

        // Test cache configuration
        $cacheConfig = $this->cacheService->getConfiguration();
        $this->assertArrayHasKey('version', $cacheConfig);
        $this->assertArrayHasKey('environment', $cacheConfig);
        $this->assertArrayHasKey('features', $cacheConfig);
        $this->assertArrayHasKey('limits', $cacheConfig);

        // Test mail configuration
        $mailConfig = $this->mailService->getConfiguration();
        $this->assertArrayHasKey('version', $mailConfig);
        $this->assertArrayHasKey('environment', $mailConfig);
        $this->assertArrayHasKey('features', $mailConfig);
        $this->assertArrayHasKey('limits', $mailConfig);

        // Test queue configuration
        $queueConfig = $this->queueService->getConfiguration();
        $this->assertArrayHasKey('version', $queueConfig);
        $this->assertArrayHasKey('environment', $queueConfig);
        $this->assertArrayHasKey('features', $queueConfig);
        $this->assertArrayHasKey('limits', $queueConfig);

        // Test storage configuration
        $storageConfig = $this->storageService->getConfiguration();
        $this->assertArrayHasKey('version', $storageConfig);
        $this->assertArrayHasKey('environment', $storageConfig);
        $this->assertArrayHasKey('features', $storageConfig);
        $this->assertArrayHasKey('limits', $storageConfig);
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        $this->cacheService->delete('test_key');
        
        parent::tearDown();
    }
} 