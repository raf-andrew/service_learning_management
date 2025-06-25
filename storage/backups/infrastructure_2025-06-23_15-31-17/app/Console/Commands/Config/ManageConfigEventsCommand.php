<?php

namespace App\Console\Commands\Config;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageConfigEventsCommand extends Command
{
    protected $signature = 'config:events
        {action : Action to perform (list|show|add|remove|sync|show-config|validate)}
        {name? : Event name}
        {--type=event : Type of item (event|listener)}';

    protected $description = 'Manage and inspect events and listeners configurations as defined in .config/events.php and .config/config.php';

    protected $configPaths = [
        'events' => '.config/events.php',
        'config' => '.config/config.php'
    ];

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $type = $this->option('type');
        $config = $this->getConfig();

        switch ($action) {
            case 'list':
                return $this->listItems($config, $type);
            case 'show':
                return $this->showItem($config, $name, $type);
            case 'add':
                return $this->addItem($config, $name, $type);
            case 'remove':
                return $this->removeItem($config, $name, $type);
            case 'sync':
                return $this->syncItems($config);
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

    protected function listItems($config, $type)
    {
        $path = $type === 'event' ? 'events' : 'listeners';
        $itemsPath = $config['events'][$path]['path'] ?? null;
        $namespaces = $config['events'][$path]['namespaces'] ?? [];
        
        $this->info("Registered {$type} Namespaces:");
        foreach ($namespaces as $ns) {
            $this->line('- ' . $ns);
        }
        
        $this->info("{$type}s Path: " . $itemsPath);
        if (File::exists($itemsPath)) {
            $files = File::files($itemsPath);
            $this->table([ucfirst($type) . ' File'], array_map(fn($f) => [$f->getFilename()], $files));
        }
        
        if ($type === 'event') {
            $this->info('Broadcast Configuration:');
            $broadcastConfig = $config['events']['events']['broadcast'] ?? [];
            $this->table(['Setting', 'Value'], collect($broadcastConfig)->map(fn($v, $k) => [$k, $v])->toArray());
        } else {
            $this->info('Queue Configuration:');
            $queueConfig = $config['events']['listeners']['queue'] ?? [];
            $this->table(['Setting', 'Value'], collect($queueConfig)->map(fn($v, $k) => [$k, $v])->toArray());
        }
        
        return 0;
    }

    protected function showItem($config, $name, $type)
    {
        $path = $type === 'event' ? 'events' : 'listeners';
        $itemsPath = $config['events'][$path]['path'] ?? null;
        if (!$name) {
            $this->error("Please provide a {$type} name.");
            return 1;
        }
        $file = $itemsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error("{$type} not found: " . $file);
            return 1;
        }
        $this->info("{$type} file: " . $file);
        $this->line(File::get($file));
        return 0;
    }

    protected function addItem($config, $name, $type)
    {
        $path = $type === 'event' ? 'events' : 'listeners';
        $itemsPath = $config['events'][$path]['path'] ?? null;
        if (!$name) {
            $this->error("Please provide a {$type} name.");
            return 1;
        }
        $file = $itemsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (File::exists($file)) {
            $this->error("{$type} already exists: " . $file);
            return 1;
        }

        if ($type === 'event') {
            $stub = "<?php\n\nnamespace App\\Events;\n\nuse Illuminate\\Broadcasting\\Channel;\nuse Illuminate\\Broadcasting\\InteractsWithSockets;\nuse Illuminate\\Broadcasting\\PresenceChannel;\nuse Illuminate\\Broadcasting\\PrivateChannel;\nuse Illuminate\\Contracts\\Broadcasting\\ShouldBroadcast;\nuse Illuminate\\Foundation\\Events\\Dispatchable;\nuse Illuminate\\Queue\\SerializesModels;\n\nclass {$name}\n{\n    use Dispatchable, InteractsWithSockets, SerializesModels;\n\n    public function __construct()\n    {\n        //\n    }\n\n    public function broadcastOn()\n    {\n        return new PrivateChannel('channel-name');\n    }\n}\n";
        } else {
            $stub = "<?php\n\nnamespace App\\Listeners;\n\nuse App\\Events\\{$name};\nuse Illuminate\\Contracts\\Queue\\ShouldQueue;\nuse Illuminate\\Queue\\InteractsWithQueue;\n\nclass {$name}Listener implements ShouldQueue\n{\n    use InteractsWithQueue;\n\n    public function __construct()\n    {\n        //\n    }\n\n    public function handle({$name} \$event)\n    {\n        //\n    }\n}\n";
        }

        File::put($file, $stub);
        $this->info("{$type} created: " . $file);
        return 0;
    }

    protected function removeItem($config, $name, $type)
    {
        $path = $type === 'event' ? 'events' : 'listeners';
        $itemsPath = $config['events'][$path]['path'] ?? null;
        if (!$name) {
            $this->error("Please provide a {$type} name.");
            return 1;
        }
        $file = $itemsPath . DIRECTORY_SEPARATOR . $name . '.php';
        if (!File::exists($file)) {
            $this->error("{$type} not found: " . $file);
            return 1;
        }
        File::delete($file);
        $this->info("{$type} deleted: " . $file);
        return 0;
    }

    protected function syncItems($config)
    {
        $this->call('config:clear');
        $this->call('event:clear');
        $this->info('Events configuration synchronized and event cache cleared.');
        return 0;
    }

    protected function showConfig($config)
    {
        $this->line('Events Configuration:');
        $this->line(json_encode($config['events'], JSON_PRETTY_PRINT));
        return 0;
    }

    protected function validateConfig($config)
    {
        $errors = [];
        if (empty($config['events']['events']['path'])) {
            $errors[] = 'Missing events path.';
        }
        if (empty($config['events']['events']['namespaces'])) {
            $errors[] = 'Missing events namespaces.';
        }
        if (empty($config['events']['listeners']['path'])) {
            $errors[] = 'Missing listeners path.';
        }
        if (empty($config['events']['listeners']['namespaces'])) {
            $errors[] = 'Missing listeners namespaces.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 1;
        }
        $this->info('Events configuration is valid.');
        return 0;
    }
} 