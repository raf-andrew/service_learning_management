<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class AnalyzeSniffingResultsCommand extends Command
{
    protected $signature = 'sniffing:analyze
                            {--days=7 : Number of days to analyze}
                            {--type=* : Types of issues to analyze (error, warning, info)}
                            {--format=html : Output format (html, markdown, json)}
                            {--output= : Output file path}
                            {--trends : Include trend analysis}
                            {--files : Include file-specific analysis}
                            {--rules : Include rule-specific analysis}';

    protected $description = 'Analyze sniffing results and generate reports';

    private SniffResultRepository $repository;

    public function __construct(SniffResultRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle()
    {
        $days = (int) $this->option('days');
        $types = $this->option('type') ?: ['error', 'warning', 'info'];
        $format = $this->option('format');
        $includeTrends = $this->option('trends');
        $includeFiles = $this->option('files');
        $includeRules = $this->option('rules');

        $results = $this->repository->getResultsByDateRange(
            now()->subDays($days)->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        if ($results->isEmpty()) {
            $this->info('No results found for the specified period.');
            return 0;
        }

        $analysis = $this->analyzeResults($results, $types, $includeTrends, $includeFiles, $includeRules);
        $report = $this->generateReport($analysis, $format);
        
        $this->saveReport($report, $format);
        
        return 0;
    }

    private function analyzeResults($results, array $types, bool $includeTrends, bool $includeFiles, bool $includeRules): array
    {
        $analysis = [
            'summary' => $this->generateSummary($results, $types),
            'trends' => $includeTrends ? $this->analyzeTrends($results) : null,
            'files' => $includeFiles ? $this->analyzeFiles($results) : null,
            'rules' => $includeRules ? $this->analyzeRules($results) : null,
        ];

        return $analysis;
    }

    private function generateSummary($results, array $types): array
    {
        $summary = [
            'total_runs' => $results->count(),
            'total_issues' => 0,
            'by_type' => [],
            'by_severity' => [],
        ];

        foreach ($types as $type) {
            $summary['by_type'][$type] = $results->sum(function ($result) use ($type) {
                return $result->violations->where('type', $type)->count();
            });
            $summary['total_issues'] += $summary['by_type'][$type];
        }

        foreach (['error', 'warning', 'info'] as $severity) {
            $summary['by_severity'][$severity] = $results->sum(function ($result) use ($severity) {
                return $result->violations->where('severity', $severity)->count();
            });
        }

        return $summary;
    }

    private function analyzeTrends($results): array
    {
        $trends = [];
        $groupedResults = $results->groupBy(function ($result) {
            return $result->sniff_date->format('Y-m-d');
        });

        foreach ($groupedResults as $date => $dayResults) {
            $trends[] = [
                'date' => $date,
                'total_runs' => $dayResults->count(),
                'total_issues' => $dayResults->sum(function ($result) {
                    return $result->violations->count();
                }),
                'by_severity' => [
                    'error' => $dayResults->sum(function ($result) {
                        return $result->violations->where('severity', 'error')->count();
                    }),
                    'warning' => $dayResults->sum(function ($result) {
                        return $result->violations->where('severity', 'warning')->count();
                    }),
                    'info' => $dayResults->sum(function ($result) {
                        return $result->violations->where('severity', 'info')->count();
                    }),
                ],
            ];
        }

        return $trends;
    }

    private function analyzeFiles($results): array
    {
        $files = [];
        $groupedResults = $results->groupBy('file_path');

        foreach ($groupedResults as $file => $fileResults) {
            $files[] = [
                'path' => $file,
                'total_issues' => $fileResults->sum(function ($result) {
                    return $result->violations->count();
                }),
                'by_severity' => [
                    'error' => $fileResults->sum(function ($result) {
                        return $result->violations->where('severity', 'error')->count();
                    }),
                    'warning' => $fileResults->sum(function ($result) {
                        return $result->violations->where('severity', 'warning')->count();
                    }),
                    'info' => $fileResults->sum(function ($result) {
                        return $result->violations->where('severity', 'info')->count();
                    }),
                ],
                'last_checked' => $fileResults->max('sniff_date'),
            ];
        }

        return $files;
    }

    private function analyzeRules($results): array
    {
        $rules = [];
        $allViolations = $results->pluck('violations')->flatten();
        $groupedViolations = $allViolations->groupBy('rule_name');

        foreach ($groupedViolations as $rule => $violations) {
            $rules[] = [
                'name' => $rule,
                'total_issues' => $violations->count(),
                'by_severity' => [
                    'error' => $violations->where('severity', 'error')->count(),
                    'warning' => $violations->where('severity', 'warning')->count(),
                    'info' => $violations->where('severity', 'info')->count(),
                ],
                'affected_files' => $violations->pluck('file_path')->unique()->count(),
            ];
        }

        return $rules;
    }

    private function generateReport(array $analysis, string $format): string
    {
        return match($format) {
            'html' => View::make('sniffing.reports.analysis.html', $analysis)->render(),
            'markdown' => View::make('sniffing.reports.analysis.markdown', $analysis)->render(),
            'json' => json_encode($analysis, JSON_PRETTY_PRINT),
            default => $this->generateTextReport($analysis),
        };
    }

    private function generateTextReport(array $analysis): string
    {
        $output = "Sniffing Analysis Report\n";
        $output .= "=====================\n\n";

        // Summary
        $output .= "Summary\n";
        $output .= "-------\n";
        $output .= "Total Runs: {$analysis['summary']['total_runs']}\n";
        $output .= "Total Issues: {$analysis['summary']['total_issues']}\n\n";

        // By Type
        $output .= "Issues by Type:\n";
        foreach ($analysis['summary']['by_type'] as $type => $count) {
            $output .= "- {$type}: {$count}\n";
        }
        $output .= "\n";

        // By Severity
        $output .= "Issues by Severity:\n";
        foreach ($analysis['summary']['by_severity'] as $severity => $count) {
            $output .= "- {$severity}: {$count}\n";
        }
        $output .= "\n";

        // Trends
        if ($analysis['trends']) {
            $output .= "Trends\n";
            $output .= "------\n";
            foreach ($analysis['trends'] as $trend) {
                $output .= "Date: {$trend['date']}\n";
                $output .= "Total Runs: {$trend['total_runs']}\n";
                $output .= "Total Issues: {$trend['total_issues']}\n";
                $output .= "By Severity:\n";
                foreach ($trend['by_severity'] as $severity => $count) {
                    $output .= "  - {$severity}: {$count}\n";
                }
                $output .= "\n";
            }
        }

        // Files
        if ($analysis['files']) {
            $output .= "Files Analysis\n";
            $output .= "--------------\n";
            foreach ($analysis['files'] as $file) {
                $output .= "File: {$file['path']}\n";
                $output .= "Total Issues: {$file['total_issues']}\n";
                $output .= "By Severity:\n";
                foreach ($file['by_severity'] as $severity => $count) {
                    $output .= "  - {$severity}: {$count}\n";
                }
                $output .= "Last Checked: {$file['last_checked']}\n\n";
            }
        }

        // Rules
        if ($analysis['rules']) {
            $output .= "Rules Analysis\n";
            $output .= "--------------\n";
            foreach ($analysis['rules'] as $rule) {
                $output .= "Rule: {$rule['name']}\n";
                $output .= "Total Issues: {$rule['total_issues']}\n";
                $output .= "By Severity:\n";
                foreach ($rule['by_severity'] as $severity => $count) {
                    $output .= "  - {$severity}: {$count}\n";
                }
                $output .= "Affected Files: {$rule['affected_files']}\n\n";
            }
        }

        return $output;
    }

    private function saveReport(string $report, string $format): void
    {
        $extension = match($format) {
            'html' => 'html',
            'markdown' => 'md',
            'json' => 'json',
            default => 'txt',
        };

        $outputPath = $this->option('output') ?? 
            storage_path("reports/sniffing_analysis_{$format}_" . date('Y-m-d_H-i-s') . ".{$extension}");

        File::put($outputPath, $report);
        $this->info("Analysis report generated at: {$outputPath}");
    }
} 