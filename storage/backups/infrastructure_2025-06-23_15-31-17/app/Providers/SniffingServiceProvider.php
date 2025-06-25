<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\SniffCommand;
use App\Console\Commands\GenerateReportCommand;
use App\Console\Commands\ClearSniffingDataCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SniffingServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register commands
        $this->app->singleton(SniffCommand::class);
        $this->app->singleton(GenerateReportCommand::class);
        $this->app->singleton(ClearSniffingDataCommand::class);

        // Register database configuration
        $this->app->singleton('sniffing.db', function () {
            $databasePath = storage_path('database/sniffing.sqlite');
            if (!File::exists($databasePath)) {
                File::put($databasePath, '');
            }
            return DB::connection('sqlite');
        });

        // Register migrations
        $this->app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__ . '/../database/migrations');
        });

        // Configure database connection
        config(['database.connections.sqlite.database' => storage_path('database/sniffing.sqlite')]);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SniffCommand::class,
                GenerateReportCommand::class,
                ClearSniffingDataCommand::class,
            ]);
        }
    }
}
