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
            $config = config('docker', []);
            $config = is_array($config) ? $config : [];
            return new DockerManager($config);
        });

        $this->app->singleton(NetworkManager::class, function ($app) {
            $networks = config('docker.networks', []);
            $networks = is_array($networks) ? $networks : [];
            return new NetworkManager($networks);
        });

        $this->app->singleton(VolumeManager::class, function ($app) {
            $volumes = config('docker.volumes', []);
            $volumes = is_array($volumes) ? $volumes : [];
            return new VolumeManager($volumes);
        });

        $this->app->singleton(CodespaceConfigurationManager::class, function ($app) {
            $codespaces = config('codespaces', []);
            $codespaces = is_array($codespaces) ? $codespaces : [];
            return new CodespaceConfigurationManager($codespaces);
        });

        $this->app->singleton(CodespaceInfrastructureManager::class, function ($app) {
            return new CodespaceInfrastructureManager(
                $app->make(CodespaceConfigurationManager::class),
                $app->make(DockerManager::class),
                $app->make(NetworkManager::class)
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