<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\TestRunner;

class RunTests extends Command
{
    protected $signature = 'test:run {checklist-item} {test-file?}';
    protected $description = 'Run tests and generate reports for a checklist item';

    public function handle()
    {
        $checklistItem = $this->argument('checklist-item');
        $testFile = $this->argument('test-file');

        $runner = new TestRunner($checklistItem);

        if ($testFile) {
            $report = $runner->runTestAndReport($testFile);
        } else {
            $report = $runner->runAllTests();
        }

        $this->displayReport($report);
    }

    protected function displayReport(array $report): void
    {
        $this->info("\nTest Report for {$report['checklist_item']}");
        $this->info("Generated at: {$report['timestamp']}\n");

        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tests', $report['summary']['total_tests']],
                ['Passed Tests', $report['summary']['passed_tests']],
                ['Failed Tests', $report['summary']['failed_tests']],
                ['Coverage', number_format($report['summary']['coverage_percentage'], 2) . '%'],
            ]
        );

        if (!empty($report['metrics'])) {
            $this->info("\nMetrics:");
            $this->table(
                ['Metric', 'Value'],
                collect($report['metrics'])->map(fn($value, $key) => [$key, $value])->toArray()
            );
        }

        if (!empty($report['results'])) {
            $this->info("\nTest Results:");
            $this->table(
                ['Test', 'Status', 'Details'],
                collect($report['results'])->map(function ($result) {
                    return [
                        $result['test_name'],
                        $result['passed'] ? '<fg=green>PASSED</>' : '<fg=red>FAILED</>',
                        json_encode($result['details']),
                    ];
                })->toArray()
            );
        }

        $this->info("\nReport saved to: " . storage_path('app/test-reports'));
    }
} 