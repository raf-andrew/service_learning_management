<?php

namespace App\Console\Commands\.web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class RunWeb3Tests extends Command
{
    protected $signature = 'web3:test {--type=all : Type of tests to run (all, unit, integration, security, performance, e2e)}';
    protected $description = 'Run Web3 smart contract tests';

    protected $reportsDir;
    protected $testTypes = ['unit', 'integration', 'security', 'performance', 'e2e'];

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.web3/reports');
    }

    public function handle()
    {
        $type = $this->option('type');
        $this->info("Running {$type} tests...");

        try {
            // Ensure .env file exists with required variables
            $this->validateEnvironment();

            // Run tests based on type
            $command = "cd " . base_path('.web3') . " && npx hardhat test";
            if ($type !== 'all') {
                $command .= " --test-path tests/{$type}";
            }

            $output = shell_exec($command);

            if ($output) {
                $this->info("\nTest output:");
                $this->line($output);

                // Save test results
                $this->saveTestResults($type, $output);
            } else {
                $this->error('Test execution failed: No output received');
                return 1;
            }

            $this->info("\nTests completed successfully!");
        } catch (\Exception $error) {
            $this->error("Test execution failed: " . $error->getMessage());
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

    protected function saveTestResults($type, $output)
    {
        $reportsDir = base_path('.web3/reports');
        if (!File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportFile = $reportsDir . "/test_results_{$type}_{$timestamp}.json";

        // Parse test results
        preg_match_all('/✓ (.+?) \((\d+)ms\)/', $output, $passedMatches, PREG_SET_ORDER);
        preg_match_all('/✗ (.+?) \((\d+)ms\)/', $output, $failedMatches, PREG_SET_ORDER);

        $testResults = [
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
            'passed' => [],
            'failed' => []
        ];

        foreach ($passedMatches as $match) {
            $testResults['passed'][] = [
                'name' => $match[1],
                'duration' => $match[2]
            ];
        }

        foreach ($failedMatches as $match) {
            $testResults['failed'][] = [
                'name' => $match[1],
                'duration' => $match[2]
            ];
        }

        File::put($reportFile, json_encode($testResults, JSON_PRETTY_PRINT));
        $this->info("\nTest results saved to: {$reportFile}");
    }
} 