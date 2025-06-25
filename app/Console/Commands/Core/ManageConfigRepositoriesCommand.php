<?php

namespace App\Console\Commands\Config;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageConfigRepositoriesCommand extends Command
{
    protected $signature = 'config:repositories
        {action : Action to perform (list|show|add|remove|sync|show-config|validate)}
        {name? : Repository name}';

    protected $description = 'Manage and inspect repository configurations as defined in .config/models.php and .config/config.php';

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
                return $this->listRepositories($config);
            case 'show':
                return $this->showRepository($config, $name);
            case 'add':
                return $this->addRepository($config, $name);
            case 'remove':
                return $this->removeRepository($config, $name);
            case 'sync':
                return $this->syncRepositories($config);
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

    protected function listRepositories($config)
    {
        $reposPath = $config['models']['repositories']['path'] ?? null;
        $namespaces = $config['models']['repositories']['namespaces'] ?? [];
        
        $this->info('Registered Repository Namespaces:');
        foreach ($namespaces as $ns) {
            $this->line('- ' . $ns);
        }
        
        $this->info('Repositories Path: ' . $reposPath);
        if (File::exists($reposPath)) {
            $files = File::files($reposPath);
            $this->table(['Repository File'], array_map(fn($f) => [$f->getFilename()], $files));
        }
        
        $this->info('Repository Strategy:');
        $strategy = $config['models']['repositories']['strategy'] ?? 'eloquent';
        $this->line('- ' . $strategy);
        
        $this->info('Cache Configuration:');
        $cacheConfig = $config['models']['repositories']['cache'] ?? [];
        $this->table(['Setting', 'Value'], collect($cacheConfig)->map(fn($v, $k) => [$k, $v])->toArray());
        
        return 0;
    }

    protected function showRepository($config, $name)
    {
        $reposPath = $config['models']['repositories']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a repository name.');
            return 1;
        }
        $file = $reposPath . DIRECTORY_SEPARATOR . $name . 'Repository.php';
        if (!File::exists($file)) {
            $this->error('Repository not found: ' . $file);
            return 1;
        }
        $this->info('Repository file: ' . $file);
        $this->line(File::get($file));
        return 0;
    }

    protected function addRepository($config, $name)
    {
        $reposPath = $config['models']['repositories']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a repository name.');
            return 1;
        }
        $file = $reposPath . DIRECTORY_SEPARATOR . $name . 'Repository.php';
        if (File::exists($file)) {
            $this->error('Repository already exists: ' . $file);
            return 1;
        }
        $stub = "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$name};\n\nclass {$name}Repository\n{\n    protected \$model;\n\n    public function __construct({$name} \$model)\n    {\n        \$this->model = \$model;\n    }\n\n    public function all()\n    {\n        return \$this->model->all();\n    }\n\n    public function find(\$id)\n    {\n        return \$this->model->find(\$id);\n    }\n\n    public function create(array \$data)\n    {\n        return \$this->model->create(\$data);\n    }\n\n    public function update(\$id, array \$data)\n    {\n        \$record = \$this->find(\$id);\n        if (\$record) {\n            \$record->update(\$data);\n            return \$record;\n        }\n        return null;\n    }\n\n    public function delete(\$id)\n    {\n        \$record = \$this->find(\$id);\n        if (\$record) {\n            return \$record->delete();\n        }\n        return false;\n    }\n}\n";
        File::put($file, $stub);
        $this->info('Repository created: ' . $file);
        return 0;
    }

    protected function removeRepository($config, $name)
    {
        $reposPath = $config['models']['repositories']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a repository name.');
            return 1;
        }
        $file = $reposPath . DIRECTORY_SEPARATOR . $name . 'Repository.php';
        if (!File::exists($file)) {
            $this->error('Repository not found: ' . $file);
            return 1;
        }
        File::delete($file);
        $this->info('Repository deleted: ' . $file);
        return 0;
    }

    protected function syncRepositories($config)
    {
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->info('Repository configuration synchronized and cache cleared.');
        return 0;
    }

    protected function showConfig($config)
    {
        $this->line('Repositories Configuration:');
        $this->line(json_encode($config['models']['repositories'], JSON_PRETTY_PRINT));
        return 0;
    }

    protected function validateConfig($config)
    {
        $errors = [];
        if (empty($config['models']['repositories']['path'])) {
            $errors[] = 'Missing repositories path.';
        }
        if (empty($config['models']['repositories']['namespaces'])) {
            $errors[] = 'Missing repositories namespaces.';
        }
        if (empty($config['models']['repositories']['strategy'])) {
            $errors[] = 'Missing repository strategy.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 1;
        }
        $this->info('Repositories configuration is valid.');
        return 0;
    }
} 