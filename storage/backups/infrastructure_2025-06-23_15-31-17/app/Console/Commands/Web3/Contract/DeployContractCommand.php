<?php

namespace App\Console\Commands\Web3\Contract;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DeployContractCommand extends Command
{
    protected $signature = 'web3:contract:deploy 
                          {contract : Name of the contract to deploy (e.g., Reward, ServiceVerification)}
                          {--network=hardhat : Network to deploy to (hardhat, testnet, mainnet)}
                          {--verify : Verify contract on Etherscan}';

    protected $description = 'Deploy a smart contract to the specified network';

    protected $contractsPath;
    protected $artifactsPath;

    public function __construct()
    {
        parent::__construct();
        $this->contractsPath = base_path('.web3/contracts');
        $this->artifactsPath = base_path('.web3/artifacts');
    }

    public function handle()
    {
        $contractName = $this->argument('contract');
        $network = $this->option('network');
        $shouldVerify = $this->option('verify');

        // Validate contract exists
        if (!File::exists("{$this->contractsPath}/{$contractName}.sol")) {
            $this->error("Contract {$contractName}.sol not found!");
            return 1;
        }

        $this->info("Deploying {$contractName} to {$network}...");

        // Build deployment command
        $command = "npx hardhat run scripts/deploy.js --network {$network}";
        if ($shouldVerify) {
            $command .= " --verify";
        }

        // Execute deployment
        $process = Process::fromShellCommandline($command, base_path('.web3'));
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to deploy contract: {$process->getErrorOutput()}");
            return 1;
        }

        $this->info("Contract {$contractName} deployed successfully!");
        return 0;
    }
} 