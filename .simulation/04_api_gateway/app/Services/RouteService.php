<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteService
{
    /**
     * Find a route by path and method
     *
     * @param string $path
     * @param string $method
     * @return Route|null
     */
    public function findRoute(string $path, string $method): ?Route
    {
        return Cache::remember(
            "route:{$path}:{$method}",
            now()->addMinutes(5),
            fn () => Route::where('path', $path)
                ->where('method', $method)
                ->where('is_active', true)
                ->first()
        );
    }

    /**
     * Forward a request to the target service
     *
     * @param Route $route
     * @param array $requestData
     * @return array
     */
    public function forwardRequest(Route $route, array $requestData): array
    {
        try {
            $response = Http::withHeaders($requestData['headers'] ?? [])
                ->timeout(30)
                ->send(
                    $route->method,
                    $route->target_url,
                    $requestData['body'] ?? []
                );

            return [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            Log::error('Request forwarding failed', [
                'route' => $route->path,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 500,
                'body' => ['error' => 'Service unavailable'],
                'headers' => [],
            ];
        }
    }

    /**
     * Get all active routes
     *
     * @return Collection
     */
    public function getActiveRoutes(): Collection
    {
        return Cache::remember(
            'active_routes',
            now()->addMinutes(5),
            fn () => Route::where('is_active', true)->get()
        );
    }

    /**
     * Create a new route
     *
     * @param array $data
     * @return Route
     */
    public function createRoute(array $data): Route
    {
        $route = Route::create($data);
        $this->clearRouteCache();
        return $route;
    }

    /**
     * Update an existing route
     *
     * @param Route $route
     * @param array $data
     * @return Route
     */
    public function updateRoute(Route $route, array $data): Route
    {
        $route->update($data);
        $this->clearRouteCache();
        return $route;
    }

    /**
     * Delete a route
     *
     * @param Route $route
     * @return bool
     */
    public function deleteRoute(Route $route): bool
    {
        $deleted = $route->delete();
        $this->clearRouteCache();
        return $deleted;
    }

    /**
     * Clear route cache
     *
     * @return void
     */
    private function clearRouteCache(): void
    {
        Cache::forget('active_routes');
        // Clear individual route caches
        Route::all()->each(function ($route) {
            Cache::forget("route:{$route->path}:{$route->method}");
        });
    }
} 