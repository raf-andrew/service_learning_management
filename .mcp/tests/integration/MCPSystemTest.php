<?php

namespace Tests\MCP\Integration;

use Tests\MCP\BaseTestCase;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use MCP\Core\Services\MCPService;
use MCP\Core\Services\DatabaseService;
use MCP\Core\Services\CacheService;
use MCP\Core\Services\MailService;

class MCPSystemTest extends BaseTestCase
{
    private MCPService $mcpService;
    private DatabaseService $dbService;
    private CacheService $cacheService;
    private MailService $mailService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcpService = new MCPService($this->serviceManager->getConnection('mcp'));
        $this->dbService = new DatabaseService(DB::connection());
        $this->cacheService = new CacheService(Redis::connection());
        $this->mailService = new MailService(Mail::mailer());
    }

    public function testSystemHealth(): void
    {
        // Test MCP service health
        $mcpHealth = $this->mcpService->checkHealth();
        $this->assertTrue($mcpHealth['status'] === 'healthy');
        $this->assertArrayHasKey('services', $mcpHealth);
        $this->assertArrayHasKey('version', $mcpHealth);

        // Test database health
        $dbHealth = $this->dbService->checkHealth();
        $this->assertTrue($dbHealth['status'] === 'healthy');
        $this->assertArrayHasKey('connections', $dbHealth);
        $this->assertArrayHasKey('performance', $dbHealth);

        // Test cache health
        $cacheHealth = $this->cacheService->checkHealth();
        $this->assertTrue($cacheHealth['status'] === 'healthy');
        $this->assertArrayHasKey('memory_usage', $cacheHealth);
        $this->assertArrayHasKey('connected_clients', $cacheHealth);

        // Test mail service health
        $mailHealth = $this->mailService->checkHealth();
        $this->assertTrue($mailHealth['status'] === 'healthy');
        $this->assertArrayHasKey('queue_size', $mailHealth);
        $this->assertArrayHasKey('last_success', $mailHealth);
    }

    public function testDataFlow(): void
    {
        // Test data creation
        $testData = [
            'name' => 'Test User',
            'email' => 'test@service-learning.edu',
            'role' => 'student'
        ];

        // Create test record
        $record = $this->dbService->create('users', $testData);
        $this->assertNotNull($record['id']);
        $this->assertEquals($testData['email'], $record['email']);

        // Cache the record
        $cacheKey = 'user:' . $record['id'];
        $this->cacheService->set($cacheKey, $record);
        $cachedRecord = $this->cacheService->get($cacheKey);
        $this->assertEquals($record, $cachedRecord);

        // Send notification
        $mailSent = $this->mailService->send(
            $record['email'],
            'Welcome to Service Learning',
            'Welcome to our platform!'
        );
        $this->assertTrue($mailSent);

        // Clean up
        $this->dbService->delete('users', $record['id']);
        $this->cacheService->delete($cacheKey);
    }

    public function testErrorHandling(): void
    {
        // Test invalid database operation
        try {
            $this->dbService->query('SELECT * FROM non_existent_table');
            $this->fail('Expected database exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }

        // Test invalid cache operation
        try {
            $this->cacheService->get('non_existent_key');
            $this->fail('Expected cache exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Redis\Exception::class, $e);
        }

        // Test invalid mail operation
        try {
            $this->mailService->send('invalid-email', 'Subject', 'Body');
            $this->fail('Expected mail exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Swift_TransportException::class, $e);
        }

        // Test invalid MCP operation
        try {
            $this->mcpService->request('GET', '/invalid-endpoint');
            $this->fail('Expected MCP exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\GuzzleHttp\Exception\ClientException::class, $e);
        }
    }

    public function testConcurrentOperations(): void
    {
        $promises = [];
        $client = $this->serviceManager->getConnection('mcp');

        // Test concurrent MCP requests
        for ($i = 0; $i < 5; $i++) {
            $promises[] = $client->getAsync('/health');
        }

        $results = \GuzzleHttp\Promise\Utils::unwrap($promises);
        foreach ($results as $response) {
            $this->assertEquals(200, $response->getStatusCode());
            $data = json_decode($response->getBody(), true);
            $this->assertEquals('healthy', $data['status']);
        }

        // Test concurrent database operations
        $dbPromises = [];
        for ($i = 0; $i < 5; $i++) {
            $dbPromises[] = $this->dbService->asyncQuery('SELECT 1');
        }

        $dbResults = \GuzzleHttp\Promise\Utils::unwrap($dbPromises);
        foreach ($dbResults as $result) {
            $this->assertNotNull($result);
        }
    }

    public function testServiceResilience(): void
    {
        // Test database resilience
        $this->dbService->disconnect();
        $this->assertTrue($this->dbService->checkHealth()['status'] === 'healthy');

        // Test cache resilience
        $this->cacheService->disconnect();
        $this->assertTrue($this->cacheService->checkHealth()['status'] === 'healthy');

        // Test mail resilience
        $this->mailService->disconnect();
        $this->assertTrue($this->mailService->checkHealth()['status'] === 'healthy');

        // Test MCP service resilience
        $this->mcpService->disconnect();
        $this->assertTrue($this->mcpService->checkHealth()['status'] === 'healthy');
    }

    public function testPerformance(): void
    {
        $startTime = microtime(true);

        // Test database performance
        $dbStart = microtime(true);
        $this->dbService->query('SELECT 1');
        $dbTime = microtime(true) - $dbStart;
        $this->assertLessThan(0.1, $dbTime);

        // Test cache performance
        $cacheStart = microtime(true);
        $this->cacheService->set('test_key', 'test_value');
        $this->cacheService->get('test_key');
        $cacheTime = microtime(true) - $cacheStart;
        $this->assertLessThan(0.05, $cacheTime);

        // Test MCP service performance
        $mcpStart = microtime(true);
        $this->mcpService->request('GET', '/health');
        $mcpTime = microtime(true) - $mcpStart;
        $this->assertLessThan(0.2, $mcpTime);

        // Test mail performance
        $mailStart = microtime(true);
        $this->mailService->send('test@service-learning.edu', 'Test', 'Test');
        $mailTime = microtime(true) - $mailStart;
        $this->assertLessThan(0.3, $mailTime);

        $totalTime = microtime(true) - $startTime;
        $this->assertLessThan(1.0, $totalTime);
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        $this->dbService->query('DELETE FROM users WHERE email LIKE ?', ['test@service-learning.edu']);
        $this->cacheService->delete('test_key');
        
        parent::tearDown();
    }
} 