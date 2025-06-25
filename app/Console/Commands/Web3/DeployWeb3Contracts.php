<?php

namespace App\Console\Commands\.web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeployWeb3Contracts extends Command
{
    protected $signature = 'web3:deploy {--network=hardhat : Network to deploy to (hardhat, localhost, testnet, mainnet)}';
    protected $description = 'Deploy Web3 smart contracts to the specified network';

    public function handle()
    {
        $network = $this->option('network');
        $this->info("Deploying contracts to {$network} network...");

        try {
            // Ensure .env file exists with required variables
            $this->validateEnvironment();

            // Run deployment script
            $command = "cd " . base_path('.web3') . " && npx hardhat run scripts/deploy.js --network {$network}";
            $output = shell_exec($command);

            if ($output) {
                $this->info("\nDeployment output:");
                $this->line($output);

                // Save deployment info
                $this->saveDeploymentInfo($network, $output);
            } else {
                $this->error('Deployment failed: No output received');
                return 1;
            }

            $this->info("\nContract deployment completed successfully!");
        } catch (\Exception $error) {
            $this->error("Deployment failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function validateEnvironment()
    {
        $requiredVars = [
            'PRIVATE_KEY',
            'INFURA_API_KEY',
            'ETHERSCAN_API_KEY'
        ];

        $missingVars = [];
        foreach ($requiredVars as $var) {
            if (!env($var)) {
                $missingVars[] = $var;
            }
        }

        if (!empty($missingVars)) {
            throw new \Exception(
                "Missing required environment variables: " . implode(', ', $missingVars) . "\n" .
                "Please add them to your .env file."
            );
        }
    }

    protected function saveDeploymentInfo($network, $output)
    {
        $deploymentsDir = base_path('.web3/deployments');
        if (!File::exists($deploymentsDir)) {
            File::makeDirectory($deploymentsDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $deploymentFile = $deploymentsDir . "/deployment_{$network}_{$timestamp}.json";

        // Extract contract addresses from output
        preg_match_all('/Contract (.+?) deployed to: (.+?)(?:\n|$)/', $output, $matches, PREG_SET_ORDER);
        
        $deploymentInfo = [
            'network' => $network,
            'timestamp' => now()->toIso8601String(),
            'contracts' => []
        ];

        foreach ($matches as $match) {
            $deploymentInfo['contracts'][] = [
                'name' => $match[1],
                'address' => $match[2]
            ];
        }

        File::put($deploymentFile, json_encode($deploymentInfo, JSON_PRETTY_PRINT));
        $this->info("\nDeployment information saved to: {$deploymentFile}");
    }
} 