<?php

namespace App\Console\Commands\Web3\Test;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunPerformanceTestsCommand extends Command
{
    protected $signature = 'web3:test:performance
                          {--contract= : Specific contract to test}
                          {--duration=30 : Test duration in seconds}
                          {--connections=100 : Number of concurrent connections}
                          {--report : Generate performance report}
                          {--compare : Compare with previous results}';

    protected $description = 'Run performance tests for smart contracts';

    protected $web3Path;
    protected $reportsPath;

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->reportsPath = base_path('.web3/reports/performance');
    }

    public function handle()
    {
        $contract = $this->option('contract');
        $duration = $this->option('duration');
        $connections = $this->option('connections');
        $generateReport = $this->option('report');
        $compareResults = $this->option('compare');

        $this->info('Running performance tests...');

        // Create reports directory if it doesn't exist
        if (!File::exists($this->reportsPath)) {
            File::makeDirectory($this->reportsPath, 0755, true);
        }

        // Build performance test command
        $command = "node scripts/run-performance-tests.js --duration {$duration} --connections {$connections}";
        
        if ($contract) {
            $command .= " --contract {$contract}";
        }

        if ($compareResults) {
            $command .= ' --compare';
        }

        // Execute performance tests
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Performance tests failed: {$process->getErrorOutput()}");
            return 1;
        }

        // Generate report if requested
        if ($generateReport) {
            $this->generatePerformanceReport();
        }

        $this->info('Performance tests completed successfully!');
        return 0;
    }

    protected function generatePerformanceReport()
    {
        $this->info('Generating performance report...');
        
        $process = Process::fromShellCommandline(
            'node scripts/generate-test-report.js --type performance',
            $this->web3Path
        );
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to generate performance report: {$process->getErrorOutput()}");
            return;
        }

        $this->info('Performance report generated successfully!');
    }
} 