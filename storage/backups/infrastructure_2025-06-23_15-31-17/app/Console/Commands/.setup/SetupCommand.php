<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class SetupCommand extends Command
{
    protected $signature = 'setup:run 
                            {action=check : Action to perform (check, install, uninstall)}
                            {component=all : Component to process (all, php, composer, node, npm, git, mysql, redis, php_extensions, composer_packages)}';

    protected $description = 'Setup and manage local development environment';

    private $config;
    private $results = [
        'requirements' => [],
        'php_extensions' => [],
        'composer_packages' => []
    ];

    public function handle()
    {
        $this->config = json_decode(File::get(base_path('.setup/config/requirements.json')), true);
        
        $this->info("Starting setup with action: {$this->argument('action')}, component: {$this->argument('component')}");

        $this->processRequirements();
        $this->processPhpExtensions();
        $this->processComposerPackages();

        $this->displayResults();

        return $this->hasFailures() ? 1 : 0;
    }

    private function processRequirements()
    {
        $this->results['requirements'] = $this->processComponent(
            $this->config['requirements'] ?? [],
            $this->argument('action')
        );
    }

    private function processPhpExtensions()
    {
        $this->results['php_extensions'] = $this->processComponent(
            $this->config['php_extensions'] ?? [],
            $this->argument('action')
        );
    }

    private function processComposerPackages()
    {
        $this->results['composer_packages'] = $this->processComponent(
            $this->config['composer_packages'] ?? [],
            $this->argument('action')
        );
    }

    private function processComponent($requirements, $action)
    {
        $results = [];
        $component = $this->argument('component');

        foreach ($requirements as $name => $config) {
            if ($component !== 'all' && $component !== $name) {
                continue;
            }

            switch ($action) {
                case 'check':
                    $results[$name] = $this->testRequirement($name, $config);
                    break;
                case 'install':
                    if (!$this->testRequirement($name, $config)) {
                        $results[$name] = $this->installRequirement($name, $config);
                    } else {
                        $results[$name] = true;
                    }
                    break;
                case 'uninstall':
                    if ($this->testRequirement($name, $config)) {
                        $results[$name] = $this->uninstallRequirement($name, $config);
                    } else {
                        $results[$name] = true;
                    }
                    break;
            }
        }

        return $results;
    }

    private function testRequirement($name, $config)
    {
        try {
            $result = Process::run($config['check_command']);
            if (isset($config['verify_pattern'])) {
                if (preg_match($config['verify_pattern'], $result->output(), $matches)) {
                    $version = $matches[1];
                    if (isset($config['version'])) {
                        $requiredVersion = preg_replace('/[^0-9.]/', '', $config['version']);
                        $operator = preg_replace('/[0-9.]/', '', $config['version']);
                        return $this->compareVersion($version, $requiredVersion, $operator);
                    }
                    return true;
                }
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function installRequirement($name, $config)
    {
        $this->info("Installing {$name}...");
        try {
            $result = Process::run($config['install_command']);
            if ($this->testRequirement($name, $config)) {
                $this->info("Successfully installed {$name}");
                return true;
            } else {
                $this->error("Failed to install {$name}");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("Error installing {$name}: " . $e->getMessage());
            return false;
        }
    }

    private function uninstallRequirement($name, $config)
    {
        $this->info("Uninstalling {$name}...");
        try {
            $result = Process::run($config['uninstall_command']);
            if (!$this->testRequirement($name, $config)) {
                $this->info("Successfully uninstalled {$name}");
                return true;
            } else {
                $this->error("Failed to uninstall {$name}");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("Error uninstalling {$name}: " . $e->getMessage());
            return false;
        }
    }

    private function compareVersion($version1, $version2, $operator)
    {
        $v1 = version_compare($version1, $version2, $operator);
        return $v1;
    }

    private function displayResults()
    {
        $this->newLine();
        $this->info("Setup results:");
        $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
    }

    private function hasFailures()
    {
        foreach ($this->results as $category) {
            if (in_array(false, $category)) {
                return true;
            }
        }
        return false;
    }
} 