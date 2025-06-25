<?php

namespace App\Console\Commands\Web3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunWeb3TestsCommand extends Command
{
    protected $signature = 'web3:run-tests';
    protected $description = 'Run Web3 test suite including PHPUnit and Hardhat tests';

    protected $reportsDir;
    protected $testResultsDir;
    protected $timestamp;

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.web3/reports');
        $this->testResultsDir = base_path('.web3/test-results');
        $this->timestamp = date('Ymd_His');
    }

    public function handle()
    {
        $this->info('🚀 Starting test suite execution...');

        // Create necessary directories
        $this->createDirectories();

        // Run PHPUnit tests
        if (!$this->runPhpUnitTests()) {
            return 1;
        }

        // Run Hardhat tests
        if (!$this->runHardhatTests()) {
            return 1;
        }

        // Generate test reports
        if (!$this->generateTestReports()) {
            return 1;
        }

        // Update checklist
        if (!$this->updateChecklist()) {
            return 1;
        }

        // Print summary
        $this->printSummary();

        $this->info('✨ All tests completed successfully!');
        return 0;
    }

    protected function createDirectories()
    {
        File::makeDirectory($this->reportsDir, 0755, true, true);
        File::makeDirectory($this->testResultsDir, 0755, true, true);
    }

    protected function runPhpUnitTests()
    {
        $this->info('Running PHPUnit tests...');

        $process = new Process([
            'vendor/bin/phpunit',
            '--configuration',
            '.web3/tests/phpunit.xml',
            '--coverage-html',
            $this->reportsDir . '/coverage/php',
            '--log-junit',
            $this->reportsDir . '/phpunit-' . $this->timestamp . '.xml'
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            $this->info('✓ PHPUnit tests completed successfully');
            return true;
        }

        $this->error('✗ PHPUnit tests failed');
        $this->error($process->getErrorOutput());
        return false;
    }

    protected function runHardhatTests()
    {
        $this->info('Running Hardhat tests...');

        $process = new Process(['npx', 'hardhat', 'test', '--report'], base_path('.web3'));
        $process->setOutputFile($this->reportsDir . '/hardhat-' . $this->timestamp . '.json');
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('✓ Hardhat tests completed successfully');
            return true;
        }

        $this->error('✗ Hardhat tests failed');
        $this->error($process->getErrorOutput());
        return false;
    }

    protected function generateTestReports()
    {
        $this->info('Generating test reports...');

        $process = new Process(['node', 'scripts/generate-test-report.js'], base_path('.web3'));
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('✓ Test reports generated successfully');
            return true;
        }

        $this->error('✗ Failed to generate test reports');
        $this->error($process->getErrorOutput());
        return false;
    }

    protected function updateChecklist()
    {
        $this->info('Updating checklist...');

        $process = new Process(['node', 'scripts/update-checklist.js'], base_path('.web3'));
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('✓ Checklist updated successfully');
            return true;
        }

        $this->error('✗ Failed to update checklist');
        $this->error($process->getErrorOutput());
        return false;
    }

    protected function printSummary()
    {
        $this->info('Test Suite Summary:');
        $this->line('----------------------------------------');
        $this->line('PHPUnit Reports: ' . $this->reportsDir . '/phpunit-' . $this->timestamp . '.xml');
        $this->line('Hardhat Reports: ' . $this->reportsDir . '/hardhat-' . $this->timestamp . '.json');
        $this->line('Coverage Reports: ' . $this->reportsDir . '/coverage/');
        $this->line('Test Results: ' . $this->reportsDir . '/test-results.json');
        $this->line('----------------------------------------');
    }
} 