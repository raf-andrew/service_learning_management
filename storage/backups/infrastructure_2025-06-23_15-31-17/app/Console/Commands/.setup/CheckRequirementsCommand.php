<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class CheckRequirementsCommand extends Command
{
    protected $signature = 'setup:check-requirements 
                            {--install : Install missing requirements}
                            {--force : Force installation without confirmation}
                            {--dry-run : Show what would be installed without actually installing}';

    protected $description = 'Check system requirements and dependencies';

    private $requirements;
    private $results = [
        'installed' => [],
        'missing' => [],
        'outdated' => [],
        'errors' => []
    ];

    public function handle()
    {
        $this->requirements = json_decode(File::get(base_path('.setup/config/requirements.json')), true);
        
        $this->checkRequirements();
        $this->checkPhpExtensions();
        $this->displaySummary();

        return (count($this->results['missing']) > 0 || count($this->results['errors']) > 0) ? 1 : 0;
    }

    private function checkRequirements()
    {
        foreach ($this->requirements as $name => $config) {
            if (!isset($config['required']) || !$config['required']) {
                continue;
            }

            $this->info("\nChecking {$name}...");

            if ($this->testCommand($name)) {
                $version = $this->getVersion($name, $config['verify_pattern'] ?? null);
                
                if ($version) {
                    if ($version === $config['version']) {
                        $this->results['installed'][] = [
                            'name' => $name,
                            'version' => $version
                        ];
                        $this->info("✅ {$name} {$version} is installed");
                    } else {
                        $this->results['outdated'][] = [
                            'name' => $name,
                            'current_version' => $version,
                            'required_version' => $config['version']
                        ];
                        $this->warn("⚠️ {$name} is outdated (current: {$version}, required: {$config['version']})");
                    }
                } else {
                    $this->results['errors'][] = [
                        'name' => $name,
                        'error' => 'Could not determine version'
                    ];
                    $this->error("❌ Could not determine {$name} version");
                }
            } else {
                $this->results['missing'][] = [
                    'name' => $name,
                    'required_version' => $config['version']
                ];
                $this->error("❌ {$name} is not installed");

                if ($this->option('install')) {
                    if ($this->option('force') || $this->option('dry-run')) {
                        $this->installRequirement($name, $config['install_command']);
                    } else {
                        if ($this->confirm("Would you like to install {$name}?")) {
                            $this->installRequirement($name, $config['install_command']);
                        }
                    }
                }
            }
        }
    }

    private function checkPhpExtensions()
    {
        if (!isset($this->requirements['php']['extensions'])) {
            return;
        }

        $this->info("\nChecking PHP extensions...");
        
        $loadedExtensions = explode("\n", trim(Process::run('php -m')->output()));
        
        foreach ($this->requirements['php']['extensions'] as $extName => $extConfig) {
            if (!isset($extConfig['required']) || !$extConfig['required']) {
                continue;
            }

            if (in_array($extName, $loadedExtensions)) {
                $this->results['installed'][] = [
                    'name' => "PHP Extension: {$extName}",
                    'version' => $extConfig['version']
                ];
                $this->info("✅ PHP extension {$extName} is installed");
            } else {
                $this->results['missing'][] = [
                    'name' => "PHP Extension: {$extName}",
                    'required_version' => $extConfig['version']
                ];
                $this->error("❌ PHP extension {$extName} is not installed");
            }
        }
    }

    private function testCommand($command)
    {
        try {
            $result = Process::run("where {$command}");
            return $result->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getVersion($command, $pattern)
    {
        try {
            $result = Process::run($command);
            if ($pattern && preg_match($pattern, $result->output(), $matches)) {
                return $matches[1];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function installRequirement($name, $installCommand)
    {
        $this->info("Installing {$name}...");
        
        if ($this->option('dry-run')) {
            $this->info("Would run: {$installCommand}");
            return;
        }

        try {
            $result = Process::run($installCommand);
            if ($result->successful()) {
                $this->info("Successfully installed {$name}");
                return true;
            } else {
                $this->error("Failed to install {$name}: " . $result->errorOutput());
                return false;
            }
        } catch (\Exception $e) {
            $this->error("Failed to install {$name}: " . $e->getMessage());
            return false;
        }
    }

    private function displaySummary()
    {
        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Installed: " . count($this->results['installed']));
        $this->info("Missing: " . count($this->results['missing']));
        $this->info("Outdated: " . count($this->results['outdated']));
        $this->info("Errors: " . count($this->results['errors']));

        if (count($this->results['missing']) > 0) {
            $this->newLine();
            $this->error("Missing Requirements:");
            foreach ($this->results['missing'] as $missing) {
                $this->error("- {$missing['name']} (required version: {$missing['required_version']})");
            }
        }

        if (count($this->results['outdated']) > 0) {
            $this->newLine();
            $this->warn("Outdated Requirements:");
            foreach ($this->results['outdated'] as $outdated) {
                $this->warn("- {$outdated['name']} (current: {$outdated['current_version']}, required: {$outdated['required_version']})");
            }
        }

        if (count($this->results['errors']) > 0) {
            $this->newLine();
            $this->error("Errors:");
            foreach ($this->results['errors'] as $error) {
                $this->error("- {$error['name']}: {$error['error']}");
            }
        }
    }
} 