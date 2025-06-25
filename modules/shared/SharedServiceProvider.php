<?php

namespace App\Modules\Shared;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class SharedServiceProvider extends ServiceProvider
{
    /**
     * The module name
     */
    protected string $moduleName = 'shared';

    /**
     * The module namespace
     */
    protected string $moduleNamespace = 'App\\Modules\\Shared';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register shared services
        $this->registerSharedServices();
        
        // Register utilities
        $this->registerUtilities();
        
        // Register monitoring services
        $this->registerMonitoringServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register shared middleware
        $this->registerMiddleware();
        
        // Register shared commands
        $this->registerCommands();
        
        // Register shared views
        $this->registerViews();
        
        Log::info('SharedServiceProvider booted successfully');
    }

    /**
     * Register shared services
     */
    protected function registerSharedServices(): void
    {
        $services = [
            'module.discovery' => ModuleDiscoveryService::class,
            'module.configuration' => ConfigurationService::class,
            'module.audit' => AuditService::class,
            'module.performance' => PerformanceOptimizationService::class,
            'module.monitoring' => MonitoringService::class,
        ];

        foreach ($services as $abstract => $concrete) {
            if (class_exists($concrete)) {
                $this->app->singleton($abstract, $concrete);
                Log::info("Shared service registered: {$abstract}");
            }
        }
    }

    /**
     * Register utilities
     */
    protected function registerUtilities(): void
    {
        // Register utility classes
        $utilities = [
            'module.registry' => ModuleRegistry::class,
            'module.validator' => ModuleValidator::class,
            'module.health' => ModuleHealthService::class,
        ];

        foreach ($utilities as $abstract => $concrete) {
            if (class_exists($concrete)) {
                $this->app->singleton($abstract, $concrete);
                Log::info("Utility registered: {$abstract}");
            }
        }
    }

    /**
     * Register monitoring services
     */
    protected function registerMonitoringServices(): void
    {
        // Register monitoring and performance services
        $monitoringServices = [
            'performance.optimizer' => PerformanceOptimizationService::class,
            'system.monitor' => MonitoringService::class,
        ];

        foreach ($monitoringServices as $abstract => $concrete) {
            if (class_exists($concrete)) {
                $this->app->singleton($abstract, $concrete);
                Log::info("Monitoring service registered: {$abstract}");
            }
        }
    }

    /**
     * Register middleware
     */
    protected function registerMiddleware(): void
    {
        // Register shared middleware if any
        $middlewarePath = __DIR__ . '/Middleware';
        if (is_dir($middlewarePath)) {
            $middlewareFiles = glob($middlewarePath . '/*.php');
            foreach ($middlewareFiles as $file) {
                $className = $this->getClassNameFromFile($file);
                if ($className) {
                    $this->app['router']->pushMiddlewareToGroup('web', $className);
                    Log::info("Shared middleware registered: {$className}");
                }
            }
        }
    }

    /**
     * Register commands
     */
    protected function registerCommands(): void
    {
        // Register shared commands if any
        $commandsPath = __DIR__ . '/Commands';
        if (is_dir($commandsPath)) {
            $commandFiles = glob($commandsPath . '/*.php');
            foreach ($commandFiles as $file) {
                $className = $this->getClassNameFromFile($file);
                if ($className && class_exists($className)) {
                    $this->commands[] = $className;
                    Log::info("Shared command registered: {$className}");
                }
            }
        }
    }

    /**
     * Register views
     */
    protected function registerViews(): void
    {
        // Register shared views if any
        $viewsPath = __DIR__ . '/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'shared');
            Log::info('Shared views registered');
        }
    }

    /**
     * Get class name from file
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        try {
            $content = file_get_contents($filePath);
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
                
                // Try to determine namespace
                if (preg_match('/namespace\s+([^;]+)/', $content, $namespaceMatches)) {
                    $namespace = trim($namespaceMatches[1]);
                    return $namespace . '\\' . $className;
                }
                
                return $className;
            }
        } catch (\Exception $e) {
            Log::error("Error reading class from file {$filePath}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'module.discovery',
            'module.configuration',
            'module.audit',
            'module.performance',
            'module.monitoring',
            'module.registry',
            'module.validator',
            'module.health',
            'performance.optimizer',
            'system.monitor',
        ];
    }
} 