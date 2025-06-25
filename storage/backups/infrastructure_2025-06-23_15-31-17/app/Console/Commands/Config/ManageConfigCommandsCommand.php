<?php

namespace App\Console\Commands\Config;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageConfigCommandsCommand extends Command
{
    protected $signature = 'config:commands
        {action : Action to perform (list|show|add|remove|sync|show-config|validate)}
        {name? : Command name}';

    protected $description = 'Manage and inspect custom Artisan commands as defined in .config/commands.php';

    protected $configPath = '.config/commands.php';

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $config = $this->getConfig();

        switch ($action) {
            case 'list':
                return $this->listCommands($config);
            case 'show':
                return $this->showCommand($config, $name);
            case 'add':
                return $this->addCommand($config, $name);
            case 'remove':
                return $this->removeCommand($config, $name);
            case 'sync':
                return $this->syncCommands($config);
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
        if (!File::exists(base_path($this->configPath))) {
            $this->error('Config file not found: ' . $this->configPath);
            exit(1);
        }
        return include base_path($this->configPath);
    }

    protected function listCommands($config)
    {
        $commandsPath = $config['commands']['path'] ?? null;
        $namespaces = $config['commands']['namespaces'] ?? [];
        $this->info('Registered Command Namespaces:');
        foreach ($namespaces as $ns) {
            $this->line('- ' . $ns);
        }
        $this->info('Commands Path: ' . $commandsPath);
        $files = File::files($commandsPath);
        $this->table(['Command File'], array_map(fn($f) => [$f->getFilename()], $files));
        return 0;
    }

    protected function showCommand($config, $name)
    {
        $commandsPath = $config['commands']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a command name.');
            return 1;
        }
        $file = $commandsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Command not found: ' . $file);
            return 1;
        }
        $this->info('Command file: ' . $file);
        $this->line(File::get($file));
        return 0;
    }

    protected function addCommand($config, $name)
    {
        $commandsPath = $config['commands']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a command name.');
            return 1;
        }
        $file = $commandsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (File::exists($file)) {
            $this->error('Command already exists: ' . $file);
            return 1;
        }
        $stub = "<?php\n\nnamespace App\\Console\\Commands;\n\nuse Illuminate\\Console\\Command;\n\nclass {$name} extends Command\n{\n    protected \$signature = 'custom:{$name}';\n    protected \$description = 'Describe the {$name} command.';\n\n    public function handle()\n    {\n        //\n    }\n}\n";
        File::put($file, $stub);
        $this->info('Command created: ' . $file);
        return 0;
    }

    protected function removeCommand($config, $name)
    {
        $commandsPath = $config['commands']['path'] ?? null;
        if (!$name) {
            $this->error('Please provide a command name.');
            return 1;
        }
        $file = $commandsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error('Command not found: ' . $file);
            return 1;
        }
        File::delete($file);
        $this->info('Command deleted: ' . $file);
        return 0;
    }

    protected function syncCommands($config)
    {
        // For demonstration, just clear the config cache
        $this->call('config:clear');
        $this->info('Config cache cleared.');
        return 0;
    }

    protected function showConfig($config)
    {
        $this->line(json_encode($config, JSON_PRETTY_PRINT));
        return 0;
    }

    protected function validateConfig($config)
    {
        $errors = [];
        if (empty($config['commands']['path'])) {
            $errors[] = 'Missing commands path.';
        }
        if (empty($config['commands']['namespaces'])) {
            $errors[] = 'Missing commands namespaces.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 1;
        }
        $this->info('Config is valid.');
        return 0;
    }
} 