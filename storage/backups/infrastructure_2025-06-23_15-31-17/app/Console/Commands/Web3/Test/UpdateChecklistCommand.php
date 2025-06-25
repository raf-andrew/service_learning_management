<?php

namespace App\Console\Commands\Web3\Test;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UpdateChecklistCommand extends Command
{
    protected $signature = 'web3:test:checklist
                          {action : Action to perform (update, show, reset)}
                          {--test= : Specific test to update}
                          {--status= : Test status (passed, failed, skipped)}
                          {--format=table : Output format (table, json, markdown)}';

    protected $description = 'Manage test checklist and test status';

    protected $web3Path;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
    }

    public function handle()
    {
        $action = $this->argument('action');
        $test = $this->option('test');
        $status = $this->option('status');
        $format = $this->option('format');

        switch ($action) {
            case 'update':
                if (!$test || !$status) {
                    $this->error('Test name and status are required for update action');
                    return 1;
                }
                return $this->updateChecklist($test, $status);
            case 'show':
                return $this->showChecklist($format);
            case 'reset':
                return $this->resetChecklist();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function updateChecklist($test, $status)
    {
        $this->info("Updating checklist for test: {$test}");

        $command = "node scripts/update-checklist.js update --test {$test} --status {$status}";
        
        return $this->executeCommand($command);
    }

    protected function showChecklist($format)
    {
        $this->info('Showing test checklist...');

        $command = "node scripts/update-checklist.js show --format {$format}";
        
        return $this->executeCommand($command);
    }

    protected function resetChecklist()
    {
        $this->info('Resetting test checklist...');

        $command = "node scripts/update-checklist.js reset";
        
        return $this->executeCommand($command);
    }

    protected function executeCommand($command)
    {
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Command failed: {$process->getErrorOutput()}");
            return 1;
        }

        return 0;
    }
} 