<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunIndividualTestsCommand extends Command
{
    protected $signature = 'test:individual';
    protected $description = 'Run individual test suites for specific components';

    protected $reportsDir;
    protected $sniffsDir;
    protected $tempDir;
    protected $isWindows;

    public function __construct()
    {
        parent::__construct();
        $this->reportsDir = base_path('.reports/tests');
        $this->sniffsDir = base_path('.reports/sniffs');
        $this->tempDir = base_path('.reports/temp');
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function handle()
    {
        $this->info('Starting individual test suites...');

        // Create necessary directories
        $this->createDirectories();

        // Set environment variable for Docker Compose
        putenv('PWD=' . base_path());

        // Run Service Health Agent Tests
        $this->runTest('Service Health Agent Tests', [
            'composer install',
            'vendor/bin/phpunit --filter=ServiceHealthAgentTest --log-junit=.reports/temp/service_health_test.xml'
        ]);

        // Run Deployment Automation Agent Tests
        $this->runTest('Deployment Automation Agent Tests', [
            'composer install',
            'vendor/bin/phpunit --filter=DeploymentAutomationAgentTest --log-junit=.reports/temp/deployment_test.xml'
        ]);

        // Run PSR-12 Sniffs
        $this->runTest('PSR-12 Compliance Check', [
            'composer install',
            'vendor/bin/phpcs --standard=PSR12 --report=junit --report-file=.reports/temp/psr12_sniff.xml src/MCP/Core/ServiceHealthAgent.php src/MCP/Core/DeploymentAutomationAgent.php'
        ]);

        // Run PHPMD Analysis
        $this->runTest('PHPMD Analysis', [
            'composer install',
            'vendor/bin/phpmd src/MCP/Core xml cleancode,codesize,controversial,design,naming,unusedcode > .reports/temp/phpmd.xml'
        ]);

        // Combine all results
        $this->combineResults();

        // Update test plan
        $this->updateTestPlan();

        return 0;
    }

    protected function createDirectories()
    {
        File::makeDirectory($this->reportsDir, 0755, true, true);
        File::makeDirectory($this->sniffsDir, 0755, true, true);
        File::makeDirectory($this->tempDir, 0755, true, true);
    }

    protected function runTest($testName, array $commands)
    {
        $this->info("Running {$testName}...");

        if ($this->isWindows) {
            // Remove composer install for Windows
            $filteredCommands = array_filter($commands, function($cmd) {
                return strpos($cmd, 'composer install') === false;
            });
            $this->runWindowsTest($testName, $filteredCommands);
        } else {
            $this->runDockerTest($testName, $commands);
        }
    }

    protected function runWindowsTest($testName, array $commands)
    {
        foreach ($commands as $command) {
            $this->info("Executing: {$command}");
            
            // PHPMD special handling for output
            if (strpos($command, 'phpmd') !== false && strpos($command, '>') !== false) {
                // Remove output redirection
                $parts = explode('>', $command);
                $phpmdCmd = trim($parts[0]);
                $outputFile = trim($parts[1]);
                // Always use 'php vendor/bin/phpmd' on Windows
                if (preg_match('/^(php )?vendor\/bin\/phpmd/', $phpmdCmd) && $outputFile) {
                    if (strpos($phpmdCmd, 'php vendor/bin/phpmd') !== 0) {
                        $phpmdCmd = 'php vendor/bin/phpmd' . substr($phpmdCmd, strlen('vendor/bin/phpmd'));
                    }
                    $process = new Process(explode(' ', $phpmdCmd));
                    $process->setWorkingDirectory(base_path());
                    $process->run();
                    file_put_contents(base_path($outputFile), $process->getOutput());
                    if ($process->isSuccessful()) {
                        $this->info("PHPMD command completed successfully");
                    } else {
                        $this->error("PHPMD command failed");
                        $this->error($process->getErrorOutput());
                        $this->error($process->getOutput());
                    }
                    continue;
                }
            }
            // Prepend 'php' for PHP scripts on Windows
            if (preg_match('/^(vendor\/bin\/(phpunit|phpcs|phpmd))/', $command)) {
                $command = 'php ' . $command;
            }
            $process = new Process(explode(' ', $command));
            $process->setWorkingDirectory(base_path());
            $process->run();

            if ($process->isSuccessful()) {
                $this->info("Command completed successfully");
            } else {
                $this->error("Command failed");
                $this->error($process->getErrorOutput());
                $this->error($process->getOutput());
            }
        }

        $this->info("{$testName} completed");
        $this->line('----------------------------------------');
    }

    protected function runDockerTest($testName, array $commands)
    {
        $this->info("Running {$testName}...");

        $process = new Process([
            'docker-compose',
            '-f',
            'docker-compose.test.yml',
            'run',
            '--rm',
            'test',
            'sh',
            '-c',
            implode(' && ', $commands)
        ]);

        // Only set TTY on Unix systems
        if (!$this->isWindows) {
            $process->setTty(true);
        }
        
        $process->run();

        if ($process->isSuccessful()) {
            $this->info("{$testName} completed successfully");
        } else {
            $this->error("{$testName} failed");
            $this->error($process->getErrorOutput());
        }

        $this->line('----------------------------------------');
    }

    protected function combineResults()
    {
        $this->info('Collecting all test results...');

        $xmlFiles = File::glob($this->tempDir . '/*.xml');
        foreach ($xmlFiles as $file) {
            $destinationPath = $this->reportsDir . '/' . basename($file);
            File::copy($file, $destinationPath);
            $this->info('Copied ' . basename($file) . ' to reports directory');
        }
    }

    protected function updateTestPlan()
    {
        $this->info('Updating test plan...');

        if (File::exists($this->tempDir . '/*.xml')) {
            if ($this->isWindows) {
                $process = new Process(['php', 'scripts/update-test-plan.php']);
            } else {
                $process = new Process([
                    'docker-compose',
                    '-f',
                    'docker-compose.test.yml',
                    'run',
                    '--rm',
                    'test',
                    'php',
                    'scripts/update-test-plan.php'
                ]);
            }

            $process->setWorkingDirectory(base_path());
            $process->run();

            if ($process->isSuccessful()) {
                $this->info('Test plan updated');
            } else {
                $this->error('Failed to update test plan');
                $this->error($process->getErrorOutput());
            }
        } else {
            $this->warn('No test results found to update test plan');
        }
    }
} 