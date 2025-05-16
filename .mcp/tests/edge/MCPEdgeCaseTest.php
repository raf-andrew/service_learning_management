<?php

namespace Tests\MCP\Edge;

use Tests\MCP\BaseTestCase;
use MCP\Core\Services\MCPService;
use MCP\Core\Services\DatabaseService;
use MCP\Core\Services\CacheService;
use MCP\Core\Services\MailService;
use MCP\Core\Services\QueueService;
use MCP\Core\Services\StorageService;

class MCPEdgeCaseTest extends BaseTestCase
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

    public function testLargePayloadHandling(): void
    {
        // Test MCP API with large payload
        $largePayload = str_repeat('a', 10 * 1024 * 1024); // 10MB payload
        $response = $this->mcpService->request('POST', '/test', ['data' => $largePayload]);
        $this->assertEquals(413, $response->getStatusCode());

        // Test database with large query
        $largeQuery = 'SELECT * FROM users WHERE id IN (' . implode(',', range(1, 10000)) . ')';
        try {
            $this->dbService->query($largeQuery);
            $this->fail('Expected database exception for large query');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }

        // Test cache with large value
        $largeValue = str_repeat('b', 512 * 1024); // 512KB value
        try {
            $this->cacheService->set('large_key', $largeValue);
            $this->fail('Expected cache exception for large value');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Redis\Exception::class, $e);
        }
    }

    public function testConcurrentRequests(): void
    {
        $promises = [];
        $client = $this->serviceManager->getConnection('mcp');

        // Test 100 concurrent MCP requests
        for ($i = 0; $i < 100; $i++) {
            $promises[] = $client->getAsync('/health');
        }

        $results = \GuzzleHttp\Promise\Utils::unwrap($promises);
        foreach ($results as $response) {
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Test concurrent database operations
        $dbPromises = [];
        for ($i = 0; $i < 50; $i++) {
            $dbPromises[] = $this->dbService->asyncQuery('SELECT 1');
        }

        $dbResults = \GuzzleHttp\Promise\Utils::unwrap($dbPromises);
        foreach ($dbResults as $result) {
            $this->assertNotNull($result);
        }
    }

    public function testInvalidInputs(): void
    {
        // Test MCP API with invalid inputs
        $invalidInputs = [
            null,
            '',
            ' ',
            '!@#$%^&*()',
            str_repeat('a', 1000),
            ['invalid' => 'data'],
            new \stdClass()
        ];

        foreach ($invalidInputs as $input) {
            try {
                $this->mcpService->request('POST', '/test', $input);
                $this->fail('Expected exception for invalid input');
            } catch (\Exception $e) {
                $this->assertInstanceOf(\GuzzleHttp\Exception\ClientException::class, $e);
            }
        }

        // Test database with invalid inputs
        $invalidQueries = [
            'SELECT * FROM',
            'INSERT INTO',
            'UPDATE',
            'DELETE FROM',
            'DROP TABLE users',
            '; DROP TABLE users;'
        ];

        foreach ($invalidQueries as $query) {
            try {
                $this->dbService->query($query);
                $this->fail('Expected database exception for invalid query');
            } catch (\Exception $e) {
                $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
            }
        }
    }

    public function testServiceFailover(): void
    {
        // Test MCP API failover
        $this->mcpService->disconnect();
        $this->assertTrue($this->mcpService->checkHealth()['status'] === 'healthy');

        // Test database failover
        $this->dbService->disconnect();
        $this->assertTrue($this->dbService->checkHealth()['status'] === 'healthy');

        // Test cache failover
        $this->cacheService->disconnect();
        $this->assertTrue($this->cacheService->checkHealth()['status'] === 'healthy');

        // Test mail failover
        $this->mailService->disconnect();
        $this->assertTrue($this->mailService->checkHealth()['status'] === 'healthy');

        // Test queue failover
        $this->queueService->disconnect();
        $this->assertTrue($this->queueService->checkHealth()['status'] === 'healthy');

        // Test storage failover
        $this->storageService->disconnect();
        $this->assertTrue($this->storageService->checkHealth()['status'] === 'healthy');
    }

    public function testResourceLimits(): void
    {
        // Test MCP API rate limiting
        for ($i = 0; $i < 1000; $i++) {
            $response = $this->mcpService->request('GET', '/health');
            if ($response->getStatusCode() === 429) {
                break;
            }
        }
        $this->assertEquals(429, $response->getStatusCode());

        // Test database connection limits
        $connections = [];
        try {
            for ($i = 0; $i < 1000; $i++) {
                $connections[] = $this->dbService->getConnection();
            }
            $this->fail('Expected database exception for connection limit');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }

        // Test cache memory limits
        $keys = [];
        try {
            for ($i = 0; $i < 1000000; $i++) {
                $keys[] = $this->cacheService->set("key_{$i}", "value_{$i}");
            }
            $this->fail('Expected cache exception for memory limit');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Redis\Exception::class, $e);
        }
    }

    public function testErrorRecovery(): void
    {
        // Test MCP API error recovery
        $this->mcpService->simulateError();
        $this->assertTrue($this->mcpService->checkHealth()['status'] === 'healthy');

        // Test database error recovery
        $this->dbService->simulateError();
        $this->assertTrue($this->dbService->checkHealth()['status'] === 'healthy');

        // Test cache error recovery
        $this->cacheService->simulateError();
        $this->assertTrue($this->cacheService->checkHealth()['status'] === 'healthy');

        // Test mail error recovery
        $this->mailService->simulateError();
        $this->assertTrue($this->mailService->checkHealth()['status'] === 'healthy');

        // Test queue error recovery
        $this->queueService->simulateError();
        $this->assertTrue($this->queueService->checkHealth()['status'] === 'healthy');

        // Test storage error recovery
        $this->storageService->simulateError();
        $this->assertTrue($this->storageService->checkHealth()['status'] === 'healthy');
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        $this->cacheService->delete('large_key');
        $this->cacheService->delete('test_key');
        
        parent::tearDown();
    }
} 