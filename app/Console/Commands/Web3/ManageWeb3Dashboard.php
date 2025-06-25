<?php

namespace App\Console\Commands\.web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageWeb3Dashboard extends Command
{
    protected $signature = 'web3:dashboard {action : Action to perform (deploy, update, status)}';
    protected $description = 'Manage the Web3 dashboard';

    protected $dashboardDir;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardDir = base_path('.web3/dashboard');
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'deploy':
                $this->deployDashboard();
                break;
            case 'update':
                $this->updateDashboard();
                break;
            case 'status':
                $this->checkDashboardStatus();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function deployDashboard()
    {
        $this->info('Deploying Web3 dashboard...');

        try {
            // Ensure dashboard directory exists
            if (!File::exists($this->dashboardDir)) {
                File::makeDirectory($this->dashboardDir, 0755, true);
            }

            // Get latest deployment info
            $deploymentInfo = $this->getLatestDeploymentInfo();
            if (!$deploymentInfo) {
                throw new \Exception('No deployment information found. Please deploy contracts first.');
            }

            // Generate dashboard configuration
            $this->generateDashboardConfig($deploymentInfo);

            // Build and deploy dashboard
            $command = "cd " . $this->dashboardDir . " && npm run build && npm run deploy";
            $output = shell_exec($command);

            if ($output) {
                $this->info("\nDeployment output:");
                $this->line($output);
                $this->info("\nDashboard deployed successfully!");
            } else {
                throw new \Exception('Dashboard deployment failed: No output received');
            }
        } catch (\Exception $error) {
            $this->error("Dashboard deployment failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function updateDashboard()
    {
        $this->info('Updating Web3 dashboard...');

        try {
            // Pull latest changes
            $command = "cd " . $this->dashboardDir . " && git pull";
            $output = shell_exec($command);

            if (!$output) {
                throw new \Exception('Failed to pull latest changes');
            }

            // Install dependencies
            $command = "cd " . $this->dashboardDir . " && npm install";
            $output = shell_exec($command);

            if (!$output) {
                throw new \Exception('Failed to install dependencies');
            }

            // Rebuild and redeploy
            $this->deployDashboard();
        } catch (\Exception $error) {
            $this->error("Dashboard update failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function checkDashboardStatus()
    {
        $this->info('Checking Web3 dashboard status...');

        try {
            // Check if dashboard is running
            $command = "cd " . $this->dashboardDir . " && npm run status";
            $output = shell_exec($command);

            if ($output) {
                $this->info("\nDashboard status:");
                $this->line($output);
            } else {
                $this->warn('Dashboard status check failed: No output received');
            }

            // Check deployment info
            $deploymentInfo = $this->getLatestDeploymentInfo();
            if ($deploymentInfo) {
                $this->info("\nLatest deployment information:");
                $this->line("Network: " . $deploymentInfo['network']);
                $this->line("Timestamp: " . $deploymentInfo['timestamp']);
                $this->line("\nDeployed contracts:");
                foreach ($deploymentInfo['contracts'] as $contract) {
                    $this->line("- {$contract['name']}: {$contract['address']}");
                }
            } else {
                $this->warn('No deployment information found');
            }
        } catch (\Exception $error) {
            $this->error("Status check failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function getLatestDeploymentInfo()
    {
        $deploymentsDir = base_path('.web3/deployments');
        if (!File::exists($deploymentsDir)) {
            return null;
        }

        $files = collect(File::files($deploymentsDir))
            ->filter(function ($file) {
                return str_ends_with($file->getFilename(), '.json');
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            });

        if ($files->isEmpty()) {
            return null;
        }

        $latestFile = $files->first();
        return json_decode(File::get($latestFile), true);
    }

    protected function generateDashboardConfig($deploymentInfo)
    {
        $config = [
            'network' => $deploymentInfo['network'],
            'contracts' => $deploymentInfo['contracts'],
            'lastUpdated' => now()->toIso8601String()
        ];

        $configFile = $this->dashboardDir . '/config.json';
        File::put($configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->info("Dashboard configuration generated: {$configFile}");
    }
} 