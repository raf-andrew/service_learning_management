<?php

namespace Tests\Unit\Config;

use Tests\TestCase;
use App\Config\RouteConfig;
use App\Models\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Mockery;

class RouteConfigTest extends TestCase
{
    protected $config;
    protected $routes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routes = new Collection([
            new Route([
                'path' => '/api/users',
                'target' => 'http://users-service',
                'methods' => ['GET', 'POST'],
                'rate_limit' => 100,
                'cache_ttl' => 300,
                'auth_required' => true,
                'permissions' => ['users.read', 'users.write']
            ]),
            new Route([
                'path' => '/api/products',
                'target' => 'http://products-service',
                'methods' => ['GET'],
                'rate_limit' => 200,
                'cache_ttl' => 600,
                'auth_required' => false,
                'permissions' => []
            ])
        ]);

        $this->config = new RouteConfig();
    }

    public function test_loads_routes_from_cache()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('api_gateway_routes', 3600, Mockery::any())
            ->andReturn($this->routes->keyBy('path'));

        $this->config->loadRoutes();
    }

    public function test_gets_route_by_path_and_method()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($this->routes->keyBy('path'));

        $route = $this->config->getRoute('/api/users', 'GET');

        $this->assertNotNull($route);
        $this->assertEquals('http://users-service', $route['target']);
        $this->assertEquals(['GET', 'POST'], $route['methods']);
        $this->assertEquals(100, $route['rate_limit']);
        $this->assertEquals(300, $route['cache_ttl']);
        $this->assertTrue($route['auth_required']);
        $this->assertEquals(['users.read', 'users.write'], $route['permissions']);
    }

    public function test_returns_null_for_invalid_path()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($this->routes->keyBy('path'));

        $route = $this->config->getRoute('/api/invalid', 'GET');

        $this->assertNull($route);
    }

    public function test_returns_null_for_invalid_method()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($this->routes->keyBy('path'));

        $route = $this->config->getRoute('/api/users', 'PUT');

        $this->assertNull($route);
    }

    public function test_validates_route_data()
    {
        $validRoute = [
            'path' => '/api/test',
            'target' => 'http://test-service',
            'methods' => ['GET']
        ];

        $this->assertTrue($this->config->validateRoute($validRoute));
    }

    public function test_throws_exception_for_missing_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: target');

        $invalidRoute = [
            'path' => '/api/test',
            'methods' => ['GET']
        ];

        $this->config->validateRoute($invalidRoute);
    }

    public function test_throws_exception_for_invalid_methods()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method: INVALID');

        $invalidRoute = [
            'path' => '/api/test',
            'target' => 'http://test-service',
            'methods' => ['INVALID']
        ];

        $this->config->validateRoute($invalidRoute);
    }

    public function test_throws_exception_for_invalid_rate_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate limit must be numeric');

        $invalidRoute = [
            'path' => '/api/test',
            'target' => 'http://test-service',
            'methods' => ['GET'],
            'rate_limit' => 'invalid'
        ];

        $this->config->validateRoute($invalidRoute);
    }

    public function test_adds_new_route()
    {
        $newRoute = [
            'path' => '/api/test',
            'target' => 'http://test-service',
            'methods' => ['GET'],
            'rate_limit' => 100,
            'cache_ttl' => 300
        ];

        Route::shouldReceive('create')
            ->once()
            ->with($newRoute)
            ->andReturn(new Route($newRoute));

        Cache::shouldReceive('forget')
            ->once()
            ->with('api_gateway_routes');

        $this->assertTrue($this->config->addRoute($newRoute));
    }

    public function test_updates_existing_route()
    {
        $route = [
            'path' => '/api/test',
            'target' => 'http://test-service',
            'methods' => ['GET'],
            'rate_limit' => 200
        ];

        Route::shouldReceive('where')
            ->once()
            ->with('path', '/api/test')
            ->andReturnSelf();

        Route::shouldReceive('update')
            ->once()
            ->with($route);

        Cache::shouldReceive('forget')
            ->once()
            ->with('api_gateway_routes');

        $this->assertTrue($this->config->updateRoute('/api/test', $route));
    }

    public function test_deletes_route()
    {
        Route::shouldReceive('where')
            ->once()
            ->with('path', '/api/test')
            ->andReturnSelf();

        Route::shouldReceive('delete')
            ->once();

        Cache::shouldReceive('forget')
            ->once()
            ->with('api_gateway_routes');

        $this->assertTrue($this->config->deleteRoute('/api/test'));
    }

    public function test_adds_and_retrieves_route()
    {
        $path = '/api/test';
        $config = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET', 'POST'],
            'cache_ttl' => 300,
            'rate_limit' => 100,
            'timeout' => 30,
            'auth_required' => true
        ];

        $this->config->addRoute($path, $config);
        $retrievedRoute = $this->config->getRoute($path);

        $this->assertEquals($config, $retrievedRoute);
    }

    public function test_removes_route()
    {
        $path = '/api/test';
        $config = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET']
        ];

        $this->config->addRoute($path, $config);
        $this->assertTrue($this->config->removeRoute($path));
        $this->assertNull($this->config->getRoute($path));
    }

    public function test_updates_route()
    {
        $path = '/api/test';
        $initialConfig = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET']
        ];
        $updateConfig = [
            'cache_ttl' => 300,
            'rate_limit' => 100
        ];

        $this->config->addRoute($path, $initialConfig);
        $this->config->updateRoute($path, $updateConfig);

        $retrievedRoute = $this->config->getRoute($path);
        $this->assertEquals(
            array_merge($initialConfig, $updateConfig),
            $retrievedRoute
        );
    }

    public function test_throws_exception_for_invalid_route_config()
    {
        $path = '/api/test';
        $invalidConfig = [
            'target_url' => 'http://api.example.com'
            // Missing required 'methods' field
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: methods');

        $this->config->addRoute($path, $invalidConfig);
    }

    public function test_throws_exception_for_invalid_methods_type()
    {
        $path = '/api/test';
        $invalidConfig = [
            'target_url' => 'http://api.example.com',
            'methods' => 'GET' // Should be an array
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Methods must be an array');

        $this->config->addRoute($path, $invalidConfig);
    }

    public function test_throws_exception_for_invalid_cache_ttl()
    {
        $path = '/api/test';
        $invalidConfig = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET'],
            'cache_ttl' => '300' // Should be an integer
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache TTL must be an integer');

        $this->config->addRoute($path, $invalidConfig);
    }

    public function test_throws_exception_for_invalid_rate_limit()
    {
        $path = '/api/test';
        $invalidConfig = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET'],
            'rate_limit' => '100' // Should be an integer
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate limit must be an integer');

        $this->config->addRoute($path, $invalidConfig);
    }

    public function test_throws_exception_for_invalid_timeout()
    {
        $path = '/api/test';
        $invalidConfig = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET'],
            'timeout' => '30' // Should be an integer
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be an integer');

        $this->config->addRoute($path, $invalidConfig);
    }

    public function test_throws_exception_for_invalid_auth_required()
    {
        $path = '/api/test';
        $invalidConfig = [
            'target_url' => 'http://api.example.com',
            'methods' => ['GET'],
            'auth_required' => 'true' // Should be a boolean
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Auth required must be a boolean');

        $this->config->addRoute($path, $invalidConfig);
    }

    public function test_throws_exception_for_updating_nonexistent_route()
    {
        $path = '/api/test';
        $config = [
            'cache_ttl' => 300
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Route not found: {$path}");

        $this->config->updateRoute($path, $config);
    }

    public function test_returns_all_routes()
    {
        $routes = [
            '/api/test1' => [
                'target_url' => 'http://api.example.com/1',
                'methods' => ['GET']
            ],
            '/api/test2' => [
                'target_url' => 'http://api.example.com/2',
                'methods' => ['POST']
            ]
        ];

        foreach ($routes as $path => $config) {
            $this->config->addRoute($path, $config);
        }

        $allRoutes = $this->config->getAllRoutes();
        $this->assertEquals($routes, $allRoutes);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 