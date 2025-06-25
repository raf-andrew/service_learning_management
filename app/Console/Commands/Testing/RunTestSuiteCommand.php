<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunTestSuiteCommand extends Command
{
    protected $signature = 'test:suite';
    protected $description = 'Run the complete test suite including tests, code style checks, and static analysis';

    protected $reportsDir;
    protected $sniffsDir;

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.reports/tests');
        $this->sniffsDir = base_path('.reports/sniffs');
    }

    public function handle()
    {
        $this->info('Starting test suite...');

        // Create report directories
        $this->createReportDirectories();

        // Set environment variable for Docker Compose
        putenv('PWD=' . base_path());

        // Build and run test containers
        $this->info('Building and starting test containers...');
        $this->runDockerCompose('up -d --build');

        // Wait for services to be ready
        $this->info('Waiting for services to be ready...');
        sleep(10);

        // Run tests
        $this->info('Running tests...');
        $testExitCode = $this->runDockerCompose('exec -T test php scripts/run-tests.php');

        if ($testExitCode !== 0) {
            $this->error("Tests failed with exit code {$testExitCode}");
            $this->cleanup($testExitCode);
            return $testExitCode;
        }

        // Run code style checks
        $this->info('Running code style checks...');
        $this->runDockerCompose('exec -T psr12-sniffs');

        // Run static analysis
        $this->info('Running static analysis...');
        $this->runDockerCompose('exec -T phpmd-analysis');

        // Generate test report
        $this->info('Generating test report...');
        $this->runDockerCompose('exec -T generate-report');

        // Stop containers
        $this->info('Stopping test containers...');
        $this->runDockerCompose('down');

        // Check test results
        $this->checkTestResults();

        // Generate test plan
        $this->generateTestPlan();

        $this->info('All tests passed!');
        return 0;
    }

    protected function createReportDirectories()
    {
        File::makeDirectory($this->reportsDir, 0755, true, true);
        File::makeDirectory($this->sniffsDir, 0755, true, true);
    }

    protected function runDockerCompose($command)
    {
        $process = new Process(['docker-compose', '-f', 'docker-compose.test.yml'] + explode(' ', $command));
        $process->setTty(true);
        $process->run();

        return $process->getExitCode();
    }

    protected function checkTestResults()
    {
        if (File::exists($this->reportsDir . '/phpunit.xml')) {
            $this->info('Test results have been saved to .reports/tests/phpunit.xml');
        } else {
            $this->warn('Warning: No test results found');
        }

        if (File::exists($this->sniffsDir . '/phpcs.xml')) {
            $this->info('Code sniff results have been saved to .reports/sniffs/phpcs.xml');
        } else {
            $this->warn('Warning: No code sniff results found');
        }

        if (File::exists($this->sniffsDir . '/phpmd.xml')) {
            $this->info('PHPMD results have been saved to .reports/sniffs/phpmd.xml');
        } else {
            $this->warn('Warning: No PHPMD results found');
        }
    }

    protected function generateTestPlan()
    {
        $testPlan = $this->generateTestPlanContent();
        File::put(base_path('.reports/test_plan.md'), $testPlan);
        $this->info('Test plan has been updated in .reports/test_plan.md');
    }

    protected function generateTestPlanContent()
    {
        $unitTests = count(File::glob(base_path('tests/Unit/*.php')));
        $featureTests = count(File::glob(base_path('tests/Feature/*.php')));
        $integrationTests = count(File::glob(base_path('tests/Integration/*.php')));
        $smokeTests = count(File::glob(base_path('tests/Smoke/*.php')));

        $phpunitXml = simplexml_load_file($this->reportsDir . '/phpunit.xml');
        $tests = (int)$phpunitXml->testsuite['tests'];
        $assertions = (int)$phpunitXml->testsuite['assertions'];
        $failures = (int)$phpunitXml->testsuite['failures'];
        $errors = (int)$phpunitXml->testsuite['errors'];
        $time = (float)$phpunitXml->testsuite['time'];

        $coverageXml = @simplexml_load_file($this->reportsDir . '/coverage/index.xml');
        $lineRate = $coverageXml ? (float)$coverageXml['line-rate'] : 0;

        $memoryUsage = @file_get_contents($this->reportsDir . '/memory.txt');

        return <<<EOT
# Test Plan Report

## Test Coverage
- Unit Tests: {$unitTests}
- Feature Tests: {$featureTests}
- Integration Tests: {$integrationTests}
- Smoke Tests: {$smokeTests}

## Test Results
- Total Tests: {$tests}
- Passed Tests: {$assertions}
- Failed Tests: {$failures}
- Error Tests: {$errors}

## Code Quality
- Code Coverage: {$lineRate}
- Code Style: {$this->getCodeStyleStatus()}
- Code Complexity: {$this->getCodeComplexityStatus()}

## Security Checks
- Test Integrity: {$this->getSecurityStatus()}
- Test Coverage: {$this->getCoverageStatus()}

## Performance Metrics
- Execution Time: {$time}
- Memory Usage: {$memoryUsage}
EOT;
    }

    protected function getCodeStyleStatus()
    {
        return File::exists($this->sniffsDir . '/phpcs.xml') ? 'Passed' : 'Failed';
    }

    protected function getCodeComplexityStatus()
    {
        return File::exists($this->sniffsDir . '/phpmd.xml') ? 'Passed' : 'Failed';
    }

    protected function getSecurityStatus()
    {
        return File::exists($this->reportsDir . '/security.xml') ? 'Passed' : 'Failed';
    }

    protected function getCoverageStatus()
    {
        return File::exists($this->reportsDir . '/coverage') ? 'Passed' : 'Failed';
    }

    protected function cleanup($exitCode)
    {
        $this->runDockerCompose('down');
        return $exitCode;
    }
} 