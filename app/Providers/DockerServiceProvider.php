<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use App\Services\CodespaceConfigurationManager;
use App\Services\CodespaceInfrastructureManager;

class DockerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DockerManager::class, function ($app) {
            return new DockerManager(config('docker', []));
        });

        $this->app->singleton(NetworkManager::class, function ($app) {
            return new NetworkManager(config('docker.networks', []));
        });

        $this->app->singleton(VolumeManager::class, function ($app) {
            return new VolumeManager(config('docker.volumes', []));
        });

        $this->app->singleton(CodespaceConfigurationManager::class, function ($app) {
            return new CodespaceConfigurationManager(config('codespaces', []));
        });

        $this->app->singleton(CodespaceInfrastructureManager::class, function ($app) {
            return new CodespaceInfrastructureManager(
                $app->make(DockerManager::class),
                $app->make(NetworkManager::class),
                $app->make(VolumeManager::class),
                config('codespaces', [])
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/docker.php' => config_path('docker.php'),
            __DIR__.'/../../config/codespaces.php' => config_path('codespaces.php'),
        ], 'docker-config');

        $this->mergeConfigFrom(
            __DIR__.'/../../config/docker.php', 'docker'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/codespaces.php', 'codespaces'
        );
    }
} 