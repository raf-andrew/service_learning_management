<?php

namespace Tests\MCP\Security;

use Tests\MCP\BaseTestCase;
use MCP\Core\Services\MCPService;
use MCP\Core\Services\DatabaseService;
use MCP\Core\Services\CacheService;
use MCP\Core\Services\MailService;
use MCP\Core\Services\QueueService;
use MCP\Core\Services\StorageService;

class MCPSecurityTest extends BaseTestCase
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

    public function testAuthentication(): void
    {
        // Test MCP API authentication
        $response = $this->mcpService->request('GET', '/auth/test', [], [
            'Authorization' => 'Bearer invalid_token'
        ]);
        $this->assertEquals(401, $response->getStatusCode());

        // Test database authentication
        try {
            $this->dbService->connect([
                'username' => 'invalid',
                'password' => 'invalid'
            ]);
            $this->fail('Expected database authentication exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }

        // Test cache authentication
        try {
            $this->cacheService->connect([
                'password' => 'invalid'
            ]);
            $this->fail('Expected cache authentication exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Redis\Exception::class, $e);
        }
    }

    public function testAuthorization(): void
    {
        // Test MCP API authorization
        $response = $this->mcpService->request('GET', '/admin/users', [], [
            'Authorization' => 'Bearer user_token'
        ]);
        $this->assertEquals(403, $response->getStatusCode());

        // Test database authorization
        try {
            $this->dbService->query('GRANT ALL PRIVILEGES ON *.* TO \'user\'@\'%\'');
            $this->fail('Expected database authorization exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }

        // Test storage authorization
        try {
            $this->storageService->put('../../etc/passwd', 'test');
            $this->fail('Expected storage authorization exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemException::class, $e);
        }
    }

    public function testInputValidation(): void
    {
        // Test SQL injection prevention
        $sqlInjectionAttempts = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "' UNION SELECT * FROM users; --",
            "' OR 1=1; --",
            "admin' --"
        ];

        foreach ($sqlInjectionAttempts as $attempt) {
            try {
                $this->dbService->query("SELECT * FROM users WHERE username = '{$attempt}'");
                $this->fail('Expected SQL injection prevention');
            } catch (\Exception $e) {
                $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
            }
        }

        // Test XSS prevention
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '"><script>alert("xss")</script>',
            '"><img src=x onerror=alert("xss")>',
            '"><svg/onload=alert("xss")>',
            '"><iframe src=javascript:alert("xss")>'
        ];

        foreach ($xssAttempts as $attempt) {
            $response = $this->mcpService->request('POST', '/test', ['input' => $attempt]);
            $this->assertEquals(400, $response->getStatusCode());
        }

        // Test command injection prevention
        $commandInjectionAttempts = [
            '; rm -rf /',
            '& del /f /s /q C:\\',
            '| cat /etc/passwd',
            '`whoami`',
            '$(id)'
        ];

        foreach ($commandInjectionAttempts as $attempt) {
            $response = $this->mcpService->request('POST', '/test', ['command' => $attempt]);
            $this->assertEquals(400, $response->getStatusCode());
        }
    }

    public function testDataProtection(): void
    {
        // Test sensitive data encryption
        $sensitiveData = [
            'password' => 'test123',
            'credit_card' => '4111111111111111',
            'ssn' => '123-45-6789'
        ];

        $response = $this->mcpService->request('POST', '/test', $sensitiveData);
        $this->assertEquals(200, $response->getStatusCode());
        
        $storedData = $this->dbService->query("SELECT * FROM sensitive_data WHERE id = 1");
        $this->assertNotEquals($sensitiveData['password'], $storedData['password']);
        $this->assertNotEquals($sensitiveData['credit_card'], $storedData['credit_card']);
        $this->assertNotEquals($sensitiveData['ssn'], $storedData['ssn']);

        // Test data masking
        $maskedData = $this->mcpService->request('GET', '/test/1')->getBody();
        $this->assertStringNotContainsString($sensitiveData['credit_card'], $maskedData);
        $this->assertStringNotContainsString($sensitiveData['ssn'], $maskedData);
    }

    public function testRateLimiting(): void
    {
        // Test MCP API rate limiting
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->mcpService->request('GET', '/test');
        }

        $rateLimited = false;
        foreach ($responses as $response) {
            if ($response->getStatusCode() === 429) {
                $rateLimited = true;
                break;
            }
        }
        $this->assertTrue($rateLimited);

        // Test database rate limiting
        $queries = [];
        try {
            for ($i = 0; $i < 1000; $i++) {
                $queries[] = $this->dbService->query('SELECT 1');
            }
            $this->fail('Expected database rate limiting');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
        }

        // Test cache rate limiting
        $operations = [];
        try {
            for ($i = 0; $i < 10000; $i++) {
                $operations[] = $this->cacheService->set("key_{$i}", "value_{$i}");
            }
            $this->fail('Expected cache rate limiting');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Redis\Exception::class, $e);
        }
    }

    public function testSecureCommunication(): void
    {
        // Test HTTPS enforcement
        $response = $this->mcpService->request('GET', '/test', [], [
            'X-Forwarded-Proto' => 'http'
        ]);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertStringStartsWith('https://', $response->getHeader('Location')[0]);

        // Test secure headers
        $response = $this->mcpService->request('GET', '/test');
        $headers = $response->getHeaders();
        
        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertArrayHasKey('X-XSS-Protection', $headers);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);

        // Test secure cookies
        $response = $this->mcpService->request('POST', '/auth/login', [
            'username' => 'test',
            'password' => 'test'
        ]);
        
        $cookies = $response->getHeader('Set-Cookie');
        foreach ($cookies as $cookie) {
            $this->assertStringContainsString('Secure', $cookie);
            $this->assertStringContainsString('HttpOnly', $cookie);
            $this->assertStringContainsString('SameSite=Strict', $cookie);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        $this->dbService->query('DELETE FROM sensitive_data WHERE id = 1');
        $this->cacheService->delete('test_key');
        
        parent::tearDown();
    }
} 