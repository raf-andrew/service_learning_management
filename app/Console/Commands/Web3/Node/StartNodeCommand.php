<?php

namespace App\Console\Commands\Web3\Node;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartNodeCommand extends Command
{
    protected $signature = 'web3:node:start
                          {--host=127.0.0.1 : Host to run the node on}
                          {--port=8545 : Port to run the node on}
                          {--fork= : Fork a specific network (e.g., mainnet, testnet)}
                          {--fork-block-number= : Block number to fork from}';

    protected $description = 'Start a local Hardhat node for development and testing';

    protected $web3Path;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
    }

    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $fork = $this->option('fork');
        $forkBlockNumber = $this->option('fork-block-number');

        $this->info("Starting Hardhat node on {$host}:{$port}...");

        // Build node command
        $command = "npx hardhat node --host {$host} --port {$port}";
        
        if ($fork) {
            $command .= " --fork {$fork}";
            if ($forkBlockNumber) {
                $command .= " --fork-block-number {$forkBlockNumber}";
            }
        }

        // Execute node
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(null); // Run indefinitely
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to start node: {$process->getErrorOutput()}");
            return 1;
        }

        return 0;
    }
} 