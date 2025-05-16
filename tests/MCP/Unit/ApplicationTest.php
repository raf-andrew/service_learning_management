<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Application;
use Psr\Log\LoggerInterface;
use MCP\Configuration;
use MCP\ConnectionManager;
use MCP\Router;
use MCP\Request;
use MCP\Response;

class ApplicationTest extends TestCase
{
    private Application $app;
    private LoggerInterface $logger;
    private Configuration $config;
    private ConnectionManager $connectionManager;
    private Router $router;
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->config = new Configuration($this->logger);
        $this->connectionManager = new ConnectionManager($this->logger);
        $this->router = new Router($this->logger);
        $this->request = new Request($this->logger);
        $this->response = new Response($this->logger);
        
        $this->app = new Application(
            $this->logger,
            $this->config,
            $this->connectionManager,
            $this->router,
            $this->request,
            $this->response
        );
    }

    public function testApplicationCanBeCreated(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function testApplicationHasLogger(): void
    {
        $this->assertSame($this->logger, $this->app->getLogger());
    }

    public function testApplicationHasConfiguration(): void
    {
        $this->assertSame($this->config, $this->app->getConfig());
    }

    public function testApplicationHasConnectionManager(): void
    {
        $this->assertSame($this->connectionManager, $this->app->getConnectionManager());
    }

    public function testApplicationHasRouter(): void
    {
        $this->assertSame($this->router, $this->app->getRouter());
    }

    public function testApplicationHasRequest(): void
    {
        $this->assertSame($this->request, $this->app->getRequest());
    }

    public function testApplicationHasResponse(): void
    {
        $this->assertSame($this->response, $this->app->getResponse());
    }

    public function testApplicationCanRun(): void
    {
        $this->router->addRoute('GET', '/test', function () {
            return ['status' => 'success', 'message' => 'Test route executed'];
        });
        
        $this->request->setMethod('GET');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'success', 'message' => 'Test route executed']),
            $this->response->getContent()
        );
    }

    public function testApplicationCanHandleNotFound(): void
    {
        $this->request->setMethod('GET');
        $this->request->setUri('/nonexistent');
        
        $this->app->run();
        
        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleMethodNotAllowed(): void
    {
        $this->router->addRoute('GET', '/test', function () {
            return ['status' => 'success'];
        });
        
        $this->request->setMethod('POST');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(405, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleServerError(): void
    {
        $this->router->addRoute('GET', '/test', function () {
            throw new \Exception('Test error');
        });
        
        $this->request->setMethod('GET');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(500, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleValidationError(): void
    {
        $this->router->addRoute('POST', '/test', function () {
            throw new \MCP\ValidationException('Validation failed');
        });
        
        $this->request->setMethod('POST');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(422, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleAuthenticationError(): void
    {
        $this->router->addRoute('GET', '/test', function () {
            throw new \MCP\AuthenticationException('Authentication failed');
        });
        
        $this->request->setMethod('GET');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(401, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleAuthorizationError(): void
    {
        $this->router->addRoute('GET', '/test', function () {
            throw new \MCP\AuthorizationException('Authorization failed');
        });
        
        $this->request->setMethod('GET');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(403, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleRateLimitError(): void
    {
        $this->router->addRoute('GET', '/test', function () {
            throw new \MCP\RateLimitException('Rate limit exceeded');
        });
        
        $this->request->setMethod('GET');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(429, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleMaintenanceMode(): void
    {
        $this->config->set('app.maintenance', true);
        
        $this->request->setMethod('GET');
        $this->request->setUri('/test');
        
        $this->app->run();
        
        $this->assertEquals(503, $this->response->getStatusCode());
    }

    public function testApplicationCanHandleCors(): void
    {
        $this->config->set('app.cors.enabled', true);
        $this->config->set('app.cors.origins', ['http://example.com']);
        $this->config->set('app.cors.methods', ['GET', 'POST']);
        $this->config->set('app.cors.headers', ['Content-Type', 'Authorization']);
        
        $this->request->setMethod('OPTIONS');
        $this->request->setUri('/test');
        $this->request->setHeader('Origin', 'http://example.com');
        
        $this->app->run();
        
        $this->assertEquals(204, $this->response->getStatusCode());
        $this->assertEquals('http://example.com', $this->response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST', $this->response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type, Authorization', $this->response->getHeader('Access-Control-Allow-Headers'));
    }

    public function testApplicationCanHandleCorsWithInvalidOrigin(): void
    {
        $this->config->set('app.cors.enabled', true);
        $this->config->set('app.cors.origins', ['http://example.com']);
        
        $this->request->setMethod('OPTIONS');
        $this->request->setUri('/test');
        $this->request->setHeader('Origin', 'http://invalid.com');
        
        $this->app->run();
        
        $this->assertEquals(403, $this->response->getStatusCode());
    }
} 