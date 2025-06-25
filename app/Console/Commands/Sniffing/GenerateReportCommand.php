<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use App\Models\SniffingResult;
use Illuminate\Support\Facades\File;

class GenerateReportCommand extends Command
{
    protected $signature = 'sniff:generate-report
                                {--format=html : Report format (html, json, txt)}
                                {--output= : Output file path}
                                {--last : Generate report for the last sniffing result}';

    protected $description = 'Generate a report from sniffing results';

    public function handle()
    {
        $format = $this->option('format');
        $output = $this->option('output');
        $last = $this->option('last');

        if ($last) {
            $results = SniffingResult::latest()->first();
        } else {
            $results = SniffingResult::all();
        }

        if (!$results) {
            $this->error('No sniffing results found');
            return 1;
        }

        $report = $this->generateReport($results, $format);

        if ($output) {
            File::put($output, $report);
            $this->info("Report generated and saved to: {$output}");
        } else {
            $this->line($report);
        }

        return 0;
    }

    private function generateReport($results, $format)
    {
        switch ($format) {
            case 'html':
                return $this->generateHtmlReport($results);
            case 'json':
                return $this->generateJsonReport($results);
            default:
                return $this->generateTextReport($results);
        }
    }

    private function generateHtmlReport($results)
    {
        $html = '<!DOCTYPE html><html><head><title>PHP Code Sniffer Report</title></head><body>';
        $html .= '<h1>PHP Code Sniffer Report</h1>';
        $html .= '<p>Generated at: ' . now()->format('Y-m-d H:i:s') . '</p>';

        foreach ($results as $result) {
            $html .= '<div class="result">';
            $html .= '<h2>File: ' . htmlspecialchars($result->file_path) . '</h2>';
            $html .= '<p>Errors: ' . $result->error_count . '</p>';
            $html .= '<p>Warnings: ' . $result->warning_count . '</p>';
            $html .= '<pre>' . htmlspecialchars(json_encode($result->result_data, JSON_PRETTY_PRINT)) . '</pre>';
            $html .= '</div>';
        }

        $html .= '</body></html>';
        return $html;
    }

    private function generateJsonReport($results)
    {
        return json_encode([
            'results' => $results->map(function ($result) {
                return [
                    'file_path' => $result->file_path,
                    'error_count' => $result->error_count,
                    'warning_count' => $result->warning_count,
                    'result_data' => $result->result_data
                ];
            })->toArray(),
            'generated_at' => now()->toISOString()
        ], JSON_PRETTY_PRINT);
    }

    private function generateTextReport($results)
    {
        $text = "PHP Code Sniffer Report\n";
        $text .= "Generated at: " . now()->format('Y-m-d H:i:s') . "\n\n";

        foreach ($results as $result) {
            $text .= "File: " . $result->file_path . "\n";
            $text .= "Errors: " . $result->error_count . "\n";
            $text .= "Warnings: " . $result->warning_count . "\n";
            $text .= "Results: " . json_encode($result->result_data) . "\n\n";
        }

        return $text;
    }
}
