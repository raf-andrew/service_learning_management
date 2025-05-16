<?php

namespace App\MCP\Agents\QA\TestAutomation;

use App\MCP\Agents\Development\CodeAnalysis\BaseCodeAnalysisAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Test Automation Agent
 * 
 * This agent is responsible for:
 * - Running automated tests
 * - Collecting test results
 * - Analyzing test coverage
 * - Generating test reports
 * - Managing test suites
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class TestAutomationAgent extends BaseCodeAnalysisAgent
{
    private array $metrics = [
        'tests_run' => 0,
        'tests_passed' => 0,
        'tests_failed' => 0,
        'tests_skipped' => 0,
        'coverage_percentage' => 0,
        'test_suites_executed' => 0,
        'total_execution_time' => 0
    ];

    private array $report = [];
    private array $testResults = [];
    private array $coverageData = [];
    private array $testSuites = [];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
    }

    /**
     * Get the agent's metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Run tests and return results
     */
    public function analyze(array $files): array
    {
        $this->logger->info('Starting test automation for ' . count($files) . ' files');
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning("Test file not found: $file");
                continue;
            }

            try {
                $this->runTestFile($file);
            } catch (\Throwable $e) {
                $this->logger->error("Error running tests in $file: " . $e->getMessage());
                $this->logTestError($file, $e);
            }
        }

        $this->report = [
            'metrics' => $this->metrics,
            'test_results' => $this->testResults,
            'coverage_data' => $this->coverageData,
            'test_suites' => $this->testSuites,
            'summary' => $this->generateSummary(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->report;
    }

    /**
     * Get test recommendations
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        // Check for low coverage
        if ($this->metrics['coverage_percentage'] < 100) {
            $recommendations[] = [
                'type' => 'coverage',
                'message' => 'Test coverage is below 100%',
                'severity' => 'warning',
                'current_value' => $this->metrics['coverage_percentage'],
                'target_value' => 100
            ];
        }

        // Check for failed tests
        if ($this->metrics['tests_failed'] > 0) {
            $recommendations[] = [
                'type' => 'failures',
                'message' => 'Some tests are failing',
                'severity' => 'error',
                'failed_count' => $this->metrics['tests_failed']
            ];
        }

        // Check for skipped tests
        if ($this->metrics['tests_skipped'] > 0) {
            $recommendations[] = [
                'type' => 'skipped',
                'message' => 'Some tests are being skipped',
                'severity' => 'warning',
                'skipped_count' => $this->metrics['tests_skipped']
            ];
        }

        return $recommendations;
    }

    /**
     * Get test report
     */
    public function getReport(): array
    {
        return $this->report;
    }

    /**
     * Run a test file
     */
    private function runTestFile(string $file): void
    {
        $startTime = microtime(true);

        $process = new Process(['./vendor/bin/phpunit', $file, '--coverage-text']);
        $process->run();

        $executionTime = microtime(true) - $startTime;
        $this->metrics['total_execution_time'] += $executionTime;

        if ($process->isSuccessful()) {
            $this->parseTestResults($process->getOutput(), $file);
        } else {
            $this->handleTestFailure($process->getErrorOutput(), $file);
        }

        $this->metrics['test_suites_executed']++;
    }

    /**
     * Parse test results from PHPUnit output
     */
    private function parseTestResults(string $output, string $file): void
    {
        // Parse test counts
        preg_match('/Tests: (\d+), Assertions: (\d+), Failures: (\d+), Errors: (\d+), Skipped: (\d+)/', $output, $matches);
        
        if ($matches) {
            $this->metrics['tests_run'] += (int)$matches[1];
            $this->metrics['tests_failed'] += (int)$matches[3] + (int)$matches[4];
            $this->metrics['tests_skipped'] += (int)$matches[5];
            $this->metrics['tests_passed'] += (int)$matches[1] - (int)$matches[3] - (int)$matches[4] - (int)$matches[5];
        }

        // Parse coverage data
        preg_match('/Lines:\s+(\d+\.\d+)%/', $output, $coverageMatches);
        if ($coverageMatches) {
            $this->coverageData[$file] = (float)$coverageMatches[1];
            $this->updateAverageCoverage();
        }

        $this->testResults[$file] = [
            'output' => $output,
            'status' => 'passed',
            'execution_time' => $this->metrics['total_execution_time']
        ];
    }

    /**
     * Handle test failure
     */
    private function handleTestFailure(string $error, string $file): void
    {
        $this->testResults[$file] = [
            'output' => $error,
            'status' => 'failed',
            'execution_time' => $this->metrics['total_execution_time']
        ];

        // Log failure to .failures directory
        $this->logTestFailure($file, $error);
    }

    /**
     * Log test error
     */
    private function logTestError(string $file, \Throwable $error): void
    {
        $errorLog = sprintf(
            "Test Error in %s\nTimestamp: %s\nError: %s\nStack Trace:\n%s\n",
            $file,
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getTraceAsString()
        );

        $errorFile = '.errors/' . basename($file) . '_' . date('Y-m-d_H-i-s') . '.log';
        file_put_contents($errorFile, $errorLog);
    }

    /**
     * Log test failure
     */
    private function logTestFailure(string $file, string $error): void
    {
        $failureLog = sprintf(
            "Test Failure in %s\nTimestamp: %s\nOutput:\n%s\n",
            $file,
            date('Y-m-d H:i:s'),
            $error
        );

        $failureFile = '.failures/' . basename($file) . '_' . date('Y-m-d_H-i-s') . '.log';
        file_put_contents($failureFile, $failureLog);
    }

    /**
     * Update average coverage
     */
    private function updateAverageCoverage(): void
    {
        if (empty($this->coverageData)) {
            $this->metrics['coverage_percentage'] = 0;
            return;
        }

        $this->metrics['coverage_percentage'] = array_sum($this->coverageData) / count($this->coverageData);
    }

    /**
     * Generate test summary
     */
    private function generateSummary(): array
    {
        return [
            'total_files_tested' => count($this->testResults),
            'total_tests' => $this->metrics['tests_run'],
            'passed_tests' => $this->metrics['tests_passed'],
            'failed_tests' => $this->metrics['tests_failed'],
            'skipped_tests' => $this->metrics['tests_skipped'],
            'average_coverage' => $this->metrics['coverage_percentage'],
            'total_execution_time' => $this->metrics['total_execution_time']
        ];
    }
} 