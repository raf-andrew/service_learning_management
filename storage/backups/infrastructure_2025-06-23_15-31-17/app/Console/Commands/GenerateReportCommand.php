<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GenerateReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sniff:report {--format=html} {--output=} {--include-results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a sniffing analysis report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $output = $this->option('output');
        $includeResults = $this->option('include-results');

        $this->info("Generating sniffing report in {$format} format...");

        try {
            $reportData = $this->generateReportData($includeResults);
            $report = $this->formatReport($reportData, $format);

            if ($output) {
                $this->saveReport($report, $output, $format);
                $this->info("Report saved to: {$output}");
            } else {
                $this->line($report);
            }

        } catch (\Exception $e) {
            $this->error("Error generating report: {$e->getMessage()}");
            Log::error('GenerateReportCommand error', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Generate report data
     */
    protected function generateReportData(bool $includeResults): array
    {
        $data = [
            'timestamp' => now()->toISOString(),
            'summary' => [
                'total_analyses' => 0,
                'total_rules_checked' => 0,
                'total_passed' => 0,
                'total_failed' => 0,
            ],
            'analyses' => []
        ];

        // Get sniffing results from storage
        $resultsPath = storage_path('app/sniffing/results');
        if (File::exists($resultsPath)) {
            $files = File::files($resultsPath);
            $data['summary']['total_analyses'] = count($files);

            foreach ($files as $file) {
                $content = json_decode(File::get($file->getPathname()), true);
                if ($content) {
                    $analysis = [
                        'filename' => $file->getFilename(),
                        'timestamp' => $content['timestamp'] ?? null,
                        'target' => $content['target'] ?? 'Unknown',
                        'rules_checked' => count($content['results'] ?? []),
                        'passed' => 0,
                        'failed' => 0,
                    ];

                    if (isset($content['results'])) {
                        foreach ($content['results'] as $result) {
                            if (($result['status'] ?? '') === 'passed') {
                                $analysis['passed']++;
                                $data['summary']['total_passed']++;
                            } else {
                                $analysis['failed']++;
                                $data['summary']['total_failed']++;
                            }
                        }
                    }

                    $data['summary']['total_rules_checked'] += $analysis['rules_checked'];

                    if ($includeResults) {
                        $analysis['results'] = $content['results'] ?? [];
                    }

                    $data['analyses'][] = $analysis;
                }
            }
        }

        return $data;
    }

    /**
     * Format report based on format type
     */
    protected function formatReport(array $data, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            
            case 'html':
                return $this->formatHtmlReport($data);
            
            case 'text':
                return $this->formatTextReport($data);
            
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Format HTML report
     */
    protected function formatHtmlReport(array $data): string
    {
        $html = '<!DOCTYPE html><html><head><title>Sniffing Analysis Report</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;margin:20px;}';
        $html .= 'table{border-collapse:collapse;width:100%;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}';
        $html .= 'th{background-color:#f2f2f2;}</style></head><body>';
        
        $html .= '<h1>Sniffing Analysis Report</h1>';
        $html .= '<p><strong>Generated:</strong> ' . $data['timestamp'] . '</p>';
        
        $html .= '<h2>Summary</h2>';
        $html .= '<table><tr><th>Metric</th><th>Value</th></tr>';
        $html .= '<tr><td>Total Analyses</td><td>' . $data['summary']['total_analyses'] . '</td></tr>';
        $html .= '<tr><td>Total Rules Checked</td><td>' . $data['summary']['total_rules_checked'] . '</td></tr>';
        $html .= '<tr><td>Total Passed</td><td>' . $data['summary']['total_passed'] . '</td></tr>';
        $html .= '<tr><td>Total Failed</td><td>' . $data['summary']['total_failed'] . '</td></tr>';
        $html .= '</table>';
        
        if (!empty($data['analyses'])) {
            $html .= '<h2>Analyses</h2>';
            $html .= '<table><tr><th>File</th><th>Target</th><th>Rules</th><th>Passed</th><th>Failed</th></tr>';
            
            foreach ($data['analyses'] as $analysis) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($analysis['filename']) . '</td>';
                $html .= '<td>' . htmlspecialchars($analysis['target']) . '</td>';
                $html .= '<td>' . $analysis['rules_checked'] . '</td>';
                $html .= '<td>' . $analysis['passed'] . '</td>';
                $html .= '<td>' . $analysis['failed'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        
        $html .= '</body></html>';
        return $html;
    }

    /**
     * Format text report
     */
    protected function formatTextReport(array $data): string
    {
        $text = "SNIFFING ANALYSIS REPORT\n";
        $text .= "Generated: " . $data['timestamp'] . "\n\n";
        
        $text .= "SUMMARY:\n";
        $text .= "  Total Analyses: " . $data['summary']['total_analyses'] . "\n";
        $text .= "  Total Rules Checked: " . $data['summary']['total_rules_checked'] . "\n";
        $text .= "  Total Passed: " . $data['summary']['total_passed'] . "\n";
        $text .= "  Total Failed: " . $data['summary']['total_failed'] . "\n\n";
        
        if (!empty($data['analyses'])) {
            $text .= "ANALYSES:\n";
            foreach ($data['analyses'] as $analysis) {
                $text .= "  File: " . $analysis['filename'] . "\n";
                $text .= "  Target: " . $analysis['target'] . "\n";
                $text .= "  Rules: " . $analysis['rules_checked'] . "\n";
                $text .= "  Passed: " . $analysis['passed'] . "\n";
                $text .= "  Failed: " . $analysis['failed'] . "\n\n";
            }
        }
        
        return $text;
    }

    /**
     * Save report to file
     */
    protected function saveReport(string $content, string $path, string $format): void
    {
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        File::put($path, $content);
    }
} 