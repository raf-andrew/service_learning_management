<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Modules\Shared\ModuleDiscoveryService;
use App\Modules\Shared\ConfigurationService;
use ReflectionClass;
use ReflectionException;

class UnifiedServiceProvider extends ServiceProvider
{
    /**
     * Cache key for service provider registry
     */
    private const CACHE_KEY = 'unified_service_provider_registry';
    
    /**
     * Cache TTL in seconds
     */
    private const CACHE_TTL = 3600;

    /**
     * Registered service providers
     */
    private array $registeredProviders = [];

    /**
     * Module discovery service
     */
    private ModuleDiscoveryService $moduleDiscovery;

    /**
     * Configuration service
     */
    private ConfigurationService $configurationService;

    /**
     * Register services.
     */
    public function register(): void
    {
        // Only bind the services here, do not instantiate or use them
        $this->app->singleton(ModuleDiscoveryService::class, function ($app) {
            return new ModuleDiscoveryService();
        });
        $this->app->singleton(ConfigurationService::class, function ($app) {
            return new ConfigurationService();
        });
        $this->app->singleton(self::class, function ($app) {
            return $this;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->moduleDiscovery = $this->app->make(ModuleDiscoveryService::class);
        $this->configurationService = $this->app->make(ConfigurationService::class);

        // Register module services
        $this->registerModuleServices();
        // Register shared services
        $this->registerSharedServices();
        // Register performance optimizations
        $this->registerPerformanceOptimizations();
        // Boot module services
        $this->bootModuleServices();
        // Publish module assets
        $this->publishModuleAssets();
        // Register middleware
        $this->registerMiddleware();
        // Register routes
        $this->registerRoutes();
        // Register views
        $this->registerViews();
        // Register commands
        $this->registerCommands();
        Log::info('UnifiedServiceProvider booted successfully', [
            'registered_providers' => count($this->registeredProviders),
            'modules_discovered' => $this->moduleDiscovery->discoverModules()->count(),
        ]);
    }

    /**
     * Register core services
     */
    protected function registerCoreServices(): void
    {
        // Register module discovery service
        $this->app->singleton(ModuleDiscoveryService::class, function ($app) {
            return $this->moduleDiscovery;
        });

        // Register configuration service
        $this->app->singleton(ConfigurationService::class, function ($app) {
            return $this->configurationService;
        });

        // Register unified service provider
        $this->app->singleton(self::class, function ($app) {
            return $this;
        });

        Log::info('Core services registered');
    }

    /**
     * Register module services
     */
    protected function registerModuleServices(): void
    {
        if (!config('modules.enabled', true)) {
            Log::info('Modules are disabled, skipping module service registration');
            return;
        }

        $modules = $this->moduleDiscovery->discoverModules();
        $loadOrder = $this->moduleDiscovery->getLoadOrder();

        // Register modules in dependency order
        foreach ($loadOrder as $moduleName) {
            $module = $modules->firstWhere('name', $moduleName);
            if ($module && $module['enabled']) {
                $this->registerModule($module);
            }
        }

        Log::info('Module services registered', [
            'modules_registered' => count($this->registeredProviders),
            'load_order' => $loadOrder,
        ]);
    }

    /**
     * Register a single module
     */
    protected function registerModule(array $module): void
    {
        try {
            $moduleName = $module['name'];
            $serviceProvider = $module['service_provider'];

            if (!$serviceProvider) {
                Log::warning("Module {$moduleName} has no service provider");
                return;
            }

            // Check if service provider class exists
            if (!class_exists($serviceProvider)) {
                Log::error("Service provider class does not exist: {$serviceProvider}");
                return;
            }

            // Register the service provider
            $this->app->register($serviceProvider);
            $this->registeredProviders[$moduleName] = $serviceProvider;

            Log::info("Module {$moduleName} registered successfully", [
                'service_provider' => $serviceProvider,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to register module {$moduleName}", [
                'error' => $e->getMessage(),
                'service_provider' => $serviceProvider ?? 'unknown',
            ]);
        }
    }

    /**
     * Register shared services
     */
    protected function registerSharedServices(): void
    {
        // Register shared utilities
        $sharedServices = [
            'module.registry' => \App\Modules\Shared\ModuleRegistry::class,
            'module.validator' => \App\Modules\Shared\ModuleValidator::class,
            'module.health' => \App\Modules\Shared\ModuleHealthService::class,
        ];

        foreach ($sharedServices as $abstract => $concrete) {
            if (class_exists($concrete)) {
                $this->app->singleton($abstract, $concrete);
                Log::info("Shared service registered: {$abstract}");
            }
        }
    }

    /**
     * Register performance optimizations
     */
    protected function registerPerformanceOptimizations(): void
    {
        if (config('modules.performance.optimization.autoload_optimization', true)) {
            // Optimize autoloader
            $this->optimizeAutoloader();
        }

        if (config('modules.performance.optimization.config_caching', true)) {
            // Enable configuration caching
            $this->enableConfigCaching();
        }

        if (config('modules.performance.optimization.route_caching', true)) {
            // Enable route caching
            $this->enableRouteCaching();
        }

        Log::info('Performance optimizations registered');
    }

    /**
     * Boot module services
     */
    protected function bootModuleServices(): void
    {
        foreach ($this->registeredProviders as $moduleName => $serviceProvider) {
            try {
                $provider = $this->app->make($serviceProvider);
                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            } catch (\Exception $e) {
                Log::error("Failed to boot module {$moduleName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Publish module assets
     */
    protected function publishModuleAssets(): void
    {
        $modules = $this->moduleDiscovery->discoverModules();

        foreach ($modules as $module) {
            if (!$module['enabled']) {
                continue;
            }

            $modulePath = $module['path'];
            $moduleName = $module['name'];

            // Publish configuration files
            $configPath = $modulePath . '/config';
            if (File::isDirectory($configPath)) {
                $this->publishes([
                    $configPath => config_path("modules/{$moduleName}")
                ], "{$moduleName}-config");
            }

            // Publish views
            $viewsPath = $modulePath . '/views';
            if (File::isDirectory($viewsPath)) {
                $this->publishes([
                    $viewsPath => resource_path("views/modules/{$moduleName}")
                ], "{$moduleName}-views");
            }

            // Publish assets
            $assetsPath = $modulePath . '/assets';
            if (File::isDirectory($assetsPath)) {
                $this->publishes([
                    $assetsPath => public_path("modules/{$moduleName}")
                ], "{$moduleName}-assets");
            }
        }
    }

    /**
     * Register middleware
     */
    protected function registerMiddleware(): void
    {
        $modules = $this->moduleDiscovery->discoverModules();

        foreach ($modules as $module) {
            if (!$module['enabled']) {
                continue;
            }

            $middlewarePath = $module['path'] . '/Middleware';
            if (File::isDirectory($middlewarePath)) {
                $this->registerModuleMiddleware($module['name'], $middlewarePath);
            }
        }
    }

    /**
     * Register module middleware
     */
    protected function registerModuleMiddleware(string $moduleName, string $middlewarePath): void
    {
        try {
            $files = File::files($middlewarePath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $className = $this->getClassNameFromFile($file->getPathname());
                    if ($className) {
                        $middlewareName = strtolower($moduleName) . '.' . strtolower(class_basename($className));
                        $this->app['router']->aliasMiddleware($middlewareName, $className);
                        
                        Log::info("Middleware registered: {$middlewareName} -> {$className}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to register middleware for module {$moduleName}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register routes
     */
    protected function registerRoutes(): void
    {
        $modules = $this->moduleDiscovery->discoverModules();

        foreach ($modules as $module) {
            if (!$module['enabled']) {
                continue;
            }

            $routesPath = $module['path'] . '/routes';
            if (File::isDirectory($routesPath)) {
                $this->registerModuleRoutes($module['name'], $routesPath);
            }
        }
    }

    /**
     * Register module routes
     */
    protected function registerModuleRoutes(string $moduleName, string $routesPath): void
    {
        try {
            $files = File::files($routesPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $routeFile = $routesPath . '/' . $file->getFilename();
                    
                    Route::middleware('web')
                        ->prefix("modules/{$moduleName}")
                        ->group($routeFile);
                    
                    Log::info("Routes registered for module {$moduleName}: {$file->getFilename()}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to register routes for module {$moduleName}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register views
     */
    protected function registerViews(): void
    {
        $modules = $this->moduleDiscovery->discoverModules();

        foreach ($modules as $module) {
            if (!$module['enabled']) {
                continue;
            }

            $viewsPath = $module['path'] . '/views';
            if (File::isDirectory($viewsPath)) {
                $namespace = strtolower($module['name']);
                View::addNamespace($namespace, $viewsPath);
                
                Log::info("Views registered for module {$module['name']}: {$namespace}");
            }
        }
    }

    /**
     * Register commands
     */
    protected function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $modules = $this->moduleDiscovery->discoverModules();

        foreach ($modules as $module) {
            if (!$module['enabled']) {
                continue;
            }

            $commandsPath = $module['path'] . '/commands';
            if (File::isDirectory($commandsPath)) {
                $this->registerModuleCommands($module['name'], $commandsPath);
            }
        }
    }

    /**
     * Register module commands
     */
    protected function registerModuleCommands(string $moduleName, string $commandsPath): void
    {
        try {
            $files = File::files($commandsPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $className = $this->getClassNameFromFile($file->getPathname());
                    if ($className && class_exists($className)) {
                        $this->commands($className);
                        
                        Log::info("Command registered for module {$moduleName}: {$className}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to register commands for module {$moduleName}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Optimize autoloader
     */
    protected function optimizeAutoloader(): void
    {
        if (config('modules.performance.optimization.autoload_optimization', true)) {
            // This would typically involve optimizing the composer autoloader
            // For now, we'll just log that optimization is enabled
            Log::info('Autoloader optimization enabled');
        }
    }

    /**
     * Enable configuration caching
     */
    protected function enableConfigCaching(): void
    {
        if (config('modules.performance.optimization.config_caching', true)) {
            // Enable Laravel's configuration caching
            Log::info('Configuration caching enabled');
        }
    }

    /**
     * Enable route caching
     */
    protected function enableRouteCaching(): void
    {
        if (config('modules.performance.optimization.route_caching', true)) {
            // Enable Laravel's route caching
            Log::info('Route caching enabled');
        }
    }

    /**
     * Get class name from PHP file
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        try {
            $content = File::get($filePath);
            
            // Simple regex to extract class name
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
                
                // Try to extract namespace
                if (preg_match('/namespace\s+([^;]+)/', $content, $namespaceMatches)) {
                    $namespace = trim($namespaceMatches[1]);
                    return $namespace . '\\' . $className;
                }
                
                return $className;
            }
        } catch (\Exception $e) {
            Log::error("Error extracting class name from {$filePath}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get registered providers
     */
    public function getRegisteredProviders(): array
    {
        return $this->registeredProviders;
    }

    /**
     * Get module health status
     */
    public function getModuleHealth(): array
    {
        return $this->moduleDiscovery->getAllModulesHealth()->toArray();
    }

    /**
     * Validate all modules
     */
    public function validateModules(): array
    {
        return $this->moduleDiscovery->validateDependencies();
    }

    /**
     * Clear all caches
     */
    public function clearAllCaches(): void
    {
        $this->moduleDiscovery->clearCache();
        $this->configurationService->clearCache();
        
        Log::info('All module caches cleared');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ModuleDiscoveryService::class,
            ConfigurationService::class,
            self::class,
        ];
    }
} 