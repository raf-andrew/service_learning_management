<?php

namespace App\Config;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Route;

class RouteConfig
{
    protected $routes = [];
    protected $cacheKey = 'api_gateway_routes';
    protected $cacheTtl = 3600; // 1 hour

    public function __construct()
    {
        $this->loadRoutes();
    }

    public function getRoute(string $path)
    {
        return $this->routes[$path] ?? null;
    }

    public function addRoute(string $path, array $config)
    {
        $this->validateRouteConfig($config);
        $this->routes[$path] = $config;
        $this->saveRoutes();
        return true;
    }

    public function removeRoute(string $path)
    {
        if (isset($this->routes[$path])) {
            unset($this->routes[$path]);
            $this->saveRoutes();
            return true;
        }
        return false;
    }

    public function updateRoute(string $path, array $config)
    {
        if (!isset($this->routes[$path])) {
            throw new \InvalidArgumentException("Route not found: {$path}");
        }

        $this->validateRouteConfig($config);
        $this->routes[$path] = array_merge($this->routes[$path], $config);
        $this->saveRoutes();
        return true;
    }

    public function getAllRoutes()
    {
        return $this->routes;
    }

    protected function loadRoutes()
    {
        $this->routes = Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return $this->loadRoutesFromDatabase();
        });
    }

    protected function saveRoutes()
    {
        Cache::put($this->cacheKey, $this->routes, $this->cacheTtl);
        $this->saveRoutesToDatabase();
    }

    protected function loadRoutesFromDatabase()
    {
        // TODO: Implement database loading
        return [];
    }

    protected function saveRoutesToDatabase()
    {
        // TODO: Implement database saving
    }

    protected function validateRouteConfig(array $config)
    {
        $required = ['target_url', 'methods'];
        $optional = ['cache_ttl', 'rate_limit', 'timeout', 'auth_required'];

        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!is_array($config['methods'])) {
            throw new \InvalidArgumentException('Methods must be an array');
        }

        if (isset($config['cache_ttl']) && !is_int($config['cache_ttl'])) {
            throw new \InvalidArgumentException('Cache TTL must be an integer');
        }

        if (isset($config['rate_limit']) && !is_int($config['rate_limit'])) {
            throw new \InvalidArgumentException('Rate limit must be an integer');
        }

        if (isset($config['timeout']) && !is_int($config['timeout'])) {
            throw new \InvalidArgumentException('Timeout must be an integer');
        }

        if (isset($config['auth_required']) && !is_bool($config['auth_required'])) {
            throw new \InvalidArgumentException('Auth required must be a boolean');
        }

        return true;
    }

    public function validateRoute(array $route)
    {
        $required = ['path', 'target', 'methods'];
        foreach ($required as $field) {
            if (!isset($route[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!is_array($route['methods'])) {
            throw new \InvalidArgumentException('Methods must be an array');
        }

        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        foreach ($route['methods'] as $method) {
            if (!in_array($method, $validMethods)) {
                throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
            }
        }

        if (isset($route['rate_limit']) && !is_numeric($route['rate_limit'])) {
            throw new \InvalidArgumentException('Rate limit must be numeric');
        }

        if (isset($route['cache_ttl']) && !is_numeric($route['cache_ttl'])) {
            throw new \InvalidArgumentException('Cache TTL must be numeric');
        }

        return true;
    }

    public function addRoute(array $route)
    {
        $this->validateRoute($route);

        Route::create($route);
        $this->invalidateCache();

        return true;
    }

    public function updateRoute(string $path, array $route)
    {
        $this->validateRoute($route);

        Route::where('path', $path)->update($route);
        $this->invalidateCache();

        return true;
    }

    public function deleteRoute(string $path)
    {
        Route::where('path', $path)->delete();
        $this->invalidateCache();

        return true;
    }

    protected function invalidateCache()
    {
        Cache::forget($this->cacheKey);
        $this->loadRoutes();
    }
} 