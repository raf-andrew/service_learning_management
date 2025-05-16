<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class CodespaceCommand extends Command
{
    protected $signature = 'codespace
        {action : The action to perform (create|delete|rebuild|list|connect)}
        {environment? : The environment to perform the action on (development|staging|production)}';

    protected $description = 'Manage GitHub Codespaces';

    protected $configFile;
    protected $stateFile;
    protected $scriptFile;

    public function __construct()
    {
        parent::__construct();
        $this->configFile = base_path('.codespaces/config/codespaces.json');
        $this->stateFile = base_path('.codespaces/state/codespaces.json');
        $this->scriptFile = base_path('.codespaces/scripts/codespace.sh');
    }

    public function handle()
    {
        if (!File::exists($this->configFile)) {
            $this->error('Codespaces configuration file not found.');
            return 1;
        }

        if (!File::exists($this->stateFile)) {
            $this->error('Codespaces state file not found.');
            return 1;
        }

        if (!File::exists($this->scriptFile)) {
            $this->error('Codespaces script file not found.');
            return 1;
        }

        $action = $this->argument('action');
        $environment = $this->argument('environment');

        if (!$environment && in_array($action, ['create', 'delete', 'rebuild', 'connect'])) {
            $environment = $this->choice(
                'Select environment',
                ['development', 'staging', 'production']
            );
        }

        $this->info("Executing Codespace action: {$action}" . ($environment ? " for environment: {$environment}" : ''));

        try {
            $command = "bash {$this->scriptFile} {$action}";
            if ($environment) {
                $command .= " {$environment}";
            }

            $process = Process::run($command);

            if (!$process->successful()) {
                $this->error("Failed to execute Codespace action: {$process->errorOutput()}");
                return 1;
            }

            $this->info($process->output());
            return 0;
        } catch (\Exception $e) {
            $this->error("Error executing Codespace action: {$e->getMessage()}");
            return 1;
        }
    }

    protected function getConfig()
    {
        return json_decode(File::get($this->configFile), true);
    }

    protected function getState()
    {
        return json_decode(File::get($this->stateFile), true);
    }

    protected function updateState($key, $value)
    {
        $state = $this->getState();
        data_set($state, $key, $value);
        File::put($this->stateFile, json_encode($state, JSON_PRETTY_PRINT));
    }
} 