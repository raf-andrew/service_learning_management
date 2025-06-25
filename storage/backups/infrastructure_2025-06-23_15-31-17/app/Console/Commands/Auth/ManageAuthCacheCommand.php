<?php

namespace App\Console\Commands\Auth;

class ManageAuthCacheCommand extends BaseAuthCommand
{
    protected $signature = 'auth:cache
        {action : The action to perform (clear|warm|status)}
        {--tag= : Cache tag}
        {--key= : Cache key}
        {--all : Apply to all cache}';

    protected $description = 'Manage authentication cache';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                return $this->clearCache();
            case 'warm':
                return $this->warmCache();
            case 'status':
                return $this->cacheStatus();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function clearCache()
    {
        $tag = $this->option('tag');
        $key = $this->option('key');
        $all = $this->option('all');

        if (!$tag && !$key && !$all) {
            $this->error('Either tag, key, or --all option is required');
            return 1;
        }

        try {
            if ($all) {
                $this->authService->clearAllCache();
                $this->info('All cache cleared successfully');
            } elseif ($tag) {
                $this->authService->clearCacheByTag($tag);
                $this->info("Cache cleared successfully for tag: {$tag}");
            } else {
                $this->authService->clearCacheByKey($key);
                $this->info("Cache cleared successfully for key: {$key}");
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: {$e->getMessage()}");
            return 1;
        }
    }

    protected function warmCache()
    {
        $tag = $this->option('tag');
        $all = $this->option('all');

        if (!$tag && !$all) {
            $this->error('Either tag or --all option is required');
            return 1;
        }

        try {
            if ($all) {
                $this->authService->warmAllCache();
                $this->info('All cache warmed successfully');
            } else {
                $this->authService->warmCacheByTag($tag);
                $this->info("Cache warmed successfully for tag: {$tag}");
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to warm cache: {$e->getMessage()}");
            return 1;
        }
    }

    protected function cacheStatus()
    {
        $tag = $this->option('tag');
        $key = $this->option('key');

        try {
            if ($tag) {
                $status = $this->authService->getCacheStatusByTag($tag);
                $this->info("Cache status for tag {$tag}:");
            } elseif ($key) {
                $status = $this->authService->getCacheStatusByKey($key);
                $this->info("Cache status for key {$key}:");
            } else {
                $status = $this->authService->getAllCacheStatus();
                $this->info('Overall cache status:');
            }

            $this->table(
                ['Metric', 'Value'],
                collect($status)->map(fn($value, $key) => [
                    $key,
                    $value
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to get cache status: {$e->getMessage()}");
            return 1;
        }
    }
} 