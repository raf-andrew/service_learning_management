<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Request;
use Psr\Log\LoggerInterface;

class RequestTest extends TestCase
{
    private Request $request;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $_GET = ['param' => 'value'];
        $_POST = ['data' => 'test'];
        $_COOKIE = ['session' => '123'];
        $_FILES = [
            'file' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/test.txt',
                'error' => 0,
                'size' => 1024
            ]
        ];
        
        $this->request = new Request($this->logger);
    }

    public function testRequestCanBeCreated(): void
    {
        $this->assertInstanceOf(Request::class, $this->request);
    }

    public function testRequestHasLogger(): void
    {
        $this->assertSame($this->logger, $this->request->getLogger());
    }

    public function testRequestCanGetMethod(): void
    {
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testRequestCanGetUri(): void
    {
        $this->assertEquals('/test', $this->request->getUri());
    }

    public function testRequestCanGetHost(): void
    {
        $this->assertEquals('example.com', $this->request->getHost());
    }

    public function testRequestCanGetUserAgent(): void
    {
        $this->assertEquals('Test User Agent', $this->request->getUserAgent());
    }

    public function testRequestCanGetIp(): void
    {
        $this->assertEquals('127.0.0.1', $this->request->getIp());
    }

    public function testRequestCanGetQueryParameters(): void
    {
        $this->assertEquals('value', $this->request->getQuery('param'));
        $this->assertEquals('default', $this->request->getQuery('nonexistent', 'default'));
    }

    public function testRequestCanGetPostParameters(): void
    {
        $this->assertEquals('test', $this->request->getPost('data'));
        $this->assertEquals('default', $this->request->getPost('nonexistent', 'default'));
    }

    public function testRequestCanGetCookie(): void
    {
        $this->assertEquals('123', $this->request->getCookie('session'));
        $this->assertEquals('default', $this->request->getCookie('nonexistent', 'default'));
    }

    public function testRequestCanGetFile(): void
    {
        $file = $this->request->getFile('file');
        
        $this->assertIsArray($file);
        $this->assertEquals('test.txt', $file['name']);
        $this->assertEquals('text/plain', $file['type']);
        $this->assertEquals('/tmp/test.txt', $file['tmp_name']);
        $this->assertEquals(0, $file['error']);
        $this->assertEquals(1024, $file['size']);
    }

    public function testRequestCanGetHeader(): void
    {
        $_SERVER['HTTP_X_TEST'] = 'test-value';
        
        $this->assertEquals('test-value', $this->request->getHeader('X-Test'));
        $this->assertEquals('default', $this->request->getHeader('X-Nonexistent', 'default'));
    }

    public function testRequestCanGetAllHeaders(): void
    {
        $_SERVER['HTTP_X_TEST1'] = 'value1';
        $_SERVER['HTTP_X_TEST2'] = 'value2';
        
        $headers = $this->request->getHeaders();
        
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('X-Test1', $headers);
        $this->assertArrayHasKey('X-Test2', $headers);
        $this->assertEquals('value1', $headers['X-Test1']);
        $this->assertEquals('value2', $headers['X-Test2']);
    }

    public function testRequestCanGetContentType(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        $this->assertEquals('application/json', $this->request->getContentType());
    }

    public function testRequestCanGetContentLength(): void
    {
        $_SERVER['CONTENT_LENGTH'] = '1024';
        
        $this->assertEquals(1024, $this->request->getContentLength());
    }

    public function testRequestCanGetRawBody(): void
    {
        $body = '{"test": "value"}';
        $this->request->setRawBody($body);
        
        $this->assertEquals($body, $this->request->getRawBody());
    }

    public function testRequestCanGetJsonBody(): void
    {
        $body = '{"test": "value"}';
        $this->request->setRawBody($body);
        
        $json = $this->request->getJsonBody();
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey('test', $json);
        $this->assertEquals('value', $json['test']);
    }

    public function testRequestCanGetJsonBodyWithInvalidJson(): void
    {
        $body = 'invalid-json';
        $this->request->setRawBody($body);
        
        $json = $this->request->getJsonBody();
        
        $this->assertNull($json);
    }

    public function testRequestCanGetAllParameters(): void
    {
        $params = $this->request->all();
        
        $this->assertIsArray($params);
        $this->assertArrayHasKey('param', $params);
        $this->assertArrayHasKey('data', $params);
        $this->assertEquals('value', $params['param']);
        $this->assertEquals('test', $params['data']);
    }

    public function testRequestCanCheckIfParameterExists(): void
    {
        $this->assertTrue($this->request->has('param'));
        $this->assertTrue($this->request->has('data'));
        $this->assertFalse($this->request->has('nonexistent'));
    }

    public function testRequestCanGetParameter(): void
    {
        $this->assertEquals('value', $this->request->get('param'));
        $this->assertEquals('test', $this->request->get('data'));
        $this->assertEquals('default', $this->request->get('nonexistent', 'default'));
    }
} 