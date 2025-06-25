<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConvertShellScriptsCommand extends Command
{
    protected $signature = 'commands:convert-scripts';
    protected $description = 'Convert shell scripts to Laravel commands';

    protected $scriptLocations = [
        '.web3/scripts' => 'web3',
        '.setup/scripts' => 'environment',
        '.mcp/scripts' => 'infrastructure',
        '.health/scripts' => 'infrastructure',
        '.codespaces/scripts' => 'codespaces',
    ];

    public function handle()
    {
        foreach ($this->scriptLocations as $scriptDir => $domain) {
            $this->info("Processing scripts in {$scriptDir}...");
            
            if (!File::exists($scriptDir)) {
                $this->warn("Directory not found: {$scriptDir}");
                continue;
            }

            $files = File::files($scriptDir);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['sh', 'ps1'])) {
                    $this->convertScript($file, $domain);
                }
            }
        }

        $this->info('Script conversion completed!');
    }

    protected function convertScript($file, $domain)
    {
        $content = File::get($file);
        $className = $this->generateClassName($file->getFilename());
        $commandName = $this->generateCommandName($file->getFilename());
        
        $commandContent = $this->generateCommandClass($className, $commandName, $content);
        
        $targetPath = app_path("Console/Commands/.{$domain}/{$className}.php");
        File::put($targetPath, $commandContent);
        
        $this->info("Converted {$file->getFilename()} to {$className}");
    }

    protected function generateClassName($filename)
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name . 'Command';
    }

    protected function generateCommandName($filename)
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['-', '_'], ':', $name);
        return $name;
    }

    protected function generateCommandClass($className, $commandName, $scriptContent)
    {
        return <<<PHP
<?php

namespace App\Console\Commands\\{$className};

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class {$className} extends Command
{
    protected \$signature = '{$commandName}';
    protected \$description = 'Converted from shell script';

    public function handle()
    {
        // TODO: Convert shell script logic to PHP
        // Original script content:
        /*
        {$scriptContent}
        */
        
        \$this->info('Command executed successfully');
    }
}
PHP;
    }
} 