<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Providers\CodespacesServiceProvider;
use Illuminate\Filesystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(CodespacesServiceProvider::class);
        $this->app->singleton('files', function ($app) {
            return new Filesystem();
        });

        // Register interfaces with their implementations
        $this->app->bind(
            \App\Contracts\Services\DeveloperCredentialServiceInterface::class,
            \App\Services\DeveloperCredentialService::class
        );

        $this->app->bind(
            \App\Contracts\Repositories\DeveloperCredentialRepositoryInterface::class,
            \App\Repositories\DeveloperCredentialRepository::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $requiredEnv = [
            'APP_KEY', 'APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'CACHE_DRIVER', 'QUEUE_CONNECTION',
            'REDIS_HOST', 'REDIS_PORT', 'REDIS_DB',
        ];
        $missing = [];
        foreach ($requiredEnv as $env) {
            if (env($env) === null || env($env) === '') {
                $missing[] = $env;
            }
        }
        if (!empty($missing)) {
            \Log::error('Missing required environment variables: ' . implode(', ', $missing));
            abort(500, 'Missing required environment variables: ' . implode(', ', $missing));
        }
    }
} 