<?php

namespace App\Console\Commands\Codespaces;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ManageCodespaceCommand extends Command
{
    protected $signature = 'codespaces:manage {action : Action to perform (create|delete|rebuild|list|connect)} {env? : Environment name}';
    protected $description = 'Manage GitHub Codespaces';

    protected $configFile;
    protected $stateFile;
    protected $scriptsDir;

    public function handle()
    {
        $this->configFile = base_path('.codespaces/config/codespaces.json');
        $this->stateFile = base_path('.codespaces/state/codespaces.json');
        $this->scriptsDir = base_path('.codespaces/scripts');

        $action = $this->argument('action');
        $env = $this->argument('env');

        // Check GitHub CLI
        $this->checkGitHubCli();

        // Check authentication
        $this->checkAuthentication();

        switch ($action) {
            case 'create':
                $this->createCodespace($env);
                break;
            case 'delete':
                $this->deleteCodespace($env);
                break;
            case 'rebuild':
                $this->rebuildCodespace($env);
                break;
            case 'list':
                $this->listCodespaces();
                break;
            case 'connect':
                $this->connectCodespace($env);
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    protected function checkGitHubCli()
    {
        $process = new Process(['gh', '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('GitHub CLI is not installed. Please install it first: https://cli.github.com/');
            exit(1);
        }
    }

    protected function checkAuthentication()
    {
        $process = new Process(['gh', 'auth', 'status']);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->info('GitHub authentication required');
            $process = new Process(['gh', 'auth', 'login', '--web']);
            $process->setTty(true);
            $process->run();
        }
    }

    protected function updateState(string $key, $value)
    {
        $state = json_decode(File::get($this->stateFile), true);
        $state = array_set($state, $key, $value);
        File::put($this->stateFile, json_encode($state, JSON_PRETTY_PRINT));
    }

    protected function getCodespaceStatus(string $env): string
    {
        $state = json_decode(File::get($this->stateFile), true);
        return $state['environments'][$env]['status'] ?? 'not_created';
    }

    protected function createCodespace(string $env)
    {
        $this->info("Creating Codespace for environment: {$env}");

        $config = json_decode(File::get($this->configFile), true);
        $state = json_decode(File::get($this->stateFile), true);

        $process = new Process([
            'gh', 'codespace', 'create',
            '--repo', $state['github']['repository']['url'],
            '--branch', $state['github']['repository']['branch'],
            '--machine', $config['environments'][$env]['machine'],
            '--region', $config['defaults']['region'],
            '--json', 'id,name,url'
        ]);

        $process->run();
        $codespace = json_decode($process->getOutput(), true);

        $this->updateState("environments.{$env}.status", 'created');
        $this->updateState("environments.{$env}.name", $codespace['name']);
        $this->updateState("environments.{$env}.url", $codespace['url']);
        $this->updateState("environments.{$env}.created_at", now()->toIso8601String());

        $this->info("Codespace created: {$codespace['name']}");
        $this->info("URL: {$codespace['url']}");
    }

    protected function deleteCodespace(string $env)
    {
        $state = json_decode(File::get($this->stateFile), true);
        $name = $state['environments'][$env]['name'] ?? null;

        if ($name) {
            $this->info("Deleting Codespace: {$name}");

            $process = new Process(['gh', 'codespace', 'delete', $name, '--force']);
            $process->run();

            $this->updateState("environments.{$env}.status", 'not_created');
            $this->updateState("environments.{$env}.name", null);
            $this->updateState("environments.{$env}.url", null);
            $this->updateState("environments.{$env}.created_at", null);

            $this->info('Codespace deleted');
        } else {
            $this->warn("No Codespace found for environment: {$env}");
        }
    }

    protected function rebuildCodespace(string $env)
    {
        $state = json_decode(File::get($this->stateFile), true);
        $name = $state['environments'][$env]['name'] ?? null;

        if ($name) {
            $this->info("Rebuilding Codespace: {$name}");

            $process = new Process(['gh', 'codespace', 'rebuild', $name]);
            $process->run();

            $this->updateState("environments.{$env}.updated_at", now()->toIso8601String());

            $this->info('Codespace rebuilt');
        } else {
            $this->warn("No Codespace found for environment: {$env}");
        }
    }

    protected function listCodespaces()
    {
        $this->info('Listing Codespaces');

        $process = new Process(['gh', 'codespace', 'list', '--json', 'name,state,gitStatus,createdAt']);
        $process->run();

        $codespaces = json_decode($process->getOutput(), true);
        $this->table(
            ['Name', 'State', 'Git Status', 'Created At'],
            collect($codespaces)->map(fn($cs) => [
                $cs['name'],
                $cs['state'],
                $cs['gitStatus']['ahead'] . ' ahead, ' . $cs['gitStatus']['behind'] . ' behind',
                $cs['createdAt']
            ])
        );
    }

    protected function connectCodespace(string $env)
    {
        $state = json_decode(File::get($this->stateFile), true);
        $name = $state['environments'][$env]['name'] ?? null;

        if ($name) {
            $this->info("Connecting to Codespace: {$name}");

            $process = new Process(['gh', 'codespace', 'code', $name]);
            $process->setTty(true);
            $process->run();
        } else {
            $this->warn("No Codespace found for environment: {$env}");
        }
    }
} 