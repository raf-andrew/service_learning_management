<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\TextUI\Command;
use Illuminate\Support\Facades\File;

class RouteTestRunner
{
    protected $testClasses = [
        'Tests\Feature\Auth\RoutesTest',
        'Tests\Feature\User\RoutesTest',
        'Tests\Feature\ServiceLearning\RoutesTest',
        'Tests\Feature\Api\RoutesTest',
    ];

    protected $reportPath = 'storage/logs/route-test-report.json';
    protected $results = [];

    public function run()
    {
        echo "Starting route tests...\n";
        
        foreach ($this->testClasses as $class) {
            echo "\nTesting {$class}...\n";
            
            try {
                $command = new Command();
                $command->run(['phpunit', '--filter', $class], false);
                
                $this->collectResults($class);
            } catch (\Exception $e) {
                echo "Error running tests for {$class}: {$e->getMessage()}\n";
            }
        }

        $this->generateReport();
        echo "\nRoute testing completed. Report generated at: {$this->reportPath}\n";
    }

    protected function collectResults($class)
    {
        $reportPath = storage_path('logs/route-test-report.json');
        
        if (File::exists($reportPath)) {
            $report = json_decode(File::get($reportPath), true);
            $this->results[$class] = $report;
        }
    }

    protected function generateReport()
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'results' => $this->results,
            'summary' => $this->generateSummary(),
        ];

        File::put($this->reportPath, json_encode($report, JSON_PRETTY_PRINT));
    }

    protected function generateSummary()
    {
        $summary = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'coverage' => [],
        ];

        foreach ($this->results as $class => $result) {
            if (isset($result['coverage'])) {
                $summary['coverage'][$class] = $result['coverage'];
            }

            if (isset($result['tests'])) {
                foreach ($result['tests'] as $test) {
                    $summary['total_tests']++;
                    
                    if ($test['status'] === 'passed') {
                        $summary['passed_tests']++;
                    } else {
                        $summary['failed_tests']++;
                    }
                }
            }
        }

        return $summary;
    }
}

// Run the tests
$runner = new RouteTestRunner();
$runner->run(); 