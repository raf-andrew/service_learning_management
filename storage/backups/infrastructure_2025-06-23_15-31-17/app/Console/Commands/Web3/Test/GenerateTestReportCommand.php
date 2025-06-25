<?php

namespace App\Console\Commands\Web3\Test;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GenerateTestReportCommand extends Command
{
    protected $signature = 'web3:test:report
                          {--type=all : Type of report to generate (all, unit, integration, performance)}
                          {--format=html : Report format (html, json, markdown)}
                          {--output=reports : Output directory for reports}
                          {--coverage : Include coverage information}';

    protected $description = 'Generate test reports for Web3 tests';

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
        $type = $this->option('type');
        $format = $this->option('format');
        $output = $this->option('output');
        $includeCoverage = $this->option('coverage');

        $this->info("Generating {$type} test report in {$format} format...");

        // Create reports directory if it doesn't exist
        if (!File::exists($this->reportsPath)) {
            File::makeDirectory($this->reportsPath, 0755, true);
        }

        // Build report generation command
        $command = "node scripts/generate-test-report.js --type {$type} --format {$format} --output {$output}";
        
        if ($includeCoverage) {
            $command .= ' --coverage';
        }

        // Execute report generation
        $process = Process::fromShellCommandline($command, $this->web3Path);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to generate report: {$process->getErrorOutput()}");
            return 1;
        }

        // Generate coverage summary if requested
        if ($includeCoverage) {
            $this->generateCoverageSummary();
        }

        $this->info("Test report generated successfully in {$this->reportsPath}/{$output}");
        return 0;
    }

    protected function generateCoverageSummary()
    {
        $this->info('Generating coverage summary...');
        
        $process = Process::fromShellCommandline(
            'php scripts/generate_coverage_summary.php',
            $this->web3Path
        );
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to generate coverage summary: {$process->getErrorOutput()}");
            return;
        }

        $this->info('Coverage summary generated successfully!');
    }
} 