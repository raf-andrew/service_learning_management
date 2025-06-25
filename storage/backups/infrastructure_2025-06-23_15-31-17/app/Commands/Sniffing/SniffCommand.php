<?php

namespace App\Commands\Sniffing;

use Illuminate\Console\Command;
use PHP_CodeSniffer\CLI;
use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SniffCommand extends Command
{
    protected $signature = 'sniff:run
                                {--report=xml : Report format (xml, full, summary, json)}
                                {--file= : Specific file to sniff}
                                {--fix : Automatically fix sniff violations}
                                {--standard= : Custom standard to use}
                                {--severity= : Minimum severity level (error, warning, info)}
                                {--ignore=* : Patterns to ignore}
                                {--parallel : Run sniffing in parallel}
                                {--timeout=300 : Timeout in seconds}';

    protected $description = 'Run PHP CodeSniffer with Laravel standards';

    private SniffResultRepository $repository;
    private float $startTime;

    public function __construct(SniffResultRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle()
    {
        $this->startTime = microtime(true);
        
        try {
            $this->validateOptions();
            $result = $this->runSniffer();
            $this->storeResults($result);
            $this->displaySummary();
            
            return $result === 0 ? 0 : 1;
        } catch (\Exception $e) {
            $this->error("Error running code sniffer: {$e->getMessage()}");
            Log::error('Sniffing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function validateOptions(): void
    {
        $reportFormat = $this->option('report');
        if (!in_array($reportFormat, ['xml', 'full', 'summary', 'json'])) {
            throw new \InvalidArgumentException("Invalid report format: {$reportFormat}");
        }

        $severity = $this->option('severity');
        if ($severity && !in_array($severity, ['error', 'warning', 'info'])) {
            throw new \InvalidArgumentException("Invalid severity level: {$severity}");
        }

        $file = $this->option('file');
        if ($file && !File::exists($file)) {
            throw new \InvalidArgumentException("File not found: {$file}");
        }
    }

    private function runSniffer(): int
    {
        $cli = new CLI();
        $cli->setCommandLineValues([
            '--standard' => $this->option('standard') ?? base_path('phpcs.xml'),
            '--report' => $this->option('report'),
            '--encoding' => 'utf-8',
            '--severity' => $this->option('severity'),
            '--parallel' => $this->option('parallel'),
            '--timeout' => $this->option('timeout'),
        ]);

        if ($file = $this->option('file')) {
            $cli->setCommandLineValues(['--files' => $file]);
        }

        if ($this->option('fix')) {
            $cli->setCommandLineValues(['--fix' => true]);
        }

        if ($ignore = $this->option('ignore')) {
            $cli->setCommandLineValues(['--ignore' => $ignore]);
        }

        return $cli->process();
    }

    private function storeResults(int $result): void
    {
        $executionTime = microtime(true) - $this->startTime;
        
        $data = [
            'result_data' => $this->getResultData(),
            'report_format' => $this->option('report'),
            'file_path' => $this->option('file') ?? 'all files',
            'fix_applied' => $this->option('fix'),
            'error_count' => $this->getErrorCount(),
            'warning_count' => $this->getWarningCount(),
            'sniff_date' => now(),
            'execution_time' => $executionTime,
            'phpcs_version' => $this->getPhpcsVersion(),
            'standards_used' => [$this->option('standard') ?? 'Laravel'],
            'status' => $result === 0 ? 'success' : 'failed',
            'violations' => $this->parseViolations(),
        ];

        $this->repository->store($data);
    }

    private function getResultData(): array
    {
        // Implementation depends on the report format
        return [];
    }

    private function getErrorCount(): int
    {
        // Implementation depends on the report format
        return 0;
    }

    private function getWarningCount(): int
    {
        // Implementation depends on the report format
        return 0;
    }

    private function getPhpcsVersion(): string
    {
        return \PHP_CodeSniffer\Config::VERSION;
    }

    private function parseViolations(): array
    {
        // Implementation depends on the report format
        return [];
    }

    private function displaySummary(): void
    {
        $executionTime = number_format(microtime(true) - $this->startTime, 2);
        
        $this->info("\nSniffing Summary:");
        $this->info("----------------");
        $this->info("Execution Time: {$executionTime} seconds");
        $this->info("File: " . ($this->option('file') ?? 'All files'));
        $this->info("Report Format: " . $this->option('report'));
        $this->info("Fix Applied: " . ($this->option('fix') ? 'Yes' : 'No'));
        $this->info("Errors: " . $this->getErrorCount());
        $this->info("Warnings: " . $this->getWarningCount());
    }
}
