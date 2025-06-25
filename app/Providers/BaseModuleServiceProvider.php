<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * The module name
     */
    protected string $moduleName;

    /**
     * The module namespace
     */
    protected string $moduleNamespace;

    /**
     * Configuration files to publish
     */
    protected array $configFiles = [];

    /**
     * Migration files to load
     */
    protected array $migrations = [];

    /**
     * Route files to load
     */
    protected array $routes = [];

    /**
     * View paths to register
     */
    protected array $viewPaths = [];

    /**
     * Asset paths to publish
     */
    protected array $assetPaths = [];

    /**
     * Middleware to register
     */
    protected array $middleware = [];

    /**
     * Commands to register
     */
    protected array $commands = [];

    /**
     * Services to bind
     */
    protected array $bindings = [];

    /**
     * Singletons to register
     */
    protected array $singletons = [];

    /**
     * Module dependencies
     */
    protected array $dependencies = [];

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = '';

    /**
     * Whether the module is enabled
     */
    protected bool $enabled = true;

    /**
     * Register services.
     */
    public function register(): void
    {
        if (!$this->isModuleEnabled()) {
            $this->logModuleActivity('Module is disabled, skipping registration');
            return;
        }

        $this->validateDependencies();
        $this->registerBindings();
        $this->registerSingletons();
        $this->registerCommands();
        
        $this->logModuleActivity('Module registered successfully');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!$this->isModuleEnabled()) {
            return;
        }

        $this->loadMigrations();
        $this->loadRoutes();
        $this->loadViews();
        $this->publishAssets();
        $this->registerMiddleware();
        
        $this->logModuleActivity('Module booted successfully');
    }

    /**
     * Validate module dependencies
     */
    protected function validateDependencies(): void
    {
        foreach ($this->dependencies as $dependency) {
            if (!class_exists($dependency)) {
                throw new \RuntimeException(
                    "Module {$this->moduleName} requires dependency: {$dependency}"
                );
            }
        }
    }

    /**
     * Register bindings
     */
    protected function registerBindings(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            try {
                $this->app->bind($abstract, $concrete);
                $this->logModuleActivity("Registered binding: {$abstract}");
            } catch (\Exception $e) {
                $this->logModuleActivity("Failed to register binding {$abstract}: " . $e->getMessage(), ['error' => true]);
            }
        }
    }

    /**
     * Register singletons
     */
    protected function registerSingletons(): void
    {
        foreach ($this->singletons as $abstract => $concrete) {
            try {
                $this->app->singleton($abstract, $concrete);
                $this->logModuleActivity("Registered singleton: {$abstract}");
            } catch (\Exception $e) {
                $this->logModuleActivity("Failed to register singleton {$abstract}: " . $e->getMessage(), ['error' => true]);
            }
        }
    }

    /**
     * Register commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            foreach ($this->commands as $command) {
                try {
                    $this->commands($command);
                    $this->logModuleActivity("Registered command: {$command}");
                } catch (\Exception $e) {
                    $this->logModuleActivity("Failed to register command {$command}: " . $e->getMessage(), ['error' => true]);
                }
            }
        }
    }

    /**
     * Load migrations
     */
    protected function loadMigrations(): void
    {
        $migrationPath = $this->getModulePath('database/migrations');
        
        if (File::isDirectory($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
            $this->logModuleActivity("Loaded migrations from: {$migrationPath}");
        }
    }

    /**
     * Load routes
     */
    protected function loadRoutes(): void
    {
        foreach ($this->routes as $route) {
            $routePath = $this->getModulePath($route);
            
            if (File::exists($routePath)) {
                try {
                    Route::middleware('web')
                        ->group($routePath);
                    $this->logModuleActivity("Loaded routes from: {$routePath}");
                } catch (\Exception $e) {
                    $this->logModuleActivity("Failed to load routes from {$routePath}: " . $e->getMessage(), ['error' => true]);
                }
            }
        }
    }

    /**
     * Load views
     */
    protected function loadViews(): void
    {
        foreach ($this->viewPaths as $namespace => $path) {
            $viewPath = $this->getModulePath($path);
            
            if (File::isDirectory($viewPath)) {
                try {
                    View::addNamespace($namespace, $viewPath);
                    $this->logModuleActivity("Registered view namespace: {$namespace} -> {$viewPath}");
                } catch (\Exception $e) {
                    $this->logModuleActivity("Failed to register view namespace {$namespace}: " . $e->getMessage(), ['error' => true]);
                }
            }
        }
    }

    /**
     * Publish assets
     */
    protected function publishAssets(): void
    {
        // Publish configuration files
        foreach ($this->configFiles as $configFile) {
            $configPath = $this->getModulePath("config/{$configFile}.php");
            
            if (File::exists($configPath)) {
                $this->publishes([
                    $configPath => config_path("modules/{$this->moduleName}/{$configFile}.php")
                ], "{$this->moduleName}-config");
                
                $this->logModuleActivity("Published config: {$configFile}");
            }
        }

        // Publish asset files
        foreach ($this->assetPaths as $source => $destination) {
            $assetPath = $this->getModulePath($source);
            
            if (File::isDirectory($assetPath)) {
                $this->publishes([
                    $assetPath => $destination
                ], "{$this->moduleName}-assets");
                
                $this->logModuleActivity("Published assets: {$source} -> {$destination}");
            }
        }
    }

    /**
     * Register middleware
     */
    protected function registerMiddleware(): void
    {
        foreach ($this->middleware as $name => $class) {
            try {
                $this->app['router']->aliasMiddleware($name, $class);
                $this->logModuleActivity("Registered middleware: {$name} -> {$class}");
            } catch (\Exception $e) {
                $this->logModuleActivity("Failed to register middleware {$name}: " . $e->getMessage(), ['error' => true]);
            }
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
     * Check if module is enabled
     */
    protected function isModuleEnabled(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return config("modules.modules.{$this->moduleName}.enabled", true);
    }

    /**
     * Get module configuration
     */
    protected function getModuleConfig(string $key = null, $default = null)
    {
        $config = config("modules.modules.{$this->moduleName}", []);
        
        if ($key === null) {
            return $config;
        }
        
        return data_get($config, $key, $default);
    }

    /**
     * Get cached module configuration
     */
    protected function getCachedModuleConfig(string $key = null, $default = null)
    {
        $cacheKey = "module_config_{$this->moduleName}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            return $this->getModuleConfig($key, $default);
        });
    }

    /**
     * Log module activity
     */
    protected function logModuleActivity(string $message, array $context = []): void
    {
        $logContext = array_merge([
            'module' => $this->moduleName,
            'namespace' => $this->moduleNamespace,
            'version' => $this->version,
        ], $context);

        if (isset($context['error']) && $context['error']) {
            Log::error("Module {$this->moduleName}: {$message}", $logContext);
        } else {
            Log::info("Module {$this->moduleName}: {$message}", $logContext);
        }
    }

    /**
     * Get module metadata
     */
    public function getModuleMetadata(): array
    {
        return [
            'name' => $this->moduleName,
            'namespace' => $this->moduleNamespace,
            'version' => $this->version,
            'description' => $this->description,
            'enabled' => $this->enabled,
            'dependencies' => $this->dependencies,
            'path' => $this->getModulePath(),
        ];
    }

    /**
     * Validate module structure
     */
    public function validateModuleStructure(): array
    {
        $issues = [];
        $modulePath = $this->getModulePath();

        // Check if module directory exists
        if (!File::isDirectory($modulePath)) {
            $issues[] = "Module directory does not exist: {$modulePath}";
        }

        // Check for required files
        $requiredFiles = [
            'config' => 'config/',
            'routes' => 'routes/',
            'views' => 'views/',
        ];

        foreach ($requiredFiles as $type => $path) {
            $fullPath = $this->getModulePath($path);
            if (!File::isDirectory($fullPath)) {
                $issues[] = "Missing {$type} directory: {$fullPath}";
            }
        }

        return $issues;
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