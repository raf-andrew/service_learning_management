<?php

namespace Tests\Feature\Services;

use App\Models\Route;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class RouteServiceTest extends TestCase
{
    use RefreshDatabase;

    private RouteService $routeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routeService = new RouteService();
    }

    public function test_can_find_route_by_path_and_method()
    {
        $route = Route::factory()->create([
            'path' => '/api/test',
            'method' => 'GET',
            'is_active' => true,
        ]);

        $foundRoute = $this->routeService->findRoute('/api/test', 'GET');

        $this->assertNotNull($foundRoute);
        $this->assertEquals($route->id, $foundRoute->id);
    }

    public function test_returns_null_for_nonexistent_route()
    {
        $foundRoute = $this->routeService->findRoute('/api/nonexistent', 'GET');

        $this->assertNull($foundRoute);
    }

    public function test_returns_null_for_inactive_route()
    {
        Route::factory()->create([
            'path' => '/api/test',
            'method' => 'GET',
            'is_active' => false,
        ]);

        $foundRoute = $this->routeService->findRoute('/api/test', 'GET');

        $this->assertNull($foundRoute);
    }

    public function test_caches_route_lookup()
    {
        $route = Route::factory()->create([
            'path' => '/api/test',
            'method' => 'GET',
            'is_active' => true,
        ]);

        $this->routeService->findRoute('/api/test', 'GET');

        $this->assertTrue(Cache::has("route:/api/test:GET"));
    }

    public function test_can_get_active_routes()
    {
        Route::factory()->count(3)->create(['is_active' => true]);
        Route::factory()->count(2)->create(['is_active' => false]);

        $activeRoutes = $this->routeService->getActiveRoutes();

        $this->assertCount(3, $activeRoutes);
        $this->assertTrue($activeRoutes->every(fn ($route) => $route->is_active));
    }

    public function test_can_create_route()
    {
        $routeData = [
            'path' => '/api/new',
            'method' => 'POST',
            'target_url' => 'http://example.com/api',
            'is_active' => true,
        ];

        $route = $this->routeService->createRoute($routeData);

        $this->assertDatabaseHas('routes', $routeData);
        $this->assertEquals($routeData['path'], $route->path);
    }

    public function test_can_update_route()
    {
        $route = Route::factory()->create();
        $updateData = [
            'path' => '/api/updated',
            'method' => 'PUT',
        ];

        $updatedRoute = $this->routeService->updateRoute($route, $updateData);

        $this->assertEquals($updateData['path'], $updatedRoute->path);
        $this->assertEquals($updateData['method'], $updatedRoute->method);
    }

    public function test_can_delete_route()
    {
        $route = Route::factory()->create();

        $deleted = $this->routeService->deleteRoute($route);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('routes', ['id' => $route->id]);
    }

    public function test_clears_cache_on_route_changes()
    {
        $route = Route::factory()->create([
            'path' => '/api/test',
            'method' => 'GET',
            'is_active' => true,
        ]);

        // Cache the route
        $this->routeService->findRoute('/api/test', 'GET');
        $this->assertTrue(Cache::has("route:/api/test:GET"));

        // Update the route
        $this->routeService->updateRoute($route, ['path' => '/api/updated']);

        // Cache should be cleared
        $this->assertFalse(Cache::has("route:/api/test:GET"));
    }

    public function test_forward_request_handles_successful_response()
    {
        $route = Route::factory()->create([
            'target_url' => 'http://example.com/api',
            'method' => 'GET',
        ]);

        $requestData = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => ['test' => 'data'],
        ];

        $response = $this->routeService->forwardRequest($route, $requestData);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('body', $response);
        $this->assertArrayHasKey('headers', $response);
    }

    public function test_forward_request_handles_failed_response()
    {
        $route = Route::factory()->create([
            'target_url' => 'http://nonexistent.example.com',
            'method' => 'GET',
        ]);

        $requestData = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => ['test' => 'data'],
        ];

        $response = $this->routeService->forwardRequest($route, $requestData);

        $this->assertIsArray($response);
        $this->assertEquals(500, $response['status']);
        $this->assertEquals(['error' => 'Service unavailable'], $response['body']);
    }
} 