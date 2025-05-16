<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CodespaceService;
use App\Services\DeveloperCredentialService;

class CodespaceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CodespaceService::class, function ($app) {
            return new CodespaceService(
                $app->make(DeveloperCredentialService::class)
            );
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/codespaces.php', 'codespaces'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/codespaces.php' => config_path('codespaces.php'),
        ], 'codespaces-config');

        $this->loadRoutesFrom(__DIR__.'/../../routes/codespaces.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CodespaceCommand::class,
            ]);
        }
    }
} 