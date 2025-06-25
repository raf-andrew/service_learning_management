<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Web3\Web3Service;

class Web3ManagerCommand extends Command
{
    protected $signature = 'web3:manage 
        {action : Action to perform (status|connect|disconnect|verify)}
        {--network= : Specific network to manage}
        {--address= : Wallet address for verification}';

    protected $description = 'Manage Web3 services and connections';

    public function handle()
    {
        $action = $this->argument('action');
        $network = $this->option('network');
        $address = $this->option('address');

        if (!$this->isValidAction($action)) {
            $this->error("Invalid action: {$action}");
            return 1;
        }

        $web3Service = app(Web3Service::class);

        try {
            switch ($action) {
                case 'status':
                    $this->handleStatus($web3Service);
                    break;
                case 'connect':
                    $this->handleConnect($web3Service, $network);
                    break;
                case 'disconnect':
                    $this->handleDisconnect($web3Service, $network);
                    break;
                case 'verify':
                    if (!$address) {
                        $this->error('Address is required for verification');
                        return 1;
                    }
                    $this->handleVerify($web3Service, $address, $network);
                    break;
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    protected function isValidAction(string $action): bool
    {
        return in_array($action, ['status', 'connect', 'disconnect', 'verify']);
    }

    protected function handleStatus(Web3Service $web3Service): void
    {
        $this->info('Checking Web3 service status...');

        $status = $web3Service->getStatus();
        
        $this->table(
            ['Network', 'Status', 'Connected Address', 'Last Check'],
            collect($status)->map(fn($item) => [
                $item['network'],
                $item['connected'] ? '✅ Connected' : '❌ Disconnected',
                $item['address'] ?? 'N/A',
                $item['last_check'] ?? 'N/A'
            ])
        );
    }

    protected function handleConnect(Web3Service $web3Service, ?string $network): void
    {
        $this->info('Connecting to Web3 network...');
        
        if ($network) {
            $result = $web3Service->connectToNetwork($network);
            $this->info("Connected to {$network} successfully.");
        } else {
            $result = $web3Service->connectToAllNetworks();
            $this->info('Connected to all available networks successfully.');
        }
    }

    protected function handleDisconnect(Web3Service $web3Service, ?string $network): void
    {
        $this->info('Disconnecting from Web3 network...');
        
        if ($network) {
            $web3Service->disconnectFromNetwork($network);
            $this->info("Disconnected from {$network} successfully.");
        } else {
            $web3Service->disconnectFromAllNetworks();
            $this->info('Disconnected from all networks successfully.');
        }
    }

    protected function handleVerify(Web3Service $web3Service, string $address, ?string $network): void
    {
        $this->info('Verifying Web3 address...');
        
        $verification = $network 
            ? $web3Service->verifyAddressOnNetwork($address, $network)
            : $web3Service->verifyAddress($address);

        $this->table(
            ['Property', 'Value'],
            collect($verification)->map(fn($value, $key) => [
                ucfirst($key),
                is_bool($value) ? ($value ? '✅ Yes' : '❌ No') : $value
            ])
        );
    }
} 