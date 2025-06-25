<?php

namespace App\Console\Commands\Web3\Test;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunWeb3TestsCommand extends Command
{
    protected $signature = 'web3:test:run
                          {--type=all : Type of tests to run (all, unit, integration, performance)}
                          {--contract= : Specific contract to test}
                          {--coverage : Generate coverage report}
                          {--report : Generate test report}';

    protected $description = 'Run Web3 tests including smart contract tests and performance tests';

    protected $web3Path;
    protected $reportsPath;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->reportsPath = base_path('.web3/reports');
    }

    public function handle()
    {
        $testType = $this->option('type');
        $contract = $this->option('contract');
        $withCoverage = $this->option('coverage');
        $withReport = $this->option('report');

        $this->info("Running Web3 tests...");

        // Create reports directory if it doesn't exist
        if (!File::exists($this->reportsPath)) {
            File::makeDirectory($this->reportsPath, 0755, true);
        }

        // Build test command based on type
        $command = $this->buildTestCommand($testType, $contract, $withCoverage);

        // Execute tests
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Tests failed: {$process->getErrorOutput()}");
            return 1;
        }

        // Generate report if requested
        if ($withReport) {
            $this->generateTestReport();
        }

        $this->info("Tests completed successfully!");
        return 0;
    }

    protected function buildTestCommand($type, $contract, $withCoverage)
    {
        $command = 'npx hardhat test';

        if ($contract) {
            $command .= " test/{$contract}.test.js";
        }

        if ($withCoverage) {
            $command .= ' --coverage';
        }

        switch ($type) {
            case 'unit':
                $command .= ' test/unit';
                break;
            case 'integration':
                $command .= ' test/integration';
                break;
            case 'performance':
                $command .= ' scripts/run-performance-tests.js';
                break;
            default:
                // Run all tests
                break;
        }

        return $command;
    }

    protected function generateTestReport()
    {
        $this->info("Generating test report...");
        
        $process = Process::fromShellCommandline(
            'node scripts/generate-test-report.js',
            $this->web3Path
        );
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to generate test report: {$process->getErrorOutput()}");
            return;
        }

        $this->info("Test report generated successfully!");
    }
} 