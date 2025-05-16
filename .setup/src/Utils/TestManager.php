<?php

namespace Setup\Utils;

class TestManager {
    private array $config;
    private Logger $logger;
    private array $results = [];

    public function __construct(array $config, Logger $logger) {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function runTests(): void {
        if (!$this->config['enabled']) {
            $this->logger->info('Testing is disabled');
            return;
        }

        $this->logger->info('Running tests');

        if ($this->config['suites']['unit']) {
            $this->runUnitTests();
        }

        if ($this->config['suites']['integration']) {
            $this->runIntegrationTests();
        }

        if ($this->config['suites']['e2e']) {
            $this->runE2ETests();
        }

        $this->generateCoverageReport();
        $this->checkPerformance();
        $this->reportResults();
    }

    private function runUnitTests(): void {
        $this->logger->info('Running unit tests');

        $command = 'phpunit --testsuite Unit';
        $this->executeTestCommand($command, 'unit');
    }

    private function runIntegrationTests(): void {
        $this->logger->info('Running integration tests');

        $command = 'phpunit --testsuite Integration';
        $this->executeTestCommand($command, 'integration');
    }

    private function runE2ETests(): void {
        $this->logger->info('Running end-to-end tests');

        $command = 'phpunit --testsuite Feature';
        $this->executeTestCommand($command, 'e2e');
    }

    private function executeTestCommand(string $command, string $suite): void {
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        $this->results[$suite] = [
            'success' => $returnVar === 0,
            'output' => $output,
            'returnVar' => $returnVar
        ];

        if ($returnVar === 0) {
            $this->logger->info("{$suite} tests passed");
        } else {
            $this->logger->error("{$suite} tests failed", ['output' => $output]);
        }
    }

    private function generateCoverageReport(): void {
        if (!$this->config['coverage']['enabled']) {
            return;
        }

        $this->logger->info('Generating coverage report');

        $command = 'phpunit --coverage-html coverage';
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->logger->info('Coverage report generated');
        } else {
            $this->logger->error('Failed to generate coverage report', ['output' => $output]);
        }
    }

    private function checkPerformance(): void {
        if (!$this->config['performance']['enabled']) {
            return;
        }

        $this->logger->info('Checking test performance');

        $threshold = $this->config['performance']['threshold'];
        $command = "phpunit --log-junit junit.xml";
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists('junit.xml')) {
            $xml = simplexml_load_file('junit.xml');
            $totalTime = 0;

            foreach ($xml->testsuite as $suite) {
                $totalTime += (float) $suite['time'];
            }

            if ($totalTime > $threshold) {
                $this->logger->warning('Test performance below threshold', [
                    'totalTime' => $totalTime,
                    'threshold' => $threshold
                ]);
            } else {
                $this->logger->info('Test performance within threshold', [
                    'totalTime' => $totalTime,
                    'threshold' => $threshold
                ]);
            }
        }
    }

    private function reportResults(): void {
        $this->logger->info('Test results summary');

        foreach ($this->results as $suite => $result) {
            $status = $result['success'] ? 'PASSED' : 'FAILED';
            $this->logger->info("{$suite} tests: {$status}");
        }

        if ($this->config['coverage']['enabled']) {
            $this->reportCoverage();
        }
    }

    private function reportCoverage(): void {
        if (!file_exists('coverage/index.html')) {
            return;
        }

        $threshold = $this->config['coverage']['threshold'];
        $command = 'phpunit --coverage-text';
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            foreach ($output as $line) {
                if (preg_match('/Lines:\s+(\d+\.\d+)%/', $line, $matches)) {
                    $coverage = (float) $matches[1];
                    if ($coverage < $threshold) {
                        $this->logger->warning('Code coverage below threshold', [
                            'coverage' => $coverage,
                            'threshold' => $threshold
                        ]);
                    } else {
                        $this->logger->info('Code coverage within threshold', [
                            'coverage' => $coverage,
                            'threshold' => $threshold
                        ]);
                    }
                    break;
                }
            }
        }
    }

    public function getResults(): array {
        return $this->results;
    }
} 