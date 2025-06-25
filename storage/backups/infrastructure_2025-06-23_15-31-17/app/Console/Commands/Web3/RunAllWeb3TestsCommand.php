<?php

namespace App\Console\Commands\Web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunAllWeb3TestsCommand extends Command
{
    protected $signature = 'web3:run-all-tests';
    protected $description = 'Run all Web3 tests including PHPUnit, Hardhat, and performance tests';

    protected $reportsDir;
    protected $timestamp;

    public function handle()
    {
        $this->reportsDir = base_path('.web3/reports');
        $this->timestamp = now()->format('Ymd_His');

        $this->initializeDirectories();
        
        if (!$this->runPhpUnitTests()) {
            return 1;
        }

        if (!$this->runHardhatTests()) {
            return 1;
        }

        if (!$this->runPerformanceTests()) {
            return 1;
        }

        if (!$this->generateTestReports()) {
            return 1;
        }

        if (!$this->updateChecklist()) {
            return 1;
        }

        $this->printSummary();
        return 0;
    }

    protected function initializeDirectories()
    {
        $directories = [
            "{$this->reportsDir}/php",
            "{$this->reportsDir}/solidity",
            "{$this->reportsDir}/coverage",
            "{$this->reportsDir}/performance"
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    protected function runPhpUnitTests(): bool
    {
        $this->info('Running PHPUnit tests...');

        $phpUnitPath = base_path('vendor/bin/phpunit');
        $phpUnitBat = base_path('vendor/bin/phpunit.bat');
        $phpUnitCmd = '';

        if (File::exists($phpUnitBat)) {
            $phpUnitCmd = $phpUnitBat;
        } elseif (File::exists($phpUnitPath)) {
            $phpUnitCmd = "php {$phpUnitPath}";
        } else {
            $this->error('PHPUnit not found. Installing via Composer...');
            
            try {
                $process = new Process(['composer', 'require', '--dev', 'phpunit/phpunit']);
                $process->setWorkingDirectory(base_path());
                $process->run();

                if (File::exists($phpUnitBat)) {
                    $phpUnitCmd = $phpUnitBat;
                } elseif (File::exists($phpUnitPath)) {
                    $phpUnitCmd = "php {$phpUnitPath}";
                } else {
                    $this->error('Failed to install PHPUnit. Please install it manually using \'composer require --dev phpunit/phpunit\'');
                    return false;
                }
            } catch (\Exception $e) {
                $this->error("Error installing PHPUnit: {$e->getMessage()}");
                return false;
            }
        }

        try {
            $process = new Process([
                $phpUnitCmd,
                "--coverage-html", "{$this->reportsDir}/coverage/php",
                "--log-junit", "{$this->reportsDir}/php/junit.xml"
            ]);
            $process->setWorkingDirectory(base_path());
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('PHPUnit tests failed');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("Error running PHPUnit tests: {$e->getMessage()}");
            return false;
        }
    }

    protected function runHardhatTests(): bool
    {
        $this->info('Running Hardhat tests...');

        try {
            $process = new Process(['npx', 'hardhat', 'test', '--network', 'hardhat']);
            $process->setWorkingDirectory(base_path('.web3'));
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('Hardhat tests failed');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("Error running Hardhat tests: {$e->getMessage()}");
            return false;
        }
    }

    protected function runPerformanceTests(): bool
    {
        $this->info('Running performance tests...');

        try {
            $process = new Process(['node', 'scripts/run-performance-tests.js']);
            $process->setWorkingDirectory(base_path('.web3'));
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('Failed to run performance tests');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("Error running performance tests: {$e->getMessage()}");
            return false;
        }
    }

    protected function generateTestReports(): bool
    {
        $this->info('Generating test reports...');

        try {
            $process = new Process(['node', 'scripts/generate-test-report.js']);
            $process->setWorkingDirectory(base_path('.web3'));
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('Failed to generate test reports');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("Error generating test reports: {$e->getMessage()}");
            return false;
        }
    }

    protected function updateChecklist(): bool
    {
        $this->info('Updating checklist...');

        try {
            $process = new Process(['node', 'scripts/update-checklist.js']);
            $process->setWorkingDirectory(base_path('.web3'));
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('Failed to update checklist');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("Error updating checklist: {$e->getMessage()}");
            return false;
        }
    }

    protected function printSummary()
    {
        $this->info("\nTest suite completed successfully!");
        $this->info('Reports can be found in:');
        $this->info("- PHP Reports: {$this->reportsDir}/php");
        $this->info("- Solidity Reports: {$this->reportsDir}/solidity");
        $this->info("- Coverage Reports: {$this->reportsDir}/coverage");
        $this->info("- Performance Reports: {$this->reportsDir}/performance");
        $this->info("- Test Results: {$this->reportsDir}/test-results.json");
    }
} 