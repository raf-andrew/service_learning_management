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
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 