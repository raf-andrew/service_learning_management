<?php

namespace App\Providers;

use Dev\Brain\Memory\Memory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class MemoryServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->singleton(Memory::class, function ($app) {
            return new Memory(storage_path('app/memory'));
        });
    }

    public function boot()
    {
        // Register memory events
        Event::listen('memory.stored', function ($category, $data) {
            // Cache the latest memory entry
            Cache::tags(['memory', $category])->put(
                "latest:{$category}",
                $data,
                now()->addHours(24)
            );
        });

        // Register memory commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add memory-related commands here
            ]);
        }
    }
} 