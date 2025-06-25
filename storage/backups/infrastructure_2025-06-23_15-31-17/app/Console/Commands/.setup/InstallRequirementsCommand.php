<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Config;

class InstallRequirementsCommand extends Command
{
    protected $signature = 'setup:install-requirements 
                            {--force : Force reinstallation of all components}
                            {--dry-run : Show what would be installed without actually installing}
                            {--rollback : Rollback the last installation}';

    protected $description = 'Install system requirements and dependencies';

    private $stateFile;
    private $currentState;
    private $dependencies = [
        'php' => ['check' => 'php -v', 'install' => 'winget install PHP.PHP'],
        'composer' => ['check' => 'composer -V', 'install' => 'winget install Composer.Composer'],
        'node' => ['check' => 'node -v', 'install' => 'winget install OpenJS.NodeJS'],
        'npm' => ['check' => 'npm -v', 'install' => 'winget install OpenJS.NodeJS'],
        'sqlite3' => ['check' => 'sqlite3 --version', 'install' => 'winget install SQLite.sqlite']
    ];

    public function __construct()
    {
        parent::__construct();
        $this->stateFile = storage_path('logs/install-state.json');
        $this->loadState();
    }

    public function handle()
    {
        if ($this->option('rollback')) {
            return $this->handleRollback();
        }

        $this->checkDependencies();
        $this->processRequirements();

        $this->saveState();
        $this->displaySummary();

        return $this->currentState['failed'] ? 1 : 0;
    }

    private function checkDependencies()
    {
        foreach ($this->dependencies as $name => $dep) {
            $this->info("Checking for {$name}...");
            
            try {
                $result = Process::run($dep['check']);
                if ($result->successful()) {
                    $this->info("{$name} is present.");
                    continue;
                }
            } catch (\Exception $e) {
                // Dependency not found
            }

            $this->warn("{$name} not found. Attempting to install...");
            
            try {
                $result = Process::run($dep['install']);
                if ($result->successful()) {
                    $this->info("{$name} installed successfully");
                } else {
                    $this->error("Failed to install {$name}: " . $result->errorOutput());
                    exit(1);
                }
            } catch (\Exception $e) {
                $this->error("Failed to install {$name}: " . $e->getMessage());
                exit(1);
            }
        }
    }

    private function processRequirements()
    {
        $config = json_decode(File::get(base_path('.setup/config/requirements.json')), true);
        $checkResults = $this->runSetupCheck();

        foreach (['requirements', 'php_extensions', 'composer_packages'] as $reqType) {
            $this->info("Processing {$reqType}...");

            foreach ($config[$reqType] as $name => $config) {
                if (!$this->option('force') && isset($this->currentState['installed'][$name])) {
                    $this->info("Skipping {$name} (already installed)");
                    continue;
                }

                $isInstalled = $checkResults[$reqType][$name] ?? false;

                if (!$isInstalled || $this->option('force')) {
                    if ($this->option('dry-run')) {
                        $this->info("Would install {$name}");
                        continue;
                    }

                    $this->info("Installing {$name}...");
                    try {
                        $result = $this->runSetupInstall($name);
                        
                        if ($result[$reqType][$name] ?? false) {
                            $this->info("Successfully installed {$name}");
                            $this->currentState['installed'][$name] = true;
                            $this->currentState['rollback'][$name] = true;
                        } else {
                            $this->error("Failed to install {$name}");
                            $this->currentState['failed'][$name] = true;
                        }
                    } catch (\Exception $e) {
                        $this->error("Error installing {$name}: " . $e->getMessage());
                        $this->currentState['failed'][$name] = true;
                    }
                } else {
                    $this->info("{$name} is already installed and meets requirements");
                    $this->currentState['installed'][$name] = true;
                }
            }
        }
    }

    private function handleRollback()
    {
        $this->info("Starting rollback process...");

        foreach ($this->currentState['rollback'] as $component => $value) {
            $this->info("Rolling back {$component}...");
            try {
                $result = $this->runSetupUninstall($component);
                if ($result['requirements'][$component] ?? false) {
                    $this->info("Successfully rolled back {$component}");
                } else {
                    $this->error("Failed to roll back {$component}");
                }
            } catch (\Exception $e) {
                $this->error("Error rolling back {$component}: " . $e->getMessage());
            }
        }

        $this->resetState();
        $this->saveState();
        return 0;
    }

    private function loadState()
    {
        if (File::exists($this->stateFile)) {
            $this->currentState = json_decode(File::get($this->stateFile), true);
        } else {
            $this->resetState();
        }
    }

    private function resetState()
    {
        $this->currentState = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'installed' => [],
            'failed' => [],
            'rollback' => []
        ];
    }

    private function saveState()
    {
        File::put($this->stateFile, json_encode($this->currentState, JSON_PRETTY_PRINT));
    }

    private function displaySummary()
    {
        $this->newLine();
        $this->info("Installation Summary:");
        $this->info("-------------------");
        $this->info("Successfully installed: " . count($this->currentState['installed']) . " components");
        $this->info("Failed installations: " . count($this->currentState['failed']) . " components");

        if (count($this->currentState['failed']) > 0) {
            $this->newLine();
            $this->error("Failed components:");
            foreach ($this->currentState['failed'] as $failed => $value) {
                $this->error("- {$failed}");
            }
            $this->newLine();
            $this->info("To rollback the installation, run:");
            $this->info("php artisan setup:install-requirements --rollback");
        } else {
            $this->newLine();
            $this->info("All requirements have been successfully installed!");
        }
    }

    private function runSetupCheck()
    {
        $result = Process::run(base_path('.setup/scripts/setup.ps1') . ' -Action check');
        return json_decode($result->output(), true);
    }

    private function runSetupInstall($component)
    {
        $result = Process::run(base_path('.setup/scripts/setup.ps1') . " -Action install -Component {$component}");
        return json_decode($result->output(), true);
    }

    private function runSetupUninstall($component)
    {
        $result = Process::run(base_path('.setup/scripts/setup.ps1') . " -Action uninstall -Component {$component}");
        return json_decode($result->output(), true);
    }
} 