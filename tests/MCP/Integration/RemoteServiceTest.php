<?php

namespace Tests\MCP\Integration;

use Tests\MCP\BaseTestCase;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;

class RemoteServiceTest extends BaseTestCase
{
    public function testDatabaseConnection(): void
    {
        $this->assertNotNull(DB::connection()->getPdo());
        $this->assertTrue(DB::connection()->getDatabaseName() === 'service_learning');
    }

    public function testRedisConnection(): void
    {
        $this->assertTrue(Redis::ping() === true);
        
        // Test Redis operations
        $key = 'test_key_' . uniqid();
        Redis::set($key, 'test_value');
        $this->assertEquals('test_value', Redis::get($key));
        
        Redis::del($key);
    }

    public function testMailConnection(): void
    {
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertTrue($transport->start());
        
        // Test mail sending
        Mail::raw('Test email', function($message) {
            $message->to('test@service-learning.edu')
                   ->subject('Test Subject');
        });
    }

    public function testMCPServiceConnection(): void
    {
        $client = $this->serviceManager->getConnection('mcp');
        $response = $client->get('/health');
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('healthy', $data['status']);
    }

    public function testServiceResilience(): void
    {
        // Test database resilience
        DB::disconnect();
        $this->assertNotNull(DB::connection()->getPdo());
        
        // Test Redis resilience
        Redis::disconnect();
        $this->assertTrue(Redis::ping() === true);
        
        // Test MCP service resilience
        $client = $this->serviceManager->getConnection('mcp');
        $response = $client->get('/health');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testConcurrentServiceAccess(): void
    {
        $promises = [];
        $client = $this->serviceManager->getConnection('mcp');
        
        // Test concurrent requests to MCP service
        for ($i = 0; $i < 5; $i++) {
            $promises[] = $client->getAsync('/health');
        }
        
        $results = \GuzzleHttp\Promise\Utils::unwrap($promises);
        foreach ($results as $response) {
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function testServiceErrorHandling(): void
    {
        $client = $this->serviceManager->getConnection('mcp');
        
        // Test invalid endpoint
        try {
            $client->get('/invalid-endpoint');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\GuzzleHttp\Exception\ClientException::class, $e);
        }
        
        // Test database error handling
        try {
            DB::statement('SELECT * FROM non_existent_table');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }
    }
} 