<?php

namespace App\Console\Commands\Config;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageConfigJobsCommand extends Command
{
    protected $signature = 'config:jobs
        {action : Action to perform (list|show|add|remove|sync|show-config|validate)}
        {name? : Job name}';

    protected $description = 'Manage and inspect job configurations as defined in .config/commands.php and .config/config.php';

    protected $configPaths = [
        'commands' => '.config/commands.php',
        'config' => '.config/config.php'
    ];

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $config = $this->getConfig();

        switch ($action) {
            case 'list':
                return $this->listJobs($config);
            case 'show':
                return $this->showJob($config, $name);
            case 'add':
                return $this->addJob($config, $name);
            case 'remove':
                return $this->removeJob($config, $name);
            case 'sync':
                return $this->syncJobs($config);
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

    protected function listJobs($config)
    {
        $jobsPath = $config['commands']['jobs']['path'] ?? null;
        $namespaces = $config['commands']['jobs']['namespaces'] ?? [];
        
        $this->info('Registered Job Namespaces:');
        foreach ($namespaces as $ns) {
            $this->line('- ' . $ns);
        }
        
        $this->info('Jobs Path: ' . $jobsPath);
        if (File::exists($jobsPath)) {
            $files = File::files($jobsPath);
            $this->table(['Job File'], array_map(fn($f) => [$f->getFilename()], $files));
        }
        
        $this->info('Queue Configuration:');
        $queueConfig = $config['commands']['jobs']['queue'] ?? [];
        $this->table(['Setting', 'Value'], collect($queueConfig)->map(fn($v, $k) => [$k, $v])->toArray());
        
        return 0;
    }

    protected function showJob($config, $name)
    {
        $jobsPath = $config['commands']['jobs']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a job name.');
            return 1;
        }
        $file = $jobsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Job not found: ' . $file);
            return 1;
        }
        $this->info('Job file: ' . $file);
        $this->line(File::get($file));
        return 0;
    }

    protected function addJob($config, $name)
    {
        $jobsPath = $config['commands']['jobs']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a job name.');
            return 1;
        }
        $file = $jobsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (File::exists($file)) {
            $this->error('Job already exists: ' . $file);
            return 1;
        }
        $stub = "<?php\n\nnamespace App\\Jobs;\n\nuse Illuminate\\Bus\\Queueable;\nuse Illuminate\\Contracts\\Queue\\ShouldQueue;\nuse Illuminate\\Foundation\\Bus\\Dispatchable;\nuse Illuminate\\Queue\\InteractsWithQueue;\nuse Illuminate\\Queue\\SerializesModels;\n\nclass {$name} implements ShouldQueue\n{\n    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;\n\n    public function __construct()\n    {\n        //\n    }\n\n    public function handle()\n    {\n        //\n    }\n}\n";
        File::put($file, $stub);
        $this->info('Job created: ' . $file);
        return 0;
    }

    protected function removeJob($config, $name)
    {
        $jobsPath = $config['commands']['jobs']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a job name.');
            return 1;
        }
        $file = $jobsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Job not found: ' . $file);
            return 1;
        }
        File::delete($file);
        $this->info('Job deleted: ' . $file);
        return 0;
    }

    protected function syncJobs($config)
    {
        $this->call('config:clear');
        $this->call('queue:restart');
        $this->info('Job configuration synchronized and queue restarted.');
        return 0;
    }

    protected function showConfig($config)
    {
        $this->line('Jobs Configuration:');
        $this->line(json_encode($config['commands']['jobs'], JSON_PRETTY_PRINT));
        return 0;
    }

    protected function validateConfig($config)
    {
        $errors = [];
        if (empty($config['commands']['jobs']['path'])) {
            $errors[] = 'Missing jobs path.';
        }
        if (empty($config['commands']['jobs']['namespaces'])) {
            $errors[] = 'Missing jobs namespaces.';
        }
        if (empty($config['commands']['jobs']['queue'])) {
            $errors[] = 'Missing queue configuration.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 1;
        }
        $this->info('Jobs configuration is valid.');
        return 0;
    }
} 