<?php

namespace App\Console\Commands\.sniffing;

require_once base_path('vendor/autoload.php');

use Illuminate\Console\Command;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SniffingAnalyzeCommand extends Command
{
    protected $signature = 'sniffing:analyze 
        {--format=html : Output format (html, json, markdown)}
        {--file= : Specific file to analyze}
        {--dir= : Directory to analyze}
        {--standard=ServiceLearning : Coding standard to use}
        {--report-dir=storage/sniffing/reports : Directory for reports}
        {--coverage : Generate coverage report}';

    protected $description = 'Analyze code using PHP_CodeSniffer and generate reports';

    public function handle()
    {
        try {
            $this->info('Starting code analysis...');

            // Initialize PHP_CodeSniffer
            $runner = new Runner();
            $config = new Config();

            // Set up configuration
            $config->standards = [$this->option('standard')];
            $config->reports = [$this->option('format')];
            $config->reportFile = $this->getReportPath();
            $config->colors = true;
            $config->showProgress = true;
            $config->showSources = true;
            $config->verbosity = 1;

            // Set up files to analyze
            $files = $this->getFilesToAnalyze();
            if (empty($files)) {
                $this->error('No files found to analyze.');
                return 1;
            }

            // Run analysis
            $runner->config = $config;
            $runner->init();
            $runner->run();

            // Process results
            $this->processResults($runner);

            // Generate coverage report if requested
            if ($this->option('coverage')) {
                $this->generateCoverageReport($files);
            }

            $this->info('Analysis completed successfully.');
            $reportPath = $this->getReportPath();
            if (File::exists($reportPath)) {
                $this->info('Report successfully written to: ' . $reportPath);
            } else {
                $this->error('Report could not be written to: ' . $reportPath);
                Log::error('Sniffing report write failed', [
                    'report_path' => $reportPath
                ]);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Analysis failed: ' . $e->getMessage());
            Log::error('Sniffing analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function getFilesToAnalyze(): array
    {
        $files = [];

        if ($file = $this->option('file')) {
            if (file_exists($file)) {
                $files[] = $file;
            } else {
                $this->error("File not found: {$file}");
            }
        } elseif ($dir = $this->option('dir')) {
            $files = $this->findPhpFiles($dir);
        } else {
            // Default to analyzing app directory
            $files = $this->findPhpFiles(base_path('app'));
        }

        return $files;
    }

    protected function findPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    protected function getReportPath(): string
    {
        $format = $this->option('format');
        $reportDir = $this->option('report-dir') ?: 'storage/sniffing/reports';
        $timestamp = date('Y-m-d_H-i-s');

        if (!File::exists($reportDir)) {
            File::makeDirectory($reportDir, 0755, true);
        }

        return "{$reportDir}/report_{$timestamp}.{$format}";
    }

    protected function processResults(Runner $runner): void
    {
        $report = $runner->reporting->getReport();
        $violations = $report->getViolations();

        // Store results in database
        foreach ($violations as $violation) {
            $this->storeViolation($violation);
        }

        // Generate summary
        $this->generateSummary($violations);
    }

    protected function storeViolation(array $violation): void
    {
        // Store violation in database
        \App\Models\SniffViolation::create([
            'file_path' => $violation['file'],
            'line' => $violation['line'],
            'column' => $violation['column'],
            'type' => $violation['type'],
            'message' => $violation['message'],
            'source' => $violation['source'],
            'severity' => $violation['severity'],
            'fixable' => $violation['fixable'],
            'context' => json_encode($violation['context'] ?? []),
        ]);
    }

    protected function generateSummary(array $violations): void
    {
        $summary = [
            'total' => count($violations),
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'by_type' => [],
            'by_file' => [],
        ];

        foreach ($violations as $violation) {
            $severity = strtolower($violation['severity']);
            $summary[$severity]++;

            // Count by type
            $type = $violation['type'];
            if (!isset($summary['by_type'][$type])) {
                $summary['by_type'][$type] = 0;
            }
            $summary['by_type'][$type]++;

            // Count by file
            $file = $violation['file'];
            if (!isset($summary['by_file'][$file])) {
                $summary['by_file'][$file] = 0;
            }
            $summary['by_file'][$file]++;
        }

        // Store summary in database
        \App\Models\SniffResult::create([
            'total_violations' => $summary['total'],
            'error_count' => $summary['errors'],
            'warning_count' => $summary['warnings'],
            'info_count' => $summary['info'],
            'summary' => json_encode($summary),
            'report_path' => $this->getReportPath(),
        ]);

        // Display summary
        $this->info("\nAnalysis Summary:");
        $this->info("Total violations: {$summary['total']}");
        $this->info("Errors: {$summary['errors']}");
        $this->info("Warnings: {$summary['warnings']}");
        $this->info("Info: {$summary['info']}");
    }

    protected function generateCoverageReport(array $files): void
    {
        $coverage = [
            'total_files' => count($files),
            'analyzed_files' => 0,
            'violations_by_file' => [],
            'standards_coverage' => [],
        ];

        // Calculate coverage metrics
        foreach ($files as $file) {
            $violations = \App\Models\SniffViolation::where('file_path', $file)->get();
            $coverage['analyzed_files']++;
            $coverage['violations_by_file'][$file] = $violations->count();

            // Calculate standards coverage
            foreach ($violations as $violation) {
                $standard = $violation['source'];
                if (!isset($coverage['standards_coverage'][$standard])) {
                    $coverage['standards_coverage'][$standard] = 0;
                }
                $coverage['standards_coverage'][$standard]++;
            }
        }

        // Calculate coverage percentage
        $coverage['coverage_percentage'] = ($coverage['analyzed_files'] / $coverage['total_files']) * 100;

        // Store coverage report
        $reportPath = $this->getReportPath();
        $coveragePath = dirname($reportPath) . '/coverage_' . basename($reportPath);
        File::put($coveragePath, json_encode($coverage, JSON_PRETTY_PRINT));

        $this->info("\nCoverage Report:");
        $this->info("Files analyzed: {$coverage['analyzed_files']} / {$coverage['total_files']}");
        $this->info("Coverage: {$coverage['coverage_percentage']}%");
        $this->info("Coverage report generated at: {$coveragePath}");
    }
} 