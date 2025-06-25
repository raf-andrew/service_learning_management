<?php

namespace App\Console\Commands\Web3\Contract;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class LintContractCommand extends Command
{
    protected $signature = 'web3:contract:lint
                          {contract? : Specific contract to lint}
                          {--fix : Automatically fix linting issues}
                          {--config= : Path to custom solhint config}';

    protected $description = 'Lint smart contracts using Solhint';

    protected $web3Path;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
    }

    public function handle()
    {
        $contract = $this->argument('contract');
        $fix = $this->option('fix');
        $config = $this->option('config');

        $this->info('Linting contracts...');

        // Build lint command
        $command = 'npx solhint';
        
        if ($contract) {
            $command .= " contracts/{$contract}.sol";
        } else {
            $command .= " 'contracts/**/*.sol'";
        }

        if ($fix) {
            $command .= ' --fix';
        }

        if ($config) {
            $command .= " --config {$config}";
        }

        // Execute linting
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Linting failed: {$process->getErrorOutput()}");
            return 1;
        }

        $this->info('Linting completed successfully!');
        return 0;
    }
} 