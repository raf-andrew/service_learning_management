<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunCommandTestsCommand extends Command
{
    protected $signature = 'test:commands-all {--suite= : Specific test suite to run}';
    protected $description = 'Run all command tests and generate comprehensive reports';

    protected $testSuites = [
        'Web3CommandsTest',
        'HealthMonitorCommandsTest',
        'InfrastructureCommandsTest',
        'CodespaceCommandsTest',
        'UtilityCommandsTest',
        'AnalyticsCommandsTest',
        'ConfigCommandsTest',
        'SniffingCommandsTest'
    ];

    protected $reportsDir;

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.reports/command-tests');
    }

    public function handle()
    {
        $this->info('Starting comprehensive command tests...');
        
        // Create reports directory
        if (!File::exists($this->reportsDir)) {
            File::makeDirectory($this->reportsDir, 0755, true);
        }

        $suite = $this->option('suite');
        $verbose = $this->option('verbose');

        if ($suite) {
            $this->runSingleSuite($suite, $verbose);
        } else {
            $this->runAllSuites($verbose);
        }

        $this->generateSummaryReport();
        $this->info('Command tests completed!');
    }

    protected function runAllSuites($verbose = false)
    {
        $results = [];
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->testSuites as $suite) {
            $this->info("\n=== Testing {$suite} ===");
            
            $result = $this->runSingleSuite($suite, $verbose);
            $results[$suite] = $result;
            
            $totalTests += $result['total'];
            $passedTests += $result['passed'];
            $failedTests += $result['failed'];
        }

        // Save detailed results
        $detailedReport = [
            'timestamp' => now()->toIso8601String(),
            'total_tests' => $totalTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $failedTests,
            'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0,
            'suite_results' => $results
        ];

        File::put(
            $this->reportsDir . '/detailed-results.json',
            json_encode($detailedReport, JSON_PRETTY_PRINT)
        );

        $this->info("\n=== Overall Summary ===");
        $this->info("Total Tests: {$totalTests}");
        $this->info("Passed: {$passedTests}");
        $this->info("Failed: {$failedTests}");
        $this->info("Success Rate: " . ($totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0) . "%");
    }

    protected function runSingleSuite($suite, $verbose = false)
    {
        $testFile = "tests/Feature/Commands/{$suite}.php";
        
        if (!File::exists($testFile)) {
            $this->error("Test file not found: {$testFile}");
            return ['total' => 0, 'passed' => 0, 'failed' => 1];
        }

        $command = [
            'php',
            'vendor/phpunit/phpunit/phpunit',
            $testFile,
            '--testdox'
        ];

        if ($verbose) {
            $command[] = '--verbose';
        }

        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes timeout
        
        $this->info("Running: " . implode(' ', $command));
        
        $process->run(function ($type, $buffer) use ($verbose) {
            if ($verbose || $type === Process::ERR) {
                $this->output->write($buffer);
            }
        });

        $output = $process->getOutput();
        $exitCode = $process->getExitCode();

        // Parse test results from output
        $result = $this->parseTestOutput($output);
        
        // Save individual suite report
        $suiteReport = [
            'suite' => $suite,
            'timestamp' => now()->toIso8601String(),
            'exit_code' => $exitCode,
            'output' => $output,
            'results' => $result
        ];

        File::put(
            $this->reportsDir . "/{$suite}-results.json",
            json_encode($suiteReport, JSON_PRETTY_PRINT)
        );

        return $result;
    }

    protected function parseTestOutput($output)
    {
        // Simple parsing of test output
        $lines = explode("\n", $output);
        $total = 0;
        $passed = 0;
        $failed = 0;

        foreach ($lines as $line) {
            if (strpos($line, '✓') !== false) {
                $passed++;
                $total++;
            } elseif (strpos($line, '✗') !== false) {
                $failed++;
                $total++;
            }
        }

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed
        ];
    }

    protected function generateSummaryReport()
    {
        $summaryReport = [
            'timestamp' => now()->toIso8601String(),
            'test_suites' => $this->testSuites,
            'reports_location' => $this->reportsDir,
            'next_steps' => [
                'Review individual suite reports in .reports/command-tests/',
                'Check for any failed tests and fix issues',
                'Run with --verbose flag for detailed output',
                'Consider adding more test cases for better coverage'
            ]
        ];

        File::put(
            $this->reportsDir . '/summary-report.json',
            json_encode($summaryReport, JSON_PRETTY_PRINT)
        );

        $this->info("\nReports generated in: {$this->reportsDir}");
    }
} 