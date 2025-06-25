#!/usr/bin/env php
<?php

class TestRunner {
    private $coverageThreshold = 100;
    private $hasErrors = false;
    private $reportsDir = __DIR__ . '/reports';

    public function run() {
        echo "🚀 Starting comprehensive test suite...\n\n";
        
        // Create reports directory if it doesn't exist
        if (!file_exists($this->reportsDir)) {
            mkdir($this->reportsDir, 0777, true);
        }

        // Run PHP tests
        $this->runPhpTests();
        
        // Run Vitest tests
        $this->runVitestTests();
        
        // Generate final report
        $this->generateFinalReport();
        
        return !$this->hasErrors;
    }

    private function runPhpTests() {
        echo "📝 Running PHP Tests...\n";
        $command = "php artisan test --coverage-html {$this->reportsDir}/php-coverage";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->hasErrors = true;
            echo "❌ PHP Tests failed!\n";
            echo implode("\n", $output) . "\n";
        } else {
            echo "✅ PHP Tests passed!\n";
        }
    }

    private function runVitestTests() {
        echo "\n📝 Running Vitest Tests...\n";
        $command = "npm run test:coverage";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->hasErrors = true;
            echo "❌ Vitest Tests failed!\n";
            echo implode("\n", $output) . "\n";
        } else {
            echo "✅ Vitest Tests passed!\n";
        }
    }

    private function generateFinalReport() {
        echo "\n📊 Generating Final Report...\n";
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $this->hasErrors ? 'Failed' : 'Passed',
            'coverage' => [
                'php' => $this->getPhpCoverage(),
                'vitest' => $this->getVitestCoverage()
            ]
        ];
        
        file_put_contents(
            "{$this->reportsDir}/final-report.json",
            json_encode($report, JSON_PRETTY_PRINT)
        );
        
        echo "📄 Report generated at {$this->reportsDir}/final-report.json\n";
    }

    private function getPhpCoverage() {
        $coverageFile = "{$this->reportsDir}/php-coverage/index.html";
        if (file_exists($coverageFile)) {
            // Parse coverage from HTML report
            $content = file_get_contents($coverageFile);
            preg_match('/Total Coverage: (\d+\.?\d*)%/', $content, $matches);
            return isset($matches[1]) ? (float)$matches[1] : 0;
        }
        return 0;
    }

    private function getVitestCoverage() {
        $coverageFile = "coverage/coverage-summary.json";
        if (file_exists($coverageFile)) {
            $coverage = json_decode(file_get_contents($coverageFile), true);
            return $coverage['total']['statements']['pct'] ?? 0;
        }
        return 0;
    }
}

// Run the tests
$runner = new TestRunner();
$success = $runner->run();

exit($success ? 0 : 1); 