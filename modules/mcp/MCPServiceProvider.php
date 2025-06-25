<?php namespace Modules\MCP;

use Illuminate\Support\ServiceProvider;
use Modules\MCP\Services\MCPConnectionService;

class MCPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MCPConnectionService::class, function ($app) {
            return new MCPConnectionService();
        });
    }
    public function boot(): void
    {
        \Log::info('MCP module booted');
    }
}
