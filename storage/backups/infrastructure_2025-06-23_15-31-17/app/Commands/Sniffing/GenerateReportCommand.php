<?php

namespace App\Commands\Sniffing;

use Illuminate\Console\Command;
use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class GenerateReportCommand extends Command
{
    protected $signature = 'sniff:generate-report
                                {--file= : Generate report for specific file}
                                {--latest : Generate report for latest results}
                                {--limit=10 : Number of results to include}
                                {--format=html : Report format (html, markdown, json, txt)}
                                {--output= : Output file path}
                                {--trends : Include trend analysis}
                                {--severity= : Filter by severity (error, warning, info)}
                                {--date-range= : Date range (e.g., "2024-01-01,2024-12-31")}';

    protected $description = 'Generate a comprehensive report of sniffing results';

    private SniffResultRepository $repository;

    public function __construct(SniffResultRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle()
    {
        $results = $this->getResults();
        
        if ($results->isEmpty()) {
            $this->info('No sniffing results found.');
            return 0;
        }

        $report = $this->generateReport($results);
        $this->saveReport($report);
        
        return 0;
    }

    private function getResults()
    {
        $file = $this->option('file');
        $latest = $this->option('latest');
        $limit = $this->option('limit');
        $severity = $this->option('severity');
        $dateRange = $this->option('date-range');

        if ($file) {
            return $this->repository->getByFile($file);
        }

        if ($latest) {
            return $this->repository->getLatestResults($limit);
        }

        if ($dateRange) {
            [$startDate, $endDate] = explode(',', $dateRange);
            return $this->repository->getResultsByDateRange($startDate, $endDate);
        }

        if ($severity) {
            return $this->repository->getResultsBySeverity($severity);
        }

        return $this->repository->getAll();
    }

    private function generateReport($results): string
    {
        $format = $this->option('format');
        $includeTrends = $this->option('trends');
        
        $data = [
            'results' => $results,
            'statistics' => $this->repository->getStatistics(),
            'trends' => $includeTrends ? $this->repository->getTrendData() : null,
            'generated_at' => now(),
        ];

        return match($format) {
            'html' => $this->generateHtmlReport($data),
            'markdown' => $this->generateMarkdownReport($data),
            'json' => $this->generateJsonReport($data),
            default => $this->generateTextReport($data),
        };
    }

    private function generateHtmlReport(array $data): string
    {
        return View::make('sniffing.reports.html', $data)->render();
    }

    private function generateMarkdownReport(array $data): string
    {
        return View::make('sniffing.reports.markdown', $data)->render();
    }

    private function generateJsonReport(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function generateTextReport(array $data): string
    {
        $output = "Sniffing Report - " . $data['generated_at']->format('Y-m-d H:i:s') . "\n\n";
        
        // Add statistics
        $output .= "Statistics:\n";
        $output .= "-----------\n";
        foreach ($data['statistics'] as $key => $value) {
            $output .= ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
        
        // Add results
        $output .= "\nResults:\n";
        $output .= "--------\n";
        foreach ($data['results'] as $result) {
            $output .= "\nFile: " . $result->file_path . "\n";
            $output .= "Date: " . $result->sniff_date->format('Y-m-d H:i:s') . "\n";
            $output .= "Status: " . $result->status . "\n";
            $output .= "Errors: " . $result->error_count . "\n";
            $output .= "Warnings: " . $result->warning_count . "\n";
            
            if ($result->violations->isNotEmpty()) {
                $output .= "\nViolations:\n";
                foreach ($result->violations as $violation) {
                    $output .= "- {$violation->message} ({$violation->severity})\n";
                    $output .= "  Location: {$violation->formatted_location}\n";
                    $output .= "  Rule: {$violation->rule_name}\n";
                }
            }
            
            $output .= "\n" . str_repeat("-", 70) . "\n";
        }
        
        // Add trends if available
        if ($data['trends']) {
            $output .= "\nTrends:\n";
            $output .= "-------\n";
            foreach ($data['trends'] as $trend) {
                $output .= "Date: {$trend['date']}\n";
                $output .= "Total Runs: {$trend['total_runs']}\n";
                $output .= "Total Errors: {$trend['total_errors']}\n";
                $output .= "Total Warnings: {$trend['total_warnings']}\n\n";
            }
        }
        
        return $output;
    }

    private function saveReport(string $report): void
    {
        $format = $this->option('format');
        $extension = match($format) {
            'html' => 'html',
            'markdown' => 'md',
            'json' => 'json',
            default => 'txt',
        };
        
        $outputPath = $this->option('output') ?? 
            storage_path("reports/sniffing_report_{$format}_" . date('Y-m-d_H-i-s') . ".{$extension}");
        
        File::put($outputPath, $report);
        $this->info("Report generated at: {$outputPath}");
    }
}
