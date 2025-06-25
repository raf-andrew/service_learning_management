<?php

namespace App\Console\Commands\Auth;

class ManageAuthMiddlewareCommand extends BaseAuthCommand
{
    protected $signature = 'auth:middleware
        {action : The action to perform (list|register|unregister)}
        {--name= : Middleware name}
        {--class= : Middleware class}
        {--group= : Middleware group}';

    protected $description = 'Manage authentication middleware';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listMiddleware();
            case 'register':
                return $this->registerMiddleware();
            case 'unregister':
                return $this->unregisterMiddleware();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listMiddleware()
    {
        $group = $this->option('group');

        try {
            $middleware = $group 
                ? $this->authService->getMiddlewareByGroup($group)
                : $this->authService->getAllMiddleware();

            $this->table(
                ['Name', 'Class', 'Group', 'Priority'],
                $middleware->map(fn($mw) => [
                    $mw->name,
                    $mw->class,
                    $mw->group,
                    $mw->priority
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list middleware: {$e->getMessage()}");
            return 1;
        }
    }

    protected function registerMiddleware()
    {
        $name = $this->option('name');
        $class = $this->option('class');
        $group = $this->option('group');

        if (!$name || !$class) {
            $this->error('Middleware name and class are required');
            return 1;
        }

        try {
            $this->authService->registerMiddleware([
                'name' => $name,
                'class' => $class,
                'group' => $group
            ]);

            $this->info("Middleware registered successfully: {$name}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to register middleware: {$e->getMessage()}");
            return 1;
        }
    }

    protected function unregisterMiddleware()
    {
        $name = $this->option('name');
        if (!$name) {
            $this->error('Middleware name is required');
            return 1;
        }

        if ($this->confirm("Are you sure you want to unregister middleware {$name}?")) {
            try {
                $this->authService->unregisterMiddleware($name);
                $this->info("Middleware unregistered successfully: {$name}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to unregister middleware: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
} 