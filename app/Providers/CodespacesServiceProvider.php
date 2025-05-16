<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CodespacesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Load Codespaces configuration
        $this->mergeConfigFrom(
            base_path('.codespaces/config/services.json'),
            'codespaces'
        );

        if (Config::get('codespaces.enabled')) {
            $this->configureServices();
            $this->configureLogging();
        }
    }

    public function boot()
    {
        if ($this->app->environment('local') && Config::get('codespaces.enabled', false)) {
            // Override database configuration
            Config::set('database.connections.mysql', [
                'driver' => 'mysql',
                'host' => Config::get('codespaces.services.mysql.host', 'localhost'),
                'port' => Config::get('codespaces.services.mysql.port', 3306),
                'database' => Config::get('codespaces.services.mysql.database', 'service_learning'),
                'username' => Config::get('codespaces.services.mysql.username', 'root'),
                'password' => Config::get('codespaces.services.mysql.password', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Override Redis configuration
            Config::set('database.redis.default', [
                'host' => Config::get('codespaces.services.redis.host', 'localhost'),
                'password' => Config::get('codespaces.services.redis.password', null),
                'port' => Config::get('codespaces.services.redis.port', 6379),
                'database' => 0,
            ]);

            // Configure logging
            Config::set('logging.channels.codespaces', [
                'driver' => 'daily',
                'path' => storage_path('logs/codespaces.log'),
                'level' => Config::get('codespaces.log_level', 'debug'),
                'days' => 14,
            ]);
        }

        if (Config::get('codespaces.enabled')) {
            $this->registerServiceHealthChecks();
        }
    }

    protected function configureServices()
    {
        // Configure services based on environment
        $services = Config::get('codespaces.services', []);
        foreach ($services as $service => $config) {
            Config::set("services.{$service}", $config);
        }
    }

    protected function configureLogging()
    {
        // Set up Codespaces-specific logging
        Log::channel('codespaces')->info('Codespaces environment initialized');
    }

    protected function registerServiceHealthChecks()
    {
        // Register service health check commands
        $this->app->singleton('codespaces.health', function ($app) {
            return new \App\Services\CodespacesHealthService();
        });

        // Schedule health checks
        if ($this->app->runningInConsole()) {
            $this->app->make('schedule')->command('codespaces:health-check')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        }
    }
}
