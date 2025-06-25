<?php

namespace App\Console\Commands\Web3\Monitor;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ContractMonitorCommand extends Command
{
    protected $signature = 'web3:monitor:contract
                          {contract : Name of the contract to monitor}
                          {--network=mainnet : Network to monitor (mainnet, testnet)}
                          {--events : Monitor contract events}
                          {--transactions : Monitor contract transactions}
                          {--interval=60 : Monitoring interval in seconds}';

    protected $description = 'Monitor smart contract activity including events and transactions';

    protected $web3Path;
    protected $monitoringPath;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->monitoringPath = base_path('.web3/monitoring');
    }

    public function handle()
    {
        $contract = $this->argument('contract');
        $network = $this->option('network');
        $monitorEvents = $this->option('events');
        $monitorTransactions = $this->option('transactions');
        $interval = $this->option('interval');

        // Validate contract exists
        if (!File::exists("{$this->web3Path}/contracts/{$contract}.sol")) {
            $this->error("Contract {$contract}.sol not found!");
            return 1;
        }

        $this->info("Starting monitoring for {$contract} on {$network}...");

        // Create monitoring directory if it doesn't exist
        if (!File::exists($this->monitoringPath)) {
            File::makeDirectory($this->monitoringPath, 0755, true);
        }

        // Build monitoring command
        $command = $this->buildMonitoringCommand($contract, $network, $monitorEvents, $monitorTransactions, $interval);

        // Execute monitoring
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(null); // Run indefinitely
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Monitoring failed: {$process->getErrorOutput()}");
            return 1;
        }

        return 0;
    }

    protected function buildMonitoringCommand($contract, $network, $monitorEvents, $monitorTransactions, $interval)
    {
        $command = "node scripts/monitor-contract.js --contract {$contract} --network {$network} --interval {$interval}";

        if ($monitorEvents) {
            $command .= ' --events';
        }

        if ($monitorTransactions) {
            $command .= ' --transactions';
        }

        return $command;
    }
} 