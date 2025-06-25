<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class TestReportCommand extends Command
{
    protected $signature = 'test:report {--suite=} {--format=text} {--output=}';
    protected $description = 'Generate comprehensive test reports for all test suites';

    public function handle()
    {
        $this->info('🔍 Generating test report...');
        
        $suite = $this->option('suite');
        $suites = $suite ? [$suite] : ['Unit', 'Feature', 'Command', 'Service', 'Provider'];
        
        $results = [];
        
        foreach ($suites as $testSuite) {
            $this->info("Running {$testSuite} tests...");
            
            $result = Artisan::call('test', ['--testsuite' => $testSuite]);
            $results[$testSuite] = $result === 0 ? 'PASSED' : 'FAILED';
        }

        $this->info("\n=== TEST REPORT ===");
        foreach ($results as $suite => $status) {
            $this->line("{$suite}: {$status}");
        }
        
        $this->info('✅ Report complete!');
        return 0;
    }

    private function generateReportOutput($report, $format, $output)
    {
        $content = '';
        
        switch ($format) {
            case 'json':
                $content = json_encode($report, JSON_PRETTY_PRINT);
                break;
            case 'html':
                $content = $this->generateHtmlReport($report);
                break;
            default:
                $content = $this->generateTextReport($report);
        }
        
        if ($output) {
            File::put($output, $content);
            $this->info("Report saved to: {$output}");
        } else {
            $this->line($content);
        }
    }

    private function generateTextReport($report)
    {
        $output = "=== COMPREHENSIVE TEST REPORT ===\n";
        $output .= "Generated: {$report['timestamp']}\n";
        $output .= "PHP Version: {$report['php_version']}\n";
        $output .= "Laravel Version: {$report['laravel_version']}\n\n";
        
        $output .= "=== TEST SUITES ===\n";
        foreach ($report['test_suites'] as $suite => $data) {
            $status = strtoupper($data['status']);
            $output .= "{$suite}: {$status}\n";
        }
        
        $output .= "\n=== VITEST ===\n";
        $output .= "Status: " . strtoupper($report['vitest']['status']) . "\n";
        
        $output .= "\n=== SUMMARY ===\n";
        $output .= "Total Tests: {$report['summary']['total_tests']}\n";
        $output .= "Passed: {$report['summary']['passed']}\n";
        $output .= "Failed: {$report['summary']['failed']}\n";
        
        return $output;
    }

    private function generateHtmlReport($report)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f0f0f0; padding: 20px; border-radius: 5px; }
                .suite { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
                .passed { background: #d4edda; }
                .failed { background: #f8d7da; }
                .summary { background: #e2e3e5; padding: 15px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Test Report</h1>
                <p>Generated: {$report['timestamp']}</p>
                <p>PHP: {$report['php_version']} | Laravel: {$report['laravel_version']}</p>
            </div>
            
            <div class='summary'>
                <h2>Summary</h2>
                <p>Total: {$report['summary']['total_tests']} | 
                   Passed: {$report['summary']['passed']} | 
                   Failed: {$report['summary']['failed']}</p>
            </div>
            
            <h2>Test Suites</h2>
            " . implode('', array_map(function($suite, $data) {
                $class = $data['status'] === 'passed' ? 'passed' : 'failed';
                return "<div class='suite {$class}'>
                    <h3>{$suite}</h3>
                    <p>Status: " . strtoupper($data['status']) . "</p>
                    <p>Time: {$data['timestamp']}</p>
                </div>";
            }, array_keys($report['test_suites']), $report['test_suites'])) . "
            
            <h2>Vitest</h2>
            <div class='suite'>
                <p>Status: " . strtoupper($report['vitest']['status']) . "</p>
            </div>
        </body>
        </html>";
    }
} 