<?php

namespace App\Console\Commands\.web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageWeb3Environment extends Command
{
    protected $signature = 'web3:env {action : Action to perform (setup, validate, update)}';
    protected $description = 'Manage Web3 environment and configuration';

    protected $web3Dir;
    protected $requiredEnvVars = [
        'PRIVATE_KEY',
        'INFURA_API_KEY',
        'ETHERSCAN_API_KEY',
        'NETWORK_RPC_URL',
        'NETWORK_CHAIN_ID'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->web3Dir = base_path('.web3');
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'setup':
                $this->setupEnvironment();
                break;
            case 'validate':
                $this->validateEnvironment();
                break;
            case 'update':
                $this->updateEnvironment();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function setupEnvironment()
    {
        $this->info('Setting up Web3 environment...');

        try {
            // Create .web3 directory if it doesn't exist
            if (!File::exists($this->web3Dir)) {
                File::makeDirectory($this->web3Dir, 0755, true);
            }

            // Create hardhat.config.js if it doesn't exist
            $this->createHardhatConfig();

            // Create .env.example if it doesn't exist
            $this->createEnvExample();

            // Install dependencies
            $this->installDependencies();

            $this->info('Web3 environment setup completed successfully!');
        } catch (\Exception $error) {
            $this->error("Setup failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function validateEnvironment()
    {
        $this->info('Validating Web3 environment...');

        try {
            // Check required directories
            $this->validateDirectories();

            // Check required files
            $this->validateFiles();

            // Check environment variables
            $this->validateEnvVars();

            // Check dependencies
            $this->validateDependencies();

            $this->info('Web3 environment validation completed successfully!');
        } catch (\Exception $error) {
            $this->error("Validation failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function updateEnvironment()
    {
        $this->info('Updating Web3 environment...');

        try {
            // Update dependencies
            $this->updateDependencies();

            // Update configuration files
            $this->updateConfigFiles();

            $this->info('Web3 environment update completed successfully!');
        } catch (\Exception $error) {
            $this->error("Update failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function createHardhatConfig()
    {
        $configFile = $this->web3Dir . '/hardhat.config.js';
        if (!File::exists($configFile)) {
            $config = <<<'EOT'
require("@nomicfoundation/hardhat-toolbox");
require("hardhat-gas-reporter");
require("solidity-coverage");
require("dotenv").config();

module.exports = {
  solidity: "0.8.20",
  networks: {
    hardhat: {},
    localhost: {
      url: "http://127.0.0.1:8545"
    },
    testnet: {
      url: process.env.NETWORK_RPC_URL,
      chainId: parseInt(process.env.NETWORK_CHAIN_ID),
      accounts: [process.env.PRIVATE_KEY]
    }
  },
  gasReporter: {
    enabled: process.env.REPORT_GAS !== undefined,
    currency: "USD",
    coinmarketcap: process.env.COINMARKETCAP_API_KEY
  },
  etherscan: {
    apiKey: process.env.ETHERSCAN_API_KEY
  },
  paths: {
    sources: "./contracts",
    tests: "./tests",
    cache: "./cache",
    artifacts: "./artifacts"
  },
  mocha: {
    timeout: 40000
  }
};
EOT;
            File::put($configFile, $config);
            $this->info('Created hardhat.config.js');
        }
    }

    protected function createEnvExample()
    {
        $envFile = $this->web3Dir . '/.env.example';
        if (!File::exists($envFile)) {
            $env = <<<'EOT'
# Network Configuration
NETWORK_RPC_URL=https://your-network-rpc-url
NETWORK_CHAIN_ID=1337

# API Keys
PRIVATE_KEY=your-private-key
INFURA_API_KEY=your-infura-api-key
ETHERSCAN_API_KEY=your-etherscan-api-key
COINMARKETCAP_API_KEY=your-coinmarketcap-api-key

# Gas Reporting
REPORT_GAS=true
EOT;
            File::put($envFile, $env);
            $this->info('Created .env.example');
        }
    }

    protected function installDependencies()
    {
        $command = "cd {$this->web3Dir} && npm install";
        $output = shell_exec($command);
        if (!$output) {
            throw new \Exception('Failed to install dependencies');
        }
        $this->info('Installed dependencies');
    }

    protected function validateDirectories()
    {
        $requiredDirs = [
            'contracts',
            'tests',
            'scripts',
            'deployments',
            'dashboard'
        ];
        foreach ($requiredDirs as $dir) {
            $fullPath = $this->web3Dir . '/' . $dir;
            if (!File::exists($fullPath)) {
                throw new \Exception("Missing required directory: {$fullPath}");
            }
        }
    }

    protected function validateFiles()
    {
        $requiredFiles = [
            'hardhat.config.js',
            '.env.example'
        ];
        foreach ($requiredFiles as $file) {
            $fullPath = $this->web3Dir . '/' . $file;
            if (!File::exists($fullPath)) {
                throw new \Exception("Missing required file: {$fullPath}");
            }
        }
    }

    protected function validateEnvVars()
    {
        foreach ($this->requiredEnvVars as $var) {
            if (!env($var)) {
                throw new \Exception("Missing required environment variable: {$var}");
            }
        }
    }

    protected function validateDependencies()
    {
        $packageFile = $this->web3Dir . '/package.json';
        if (!File::exists($packageFile)) {
            throw new \Exception('Missing package.json');
        }
        $this->info('Dependencies validated');
    }

    protected function updateDependencies()
    {
        $command = "cd {$this->web3Dir} && npm update";
        $output = shell_exec($command);
        if (!$output) {
            throw new \Exception('Failed to update dependencies');
        }
        $this->info('Dependencies updated');
    }

    protected function updateConfigFiles()
    {
        $this->createHardhatConfig();
        $this->createEnvExample();
        $this->info('Configuration files updated');
    }
} 