<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class DotFolderServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register utilities as singletons
        $this->registerUtilities();
    }

    public function boot()
    {
        // Load configurations
        $this->loadConfigurations();
        
        // Register paths
        $this->registerPaths();
    }

    protected function registerUtilities()
    {
        // Register model utility
        $this->app->singleton('dotfolder.model', function () {
            return new \App\Utilities\ModelUtility();
        });

        // Register repository utility
        $this->app->singleton('dotfolder.repository', function () {
            return new \App\Utilities\RepositoryUtility();
        });

        // Register command utility
        $this->app->singleton('dotfolder.command', function () {
            return new \App\Utilities\CommandUtility();
        });

        // Register job utility
        $this->app->singleton('dotfolder.job', function () {
            return new \App\Utilities\JobUtility();
        });
    }

    protected function loadConfigurations()
    {
        // Load base configuration
        $config = Config::get('.config.base') ?? [];
        
        // Load each folder's configuration
        if (!is_array($config) || empty($config)) {
            return;
        }
        foreach ($config as $folder => $settings) {
            if (isset($settings['path'])) {
                $configPath = $settings['path'] . '/config.php';
                if (file_exists($configPath)) {
                    $folderConfig = require $configPath;
                    Config::set(".config.dotfolders.{$folder}", $folderConfig);
                }
            }
        }
    }

    protected function registerPaths()
    {
        $config = Config::get('.config.base');
        
        // Create and register each folder path
        if (!is_array($config) || empty($config)) {
            return;
        }
        foreach ($config as $folder => $settings) {
            if (isset($settings['path'])) {
                if (!file_exists($settings['path'])) {
                    mkdir($settings['path'], 0755, true);
                }
                
                // Register folder utility
                $this->app->bind("dotfolder.{$folder}", function () use ($settings) {
                    return new \App\Utilities\DotFolderUtility(
                        $settings['path'],
                        $settings['namespace']
                    );
                });
            }
        }
    }
}
