<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Response;
use Psr\Log\LoggerInterface;

class ResponseTest extends TestCase
{
    private Response $response;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->response = new Response($this->logger);
    }

    public function testResponseCanBeCreated(): void
    {
        $this->assertInstanceOf(Response::class, $this->response);
    }

    public function testResponseHasLogger(): void
    {
        $this->assertSame($this->logger, $this->response->getLogger());
    }

    public function testResponseCanSetStatusCode(): void
    {
        $this->response->setStatusCode(200);
        
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testResponseCanSetContent(): void
    {
        $content = 'Test content';
        $this->response->setContent($content);
        
        $this->assertEquals($content, $this->response->getContent());
    }

    public function testResponseCanSetJsonContent(): void
    {
        $data = ['test' => 'value'];
        $this->response->setJsonContent($data);
        
        $this->assertEquals(json_encode($data), $this->response->getContent());
        $this->assertEquals('application/json', $this->response->getHeader('Content-Type'));
    }

    public function testResponseCanSetHeader(): void
    {
        $this->response->setHeader('X-Test', 'value');
        
        $this->assertEquals('value', $this->response->getHeader('X-Test'));
    }

    public function testResponseCanSetMultipleHeaders(): void
    {
        $headers = [
            'X-Test1' => 'value1',
            'X-Test2' => 'value2'
        ];
        
        $this->response->setHeaders($headers);
        
        $this->assertEquals('value1', $this->response->getHeader('X-Test1'));
        $this->assertEquals('value2', $this->response->getHeader('X-Test2'));
    }

    public function testResponseCanSetCookie(): void
    {
        $this->response->setCookie('test', 'value', 3600, '/', 'example.com', true, true);
        
        $cookies = $this->response->getCookies();
        
        $this->assertIsArray($cookies);
        $this->assertArrayHasKey('test', $cookies);
        $this->assertEquals('value', $cookies['test']['value']);
        $this->assertEquals(3600, $cookies['test']['expire']);
        $this->assertEquals('/', $cookies['test']['path']);
        $this->assertEquals('example.com', $cookies['test']['domain']);
        $this->assertTrue($cookies['test']['secure']);
        $this->assertTrue($cookies['test']['httponly']);
    }

    public function testResponseCanSetRedirect(): void
    {
        $this->response->setRedirect('/test', 302);
        
        $this->assertEquals(302, $this->response->getStatusCode());
        $this->assertEquals('/test', $this->response->getHeader('Location'));
    }

    public function testResponseCanSetNotFound(): void
    {
        $this->response->setNotFound();
        
        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testResponseCanSetForbidden(): void
    {
        $this->response->setForbidden();
        
        $this->assertEquals(403, $this->response->getStatusCode());
    }

    public function testResponseCanSetServerError(): void
    {
        $this->response->setServerError();
        
        $this->assertEquals(500, $this->response->getStatusCode());
    }

    public function testResponseCanSetBadRequest(): void
    {
        $this->response->setBadRequest();
        
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    public function testResponseCanSetCreated(): void
    {
        $this->response->setCreated();
        
        $this->assertEquals(201, $this->response->getStatusCode());
    }

    public function testResponseCanSetNoContent(): void
    {
        $this->response->setNoContent();
        
        $this->assertEquals(204, $this->response->getStatusCode());
    }

    public function testResponseCanSetNotModified(): void
    {
        $this->response->setNotModified();
        
        $this->assertEquals(304, $this->response->getStatusCode());
    }

    public function testResponseCanSetUnauthorized(): void
    {
        $this->response->setUnauthorized();
        
        $this->assertEquals(401, $this->response->getStatusCode());
    }

    public function testResponseCanSetMethodNotAllowed(): void
    {
        $this->response->setMethodNotAllowed();
        
        $this->assertEquals(405, $this->response->getStatusCode());
    }

    public function testResponseCanSetConflict(): void
    {
        $this->response->setConflict();
        
        $this->assertEquals(409, $this->response->getStatusCode());
    }

    public function testResponseCanSetGone(): void
    {
        $this->response->setGone();
        
        $this->assertEquals(410, $this->response->getStatusCode());
    }

    public function testResponseCanSetUnsupportedMediaType(): void
    {
        $this->response->setUnsupportedMediaType();
        
        $this->assertEquals(415, $this->response->getStatusCode());
    }

    public function testResponseCanSetUnprocessableEntity(): void
    {
        $this->response->setUnprocessableEntity();
        
        $this->assertEquals(422, $this->response->getStatusCode());
    }

    public function testResponseCanSetTooManyRequests(): void
    {
        $this->response->setTooManyRequests();
        
        $this->assertEquals(429, $this->response->getStatusCode());
    }

    public function testResponseCanSetInternalServerError(): void
    {
        $this->response->setInternalServerError();
        
        $this->assertEquals(500, $this->response->getStatusCode());
    }

    public function testResponseCanSetNotImplemented(): void
    {
        $this->response->setNotImplemented();
        
        $this->assertEquals(501, $this->response->getStatusCode());
    }

    public function testResponseCanSetServiceUnavailable(): void
    {
        $this->response->setServiceUnavailable();
        
        $this->assertEquals(503, $this->response->getStatusCode());
    }

    public function testResponseCanSetGatewayTimeout(): void
    {
        $this->response->setGatewayTimeout();
        
        $this->assertEquals(504, $this->response->getStatusCode());
    }

    public function testResponseCanSetHttpVersion(): void
    {
        $this->response->setHttpVersion('1.1');
        
        $this->assertEquals('1.1', $this->response->getHttpVersion());
    }

    public function testResponseCanSetCharset(): void
    {
        $this->response->setCharset('UTF-8');
        
        $this->assertEquals('UTF-8', $this->response->getCharset());
    }

    public function testResponseCanSetContentType(): void
    {
        $this->response->setContentType('application/json');
        
        $this->assertEquals('application/json', $this->response->getContentType());
    }

    public function testResponseCanSetContentLength(): void
    {
        $this->response->setContentLength(1024);
        
        $this->assertEquals(1024, $this->response->getContentLength());
    }

    public function testResponseCanSetLastModified(): void
    {
        $date = new \DateTime();
        $this->response->setLastModified($date);
        
        $this->assertEquals($date->format('D, d M Y H:i:s') . ' GMT', $this->response->getHeader('Last-Modified'));
    }

    public function testResponseCanSetEtag(): void
    {
        $this->response->setEtag('test-etag');
        
        $this->assertEquals('"test-etag"', $this->response->getHeader('ETag'));
    }

    public function testResponseCanSetCacheControl(): void
    {
        $this->response->setCacheControl('no-cache');
        
        $this->assertEquals('no-cache', $this->response->getHeader('Cache-Control'));
    }

    public function testResponseCanSetExpires(): void
    {
        $date = new \DateTime();
        $this->response->setExpires($date);
        
        $this->assertEquals($date->format('D, d M Y H:i:s') . ' GMT', $this->response->getHeader('Expires'));
    }

    public function testResponseCanSetVary(): void
    {
        $this->response->setVary('Accept-Encoding');
        
        $this->assertEquals('Accept-Encoding', $this->response->getHeader('Vary'));
    }

    public function testResponseCanSetAllow(): void
    {
        $this->response->setAllow(['GET', 'POST']);
        
        $this->assertEquals('GET, POST', $this->response->getHeader('Allow'));
    }

    public function testResponseCanSetContentDisposition(): void
    {
        $this->response->setContentDisposition('attachment', 'test.txt');
        
        $this->assertEquals('attachment; filename="test.txt"', $this->response->getHeader('Content-Disposition'));
    }
} 