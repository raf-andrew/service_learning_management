<?php

namespace App\Console\Commands\Config;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageConfigMiddlewareCommand extends Command
{
    protected $signature = 'config:middleware
        {action : Action to perform (list|show|add|remove|sync|show-config|validate)}
        {name? : Middleware name}';

    protected $description = 'Manage and inspect middleware configurations as defined in .config/middleware.php and .config/config.php';

    protected $configPaths = [
        'middleware' => '.config/middleware.php',
        'config' => '.config/config.php'
    ];

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $config = $this->getConfig();

        switch ($action) {
            case 'list':
                return $this->listMiddleware($config);
            case 'show':
                return $this->showMiddleware($config, $name);
            case 'add':
                return $this->addMiddleware($config, $name);
            case 'remove':
                return $this->removeMiddleware($config, $name);
            case 'sync':
                return $this->syncMiddleware($config);
            case 'show-config':
                return $this->showConfig($config);
            case 'validate':
                return $this->validateConfig($config);
            default:
                $this->error('Invalid action.');
                return 1;
        }
    }

    protected function getConfig()
    {
        $config = [];
        foreach ($this->configPaths as $key => $path) {
            if (!File::exists(base_path($path))) {
                $this->error('Config file not found: ' . $path);
                exit(1);
            }
            $config[$key] = include base_path($path);
        }
        return $config;
    }

    protected function listMiddleware($config)
    {
        $middlewarePath = $config['middleware']['middleware']['path'] ?? null;
        $namespaces = $config['middleware']['middleware']['namespaces'] ?? [];
        
        $this->info('Registered Middleware Namespaces:');
        foreach ($namespaces as $ns) {
            $this->line('- ' . $ns);
        }
        
        $this->info('Middleware Path: ' . $middlewarePath);
        if (File::exists($middlewarePath)) {
            $files = File::files($middlewarePath);
            $this->table(['Middleware File'], array_map(fn($f) => [$f->getFilename()], $files));
        }
        
        $this->info('Middleware Groups:');
        $groups = $config['middleware']['middleware']['groups'] ?? [];
        foreach ($groups as $group => $middlewares) {
            $this->line("\n{$group}:");
            foreach ($middlewares as $middleware) {
                $this->line('- ' . $middleware);
            }
        }
        
        $this->info('Cache Configuration:');
        $cacheConfig = $config['middleware']['middleware']['cache'] ?? [];
        $this->table(['Setting', 'Value'], collect($cacheConfig)->map(fn($v, $k) => [$k, $v])->toArray());
        
        return 0;
    }

    protected function showMiddleware($config, $name)
    {
        $middlewarePath = $config['middleware']['middleware']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a middleware name.');
            return 1;
        }
        $file = $middlewarePath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Middleware not found: ' . $file);
            return 1;
        }
        $this->info('Middleware file: ' . $file);
        $this->line(File::get($file));
        return 0;
    }

    protected function addMiddleware($config, $name)
    {
        $middlewarePath = $config['middleware']['middleware']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a middleware name.');
            return 1;
        }
        $file = $middlewarePath . DIRECTORY_SEPARATOR . $name . '.php';
        if (File::exists($file)) {
            $this->error('Middleware already exists: ' . $file);
            return 1;
        }
        $stub = "<?php\n\nnamespace App\\Http\\Middleware;\n\nuse Closure;\nuse Illuminate\\Http\\Request;\n\nclass {$name}\n{\n    public function handle(Request \$request, Closure \$next)\n    {\n        //\n        return \$next(\$request);\n    }\n}\n";
        File::put($file, $stub);
        $this->info('Middleware created: ' . $file);
        return 0;
    }

    protected function removeMiddleware($config, $name)
    {
        $middlewarePath = $config['middleware']['middleware']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a middleware name.');
            return 1;
        }
        $file = $middlewarePath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Middleware not found: ' . $file);
            return 1;
        }
        File::delete($file);
        $this->info('Middleware deleted: ' . $file);
        return 0;
    }

    protected function syncMiddleware($config)
    {
        $this->call('config:clear');
        $this->call('route:clear');
        $this->info('Middleware configuration synchronized and route cache cleared.');
        return 0;
    }

    protected function showConfig($config)
    {
        $this->line('Middleware Configuration:');
        $this->line(json_encode($config['middleware']['middleware'], JSON_PRETTY_PRINT));
        return 0;
    }

    protected function validateConfig($config)
    {
        $errors = [];
        if (empty($config['middleware']['middleware']['path'])) {
            $errors[] = 'Missing middleware path.';
        }
        if (empty($config['middleware']['middleware']['namespaces'])) {
            $errors[] = 'Missing middleware namespaces.';
        }
        if (empty($config['middleware']['middleware']['groups'])) {
            $errors[] = 'Missing middleware groups.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 1;
        }
        $this->info('Middleware configuration is valid.');
        return 0;
    }
} 