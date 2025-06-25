<?php

namespace App\Console\Commands\.web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageWeb3Contracts extends Command
{
    protected $signature = 'web3:contracts {action : Action to perform (compile, verify, clean)} {--network=hardhat : Network to use} {--contract= : Specific contract to compile/verify}';
    protected $description = 'Manage Web3 smart contract compilation and verification';

    protected $web3Dir;
    protected $contractsDir;
    protected $artifactsDir;

    public function __construct()
    {
        parent::__construct();
        $this->web3Dir = base_path('.web3');
        $this->contractsDir = $this->web3Dir . '/contracts';
        $this->artifactsDir = $this->web3Dir . '/artifacts';
    }

    public function handle()
    {
        $action = $this->argument('action');
        $network = $this->option('network');
        $contract = $this->option('contract');

        switch ($action) {
            case 'compile':
                $this->compileContracts($contract);
                break;
            case 'verify':
                $this->verifyContracts($network, $contract);
                break;
            case 'clean':
                $this->cleanArtifacts();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function compileContracts($specificContract = null)
    {
        $this->info('Compiling smart contracts...');

        try {
            $command = "cd {$this->web3Dir} && npx hardhat compile";
            if ($specificContract) {
                $command .= " --contract {$specificContract}";
            }

            $output = shell_exec($command);

            if ($output) {
                $this->info("\nCompilation output:");
                $this->line($output);

                // Save compilation info
                $this->saveCompilationInfo($output);
            } else {
                throw new \Exception('Compilation failed: No output received');
            }

            $this->info("\nContract compilation completed successfully!");
        } catch (\Exception $error) {
            $this->error("Compilation failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function verifyContracts($network, $specificContract = null)
    {
        $this->info("Verifying contracts on {$network} network...");

        try {
            // Get deployment info
            $deploymentInfo = $this->getLatestDeploymentInfo($network);
            if (!$deploymentInfo) {
                throw new \Exception("No deployment information found for {$network} network");
            }

            foreach ($deploymentInfo['contracts'] as $contract) {
                if ($specificContract && $contract['name'] !== $specificContract) {
                    continue;
                }

                $this->info("\nVerifying {$contract['name']}...");
                $command = "cd {$this->web3Dir} && npx hardhat verify --network {$network} {$contract['address']}";
                $output = shell_exec($command);

                if ($output) {
                    $this->info("Verification output:");
                    $this->line($output);
                } else {
                    $this->warn("Verification failed for {$contract['name']}: No output received");
                }
            }

            $this->info("\nContract verification completed!");
        } catch (\Exception $error) {
            $this->error("Verification failed: " . $error->getMessage());
            return 1;
        }
    }

    protected function cleanArtifacts()
    {
        $this->info('Cleaning contract artifacts...');

        try {
            // Clean artifacts directory
            if (File::exists($this->artifactsDir)) {
                File::deleteDirectory($this->artifactsDir);
                File::makeDirectory($this->artifactsDir, 0755, true);
            }

            // Clean cache directory
            $cacheDir = $this->web3Dir . '/cache';
            if (File::exists($cacheDir)) {
                File::deleteDirectory($cacheDir);
                File::makeDirectory($cacheDir, 0755, true);
            }

            $this->info('Artifacts cleaned successfully!');
        } catch (\Exception $error) {
            $this->error("Failed to clean artifacts: " . $error->getMessage());
            return 1;
        }
    }

    protected function saveCompilationInfo($output)
    {
        $reportsDir = $this->web3Dir . '/reports';
        if (!File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportFile = $reportsDir . "/compilation_{$timestamp}.json";

        // Parse compilation output
        preg_match_all('/Compiled (\d+) Solidity files? successfully/', $output, $matches);
        $filesCompiled = $matches[1][0] ?? 0;

        $compilationInfo = [
            'timestamp' => now()->toIso8601String(),
            'files_compiled' => $filesCompiled,
            'output' => $output
        ];

        File::put($reportFile, json_encode($compilationInfo, JSON_PRETTY_PRINT));
        $this->info("\nCompilation report saved to: {$reportFile}");
    }

    protected function getLatestDeploymentInfo($network)
    {
        $deploymentsDir = $this->web3Dir . '/deployments';
        if (!File::exists($deploymentsDir)) {
            return null;
        }

        $files = collect(File::files($deploymentsDir))
            ->filter(function ($file) use ($network) {
                return str_contains($file->getFilename(), "deployment_{$network}_");
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
} 