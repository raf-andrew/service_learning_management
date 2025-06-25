<?php

namespace App\Console\Commands\Web3\Contract;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CompileContractCommand extends Command
{
    protected $signature = 'web3:contract:compile
                          {contract? : Specific contract to compile}
                          {--clean : Clean the cache before compilation}
                          {--force : Force recompilation}';

    protected $description = 'Compile smart contracts using Hardhat';

    protected $web3Path;
    protected $artifactsPath;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->artifactsPath = base_path('.web3/artifacts');
    }

    public function handle()
    {
        $contract = $this->argument('contract');
        $clean = $this->option('clean');
        $force = $this->option('force');

        if ($clean) {
            $this->info('Cleaning cache...');
            $this->cleanCache();
        }

        $this->info('Compiling contracts...');

        // Build compilation command
        $command = 'npx hardhat compile';
        if ($force) {
            $command .= ' --force';
        }

        // Execute compilation
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Compilation failed: {$process->getErrorOutput()}");
            return 1;
        }

        // Verify artifacts were generated
        if (!File::exists($this->artifactsPath)) {
            $this->error('No artifacts were generated!');
            return 1;
        }

        $this->info('Contracts compiled successfully!');
        return 0;
    }

    protected function cleanCache()
    {
        $cachePath = "{$this->web3Path}/cache";
        if (File::exists($cachePath)) {
            File::deleteDirectory($cachePath);
        }
    }
} 