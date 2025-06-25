<?php

namespace App\Console\Commands\Auth;

class ManageAuthRoutesCommand extends BaseAuthCommand
{
    protected $signature = 'auth:routes
        {action : The action to perform (list|register|unregister)}
        {--name= : Route name}
        {--uri= : Route URI}
        {--method= : HTTP method}
        {--controller= : Controller class}
        {--middleware= : Middleware group}';

    protected $description = 'Manage authentication routes';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listRoutes();
            case 'register':
                return $this->registerRoute();
            case 'unregister':
                return $this->unregisterRoute();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listRoutes()
    {
        $middleware = $this->option('middleware');

        try {
            $routes = $middleware 
                ? $this->authService->getRoutesByMiddleware($middleware)
                : $this->authService->getAllRoutes();

            $this->table(
                ['Name', 'URI', 'Method', 'Controller', 'Middleware'],
                $routes->map(fn($route) => [
                    $route->name,
                    $route->uri,
                    $route->method,
                    $route->controller,
                    implode(', ', $route->middleware)
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list routes: {$e->getMessage()}");
            return 1;
        }
    }

    protected function registerRoute()
    {
        $name = $this->option('name');
        $uri = $this->option('uri');
        $method = $this->option('method');
        $controller = $this->option('controller');
        $middleware = $this->option('middleware');

        if (!$name || !$uri || !$method || !$controller) {
            $this->error('Route name, URI, method, and controller are required');
            return 1;
        }

        try {
            $this->authService->registerRoute([
                'name' => $name,
                'uri' => $uri,
                'method' => $method,
                'controller' => $controller,
                'middleware' => $middleware ? explode(',', $middleware) : []
            ]);

            $this->info("Route registered successfully: {$name}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to register route: {$e->getMessage()}");
            return 1;
        }
    }

    protected function unregisterRoute()
    {
        $name = $this->option('name');
        if (!$name) {
            $this->error('Route name is required');
            return 1;
        }

        if ($this->confirm("Are you sure you want to unregister route {$name}?")) {
            try {
                $this->authService->unregisterRoute($name);
                $this->info("Route unregistered successfully: {$name}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to unregister route: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 