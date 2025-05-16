<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Router;
use Psr\Log\LoggerInterface;
use MCP\Controllers\Controller;

class RouterTest extends TestCase
{
    private Router $router;
    private LoggerInterface $logger;
    private Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->controller = MockFactory::createMockController();
        
        $this->router = new Router($this->logger);
    }

    public function testRouterCanBeCreated(): void
    {
        $this->assertInstanceOf(Router::class, $this->router);
    }

    public function testRouterHasLogger(): void
    {
        $this->assertSame($this->logger, $this->router->getLogger());
    }

    public function testRouterCanAddRoute(): void
    {
        $this->router->addRoute('GET', '/test', [$this->controller, 'testAction']);
        
        $this->assertTrue($this->router->hasRoute('GET', '/test'));
    }

    public function testRouterCanGetRoute(): void
    {
        $this->router->addRoute('GET', '/test', [$this->controller, 'testAction']);
        
        $route = $this->router->getRoute('GET', '/test');
        
        $this->assertIsArray($route);
        $this->assertArrayHasKey('handler', $route);
        $this->assertEquals([$this->controller, 'testAction'], $route['handler']);
    }

    public function testRouterCanHandleRoute(): void
    {
        $this->router->addRoute('GET', '/test', [$this->controller, 'testAction']);
        
        $result = $this->router->handle('GET', '/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithParameters(): void
    {
        $this->router->addRoute('GET', '/test/{id}', [$this->controller, 'testAction']);
        
        $result = $this->router->handle('GET', '/test/123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithQueryParameters(): void
    {
        $this->router->addRoute('GET', '/test', [$this->controller, 'testAction']);
        
        $result = $this->router->handle('GET', '/test?param=value');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithMiddleware(): void
    {
        $middleware = function ($request, $next) {
            $request['middleware'] = true;
            return $next($request);
        };
        
        $this->router->addRoute('GET', '/test', [$this->controller, 'testAction'], [$middleware]);
        
        $result = $this->router->handle('GET', '/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithMultipleMiddleware(): void
    {
        $middleware1 = function ($request, $next) {
            $request['middleware1'] = true;
            return $next($request);
        };
        
        $middleware2 = function ($request, $next) {
            $request['middleware2'] = true;
            return $next($request);
        };
        
        $this->router->addRoute('GET', '/test', [$this->controller, 'testAction'], [$middleware1, $middleware2]);
        
        $result = $this->router->handle('GET', '/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithGroupMiddleware(): void
    {
        $middleware = function ($request, $next) {
            $request['group_middleware'] = true;
            return $next($request);
        };
        
        $this->router->group(['middleware' => [$middleware]], function ($router) {
            $router->addRoute('GET', '/test', [$this->controller, 'testAction']);
        });
        
        $result = $this->router->handle('GET', '/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithPrefix(): void
    {
        $this->router->group(['prefix' => '/api'], function ($router) {
            $router->addRoute('GET', '/test', [$this->controller, 'testAction']);
        });
        
        $result = $this->router->handle('GET', '/api/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithNamespace(): void
    {
        $this->router->group(['namespace' => 'MCP\\Controllers'], function ($router) {
            $router->addRoute('GET', '/test', 'TestController@testAction');
        });
        
        $result = $this->router->handle('GET', '/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testRouterCanHandleRouteWithAllOptions(): void
    {
        $middleware = function ($request, $next) {
            $request['middleware'] = true;
            return $next($request);
        };
        
        $this->router->group([
            'prefix' => '/api',
            'namespace' => 'MCP\\Controllers',
            'middleware' => [$middleware]
        ], function ($router) {
            $router->addRoute('GET', '/test', 'TestController@testAction');
        });
        
        $result = $this->router->handle('GET', '/api/test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }
} 