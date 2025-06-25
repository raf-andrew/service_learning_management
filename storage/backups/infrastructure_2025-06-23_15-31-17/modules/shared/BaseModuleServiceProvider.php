<?php

namespace App\Modules\Shared;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * The module name
     */
    protected string $moduleName;

    /**
     * Configuration files to publish
     */
    protected array $configFiles = [];

    /**
     * Migration directories to load
     */
    protected array $migrations = [];

    /**
     * Route files to load
     */
    protected array $routes = [];

    /**
     * Service bindings
     */
    public array $bindings = [];

    /**
     * Service singletons
     */
    public array $singletons = [];

    /**
     * Middleware to register
     */
    public array $middleware = [];

    /**
     * Policies to register
     */
    public array $policies = [];

    /**
     * Event listeners to register
     */
    public array $listeners = [];

    /**
     * Commands to register
     */
    public array $commands = [];

    /**
     * Blade directives to register
     */
    public array $bladeDirectives = [];

    /**
     * Validation rules to register
     */
    public array $validationRules = [];

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfigurations();
        $this->registerBindings();
        $this->registerSingletons();
        $this->registerCommands();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrations();
        $this->loadRoutes();
        $this->registerMiddleware();
        $this->registerPolicies();
        $this->registerEventListeners();
        $this->registerBladeDirectives();
        $this->registerValidationRules();
        $this->publishAssets();
    }

    /**
     * Register module configurations
     */
    protected function registerConfigurations(): void
    {
        foreach ($this->configFiles as $configFile) {
            $this->mergeConfigFrom(
                $this->getModulePath("config/{$configFile}.php"),
                $configFile
            );
        }
    }

    /**
     * Register service bindings
     */
    protected function registerBindings(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Register service singletons
     */
    protected function registerSingletons(): void
    {
        foreach ($this->singletons as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }
    }

    /**
     * Load module migrations
     */
    protected function loadMigrations(): void
    {
        foreach ($this->migrations as $migrationPath) {
            $this->loadMigrationsFrom($this->getModulePath($migrationPath));
        }
    }

    /**
     * Load module routes
     */
    protected function loadRoutes(): void
    {
        foreach ($this->routes as $routeFile) {
            $this->loadRoutesFrom($this->getModulePath($routeFile));
        }
    }

    /**
     * Register middleware
     */
    protected function registerMiddleware(): void
    {
        foreach ($this->middleware as $alias => $middleware) {
            $this->app['router']->aliasMiddleware($alias, $middleware);
        }
    }

    /**
     * Register policies
     */
    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        foreach ($this->listeners as $event => $listeners) {
            if (is_array($listeners)) {
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            } else {
                Event::listen($event, $listeners);
            }
        }
    }

    /**
     * Register commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register blade directives
     */
    protected function registerBladeDirectives(): void
    {
        foreach ($this->bladeDirectives as $directive => $callback) {
            \Blade::directive($directive, $callback);
        }
    }

    /**
     * Register validation rules
     */
    protected function registerValidationRules(): void
    {
        foreach ($this->validationRules as $rule => $callback) {
            \Validator::extend($rule, $callback);
        }
    }

    /**
     * Publish module assets
     */
    protected function publishAssets(): void
    {
        // Publish configuration files
        foreach ($this->configFiles as $configFile) {
            $this->publishes([
                $this->getModulePath("config/{$configFile}.php") => config_path("{$configFile}.php"),
            ], "{$this->moduleName}-config");
        }

        // Publish migrations
        foreach ($this->migrations as $migrationPath) {
            $this->publishes([
                $this->getModulePath($migrationPath) => database_path('migrations'),
            ], "{$this->moduleName}-migrations");
        }
    }

    /**
     * Get the module path
     */
    protected function getModulePath(string $path = ''): string
    {
        return base_path("modules/{$this->moduleName}/{$path}");
    }

    /**
     * Get the module namespace
     */
    protected function getModuleNamespace(string $class = ''): string
    {
        return "App\\Modules\\" . ucfirst($this->moduleName) . "\\{$class}";
    }

    /**
     * Log module activity
     */
    protected function logModuleActivity(string $action, array $context = []): void
    {
        Log::info("Module {$this->moduleName}: {$action}", array_merge([
            'module' => $this->moduleName,
            'action' => $action,
        ], $context));
    }

    /**
     * Check if module is enabled
     */
    protected function isModuleEnabled(): bool
    {
        return config("modules.{$this->moduleName}.enabled", true);
    }

    /**
     * Get module configuration
     */
    protected function getModuleConfig(string $key, $default = null)
    {
        return config("modules.{$this->moduleName}.{$key}", $default);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return array_merge(
            array_keys($this->bindings),
            array_keys($this->singletons),
            $this->commands
        );
    }
} 