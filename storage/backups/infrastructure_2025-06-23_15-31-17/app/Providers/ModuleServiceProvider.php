<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Modules\Shared\ModuleDiscoveryService;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Only bind the discovery service here
        $this->app->singleton(ModuleDiscoveryService::class, function ($app) {
            return new ModuleDiscoveryService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('modules.enabled', true)) {
            $this->discoverAndRegisterModules();
            $this->publishModuleAssets();
        }
    }

    /**
     * Discover and register all modules
     */
    protected function discoverAndRegisterModules(): void
    {
        $discoveryService = $this->app->make(ModuleDiscoveryService::class);
        $modules = $discoveryService->discoverModules();

        foreach ($modules as $module) {
            $this->registerModule($module);
        }

        Log::info('Module discovery completed', [
            'modules_found' => $modules->count(),
            'modules' => $modules->pluck('name')->toArray()
        ]);
    }

    /**
     * Register a single module
     */
    protected function registerModule(array $module): void
    {
        if (!$module['enabled']) {
            Log::info("Module {$module['name']} is disabled, skipping registration");
            return;
        }

        if ($module['service_provider']) {
            try {
                $this->app->register($module['service_provider']);
                Log::info("Module {$module['name']} registered successfully", [
                    'service_provider' => $module['service_provider']
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to register module {$module['name']}", [
                    'error' => $e->getMessage(),
                    'service_provider' => $module['service_provider']
                ]);
            }
        } else {
            Log::warning("Module {$module['name']} has no service provider", [
                'path' => $module['path']
            ]);
        }
    }

    /**
     * Publish module assets
     */
    protected function publishModuleAssets(): void
    {
        $modulesPath = base_path('modules');
        
        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            
            // Publish config files
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
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ModuleDiscoveryService::class,
        ];
    }
} 