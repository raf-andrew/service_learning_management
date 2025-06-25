<?php

namespace App\Console\Commands\Config;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageConfigModelsCommand extends Command
{
    protected $signature = 'config:models
        {action : Action to perform (list|show|add|remove|sync|show-config|validate)}
        {name? : Model name}';

    protected $description = 'Manage and inspect model configurations as defined in .config/models.php and .config/config.php';

    protected $configPaths = [
        'models' => '.config/models.php',
        'config' => '.config/config.php'
    ];

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $config = $this->getConfig();

        switch ($action) {
            case 'list':
                return $this->listModels($config);
            case 'show':
                return $this->showModel($config, $name);
            case 'add':
                return $this->addModel($config, $name);
            case 'remove':
                return $this->removeModel($config, $name);
            case 'sync':
                return $this->syncModels($config);
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

    protected function listModels($config)
    {
        $modelsPath = $config['models']['models']['path'] ?? null;
        $namespaces = $config['models']['models']['namespaces'] ?? [];
        
        $this->info('Registered Model Namespaces:');
        foreach ($namespaces as $ns) {
            $this->line('- ' . $ns);
        }
        
        $this->info('Models Path: ' . $modelsPath);
        if (File::exists($modelsPath)) {
            $files = File::files($modelsPath);
            $this->table(['Model File'], array_map(fn($f) => [$f->getFilename()], $files));
        }
        
        $this->info('Database Connections:');
        $connections = $config['models']['models']['connections'] ?? [];
        $this->table(['Connection', 'Driver'], collect($connections)->map(fn($v, $k) => [$k, $v['driver'] ?? 'N/A'])->toArray());
        
        $this->info('Cache Configuration:');
        $cacheConfig = $config['models']['models']['cache'] ?? [];
        $this->table(['Setting', 'Value'], collect($cacheConfig)->map(fn($v, $k) => [$k, $v])->toArray());
        
        return 0;
    }

    protected function showModel($config, $name)
    {
        $modelsPath = $config['models']['models']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a model name.');
            return 1;
        }
        $file = $modelsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Model not found: ' . $file);
            return 1;
        }
        $this->info('Model file: ' . $file);
        $this->line(File::get($file));
        return 0;
    }

    protected function addModel($config, $name)
    {
        $modelsPath = $config['models']['models']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a model name.');
            return 1;
        }
        $file = $modelsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (File::exists($file)) {
            $this->error('Model already exists: ' . $file);
            return 1;
        }
        $stub = "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass {$name} extends Model\n{\n    protected \$fillable = [\n        //\n    ];\n\n    protected \$casts = [\n        //\n    ];\n}\n";
        File::put($file, $stub);
        $this->info('Model created: ' . $file);
        return 0;
    }

    protected function removeModel($config, $name)
    {
        $modelsPath = $config['models']['models']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a model name.');
            return 1;
        }
        $file = $modelsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Model not found: ' . $file);
            return 1;
        }
        File::delete($file);
        $this->info('Model deleted: ' . $file);
        return 0;
    }

    protected function syncModels($config)
    {
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->info('Model configuration synchronized and cache cleared.');
        return 0;
    }

    protected function showConfig($config)
    {
        $this->line('Models Configuration:');
        $this->line(json_encode($config['models']['models'], JSON_PRETTY_PRINT));
        return 0;
    }

    protected function validateConfig($config)
    {
        $errors = [];
        if (empty($config['models']['models']['path'])) {
            $errors[] = 'Missing models path.';
        }
        if (empty($config['models']['models']['namespaces'])) {
            $errors[] = 'Missing models namespaces.';
        }
        if (empty($config['models']['models']['connections'])) {
            $errors[] = 'Missing database connections.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 1;
        }
        $this->info('Models configuration is valid.');
        return 0;
    }
} 