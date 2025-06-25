<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

trait ModuleTestTrait
{
    /**
     * Test modules that are loaded
     */
    protected array $loadedModules = [];

    /**
     * Load a module for testing
     */
    protected function loadModuleForTesting(string $moduleName): void
    {
        $modulePath = base_path("modules/{$moduleName}");
        
        if (!File::isDirectory($modulePath)) {
            $this->markTestSkipped("Module {$moduleName} not found");
            return;
        }

        $this->loadModuleServiceProvider($moduleName);
        $this->loadModuleRoutes($moduleName);
        $this->loadModuleConfig($moduleName);
        
        $this->loadedModules[] = $moduleName;
    }

    /**
     * Load module service provider
     */
    protected function loadModuleServiceProvider(string $moduleName): void
    {
        $providerPath = base_path("modules/{$moduleName}/Providers/{$moduleName}ServiceProvider.php");
        
        if (File::exists($providerPath)) {
            $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            $this->app->register($providerClass);
        }
    }

    /**
     * Load module routes
     */
    protected function loadModuleRoutes(string $moduleName): void
    {
        $routesPath = base_path("modules/{$moduleName}/routes");
        
        if (File::isDirectory($routesPath)) {
            $files = File::files($routesPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $routeFile = $file->getPathname();
                    Route::middleware('web')->group($routeFile);
                }
            }
        }
    }

    /**
     * Load module configuration
     */
    protected function loadModuleConfig(string $moduleName): void
    {
        $configPath = base_path("modules/{$moduleName}/config");
        
        if (File::isDirectory($configPath)) {
            $files = File::files($configPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $configKey = $file->getFilenameWithoutExtension();
                    $config = require $file->getPathname();
                    Config::set("modules.{$moduleName}.{$configKey}", $config);
                }
            }
        }
    }

    /**
     * Assert module is loaded
     */
    protected function assertModuleLoaded(string $moduleName): void
    {
        $this->assertContains($moduleName, $this->loadedModules, "Module {$moduleName} is not loaded");
    }

    /**
     * Assert module configuration exists
     */
    protected function assertModuleConfigExists(string $moduleName, string $configKey): void
    {
        $config = Config::get("modules.{$moduleName}.{$configKey}");
        $this->assertNotNull($config, "Module {$moduleName} configuration {$configKey} not found");
    }

    /**
     * Get module configuration
     */
    protected function getModuleConfig(string $moduleName, string $configKey = null)
    {
        if ($configKey === null) {
            return Config::get("modules.{$moduleName}");
        }
        
        return Config::get("modules.{$moduleName}.{$configKey}");
    }

    /**
     * Set module configuration
     */
    protected function setModuleConfig(string $moduleName, string $configKey, $value): void
    {
        Config::set("modules.{$moduleName}.{$configKey}", $value);
    }

    /**
     * Enable module for testing
     */
    protected function enableModule(string $moduleName): void
    {
        Config::set("modules.modules.{$moduleName}.enabled", true);
    }

    /**
     * Disable module for testing
     */
    protected function disableModule(string $moduleName): void
    {
        Config::set("modules.modules.{$moduleName}.enabled", false);
    }

    /**
     * Assert module is enabled
     */
    protected function assertModuleEnabled(string $moduleName): void
    {
        $enabled = Config::get("modules.modules.{$moduleName}.enabled", false);
        $this->assertTrue($enabled, "Module {$moduleName} is not enabled");
    }

    /**
     * Assert module is disabled
     */
    protected function assertModuleDisabled(string $moduleName): void
    {
        $enabled = Config::get("modules.modules.{$moduleName}.enabled", true);
        $this->assertFalse($enabled, "Module {$moduleName} is enabled");
    }

    /**
     * Test module service registration
     */
    protected function testModuleServiceRegistration(string $moduleName, array $services): void
    {
        foreach ($services as $abstract => $concrete) {
            $this->assertTrue(
                $this->app->bound($abstract),
                "Service {$abstract} is not registered for module {$moduleName}"
            );
        }
    }

    /**
     * Test module route registration
     */
    protected function testModuleRouteRegistration(string $moduleName, array $routes): void
    {
        foreach ($routes as $route) {
            $this->assertTrue(
                Route::has($route),
                "Route {$route} is not registered for module {$moduleName}"
            );
        }
    }

    /**
     * Test module middleware registration
     */
    protected function testModuleMiddlewareRegistration(string $moduleName, array $middleware): void
    {
        foreach ($middleware as $name => $class) {
            $this->assertTrue(
                $this->app['router']->hasMiddlewareGroup($name),
                "Middleware {$name} is not registered for module {$moduleName}"
            );
        }
    }

    /**
     * Clean up loaded modules
     */
    protected function cleanupLoadedModules(): void
    {
        foreach ($this->loadedModules as $moduleName) {
            $this->disableModule($moduleName);
        }
        
        $this->loadedModules = [];
    }
} 