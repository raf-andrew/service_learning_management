            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'total_tests' => $result->count(),
                'passed_tests' => $result->count() - $result->failureCount() - $result->errorCount(),
                'failed_tests' => $result->failureCount() + $result->errorCount(),
                'coverage_percentage' => $result->getCodeCoverage() ? $result->getCodeCoverage()->getReport()->getNumExecutedLines() : 0
            ],
            'results' => $this->reporter->getTestResults()
        ];
    }

    /**
     * Run all test suites
     *
     * @return array
     */
    public function runAllTests(): array
    {
        $suite = new TestSuite('All Tests');
        
        foreach ($this->testSuites as $name => $directory) {
            if (is_dir($directory)) {
                $suite->addTestSuite($this->discoverTests($directory));
            }
        }

        $result = new TestResult();
        $result->addListener($this->reporter);

        $this->run($suite, $result);
        $this->generateReports($result);

        return [
            'checklist_item' => $this->checklistItem,
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'total_tests' => $result->count(),
                'passed_tests' => $result->count() - $result->failureCount() - $result->errorCount(),
                'failed_tests' => $result->failureCount() + $result->errorCount(),
                'coverage_percentage' => $result->getCodeCoverage() ? $result->getCodeCoverage()->getReport()->getNumExecutedLines() : 0
            ],
            'results' => $this->reporter->getTestResults()
        ];
    }

    /**
     * Discover tests in a directory
     *
     * @param string $directory
     * @return TestSuite
     */
    protected function discoverTests($directory)
    {
        $suite = new TestSuite(basename($directory));
        
        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className && class_exists($className)) {
                $suite->addTestSuite($className);
            }
        }

        return $suite;
    }

    /**
     * Get class name from file
     *
     * @param string $file
     * @return string|null
     */
    protected function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        if (preg_match('/namespace\s+([^;]+);.*?class\s+(\w+)/s', $content, $matches)) {
            return $matches[1] . '\\' . $matches[2];
        }
        return null;
    }

    /**
     * Generate test reports
     *
     * @param TestResult $result
     * @return void
     */
    protected function generateReports(TestResult $result)
    {
        // Generate coverage report
        if ($result->getCodeCoverage()) {
            $this->reporter->addCodeQualityMetric('coverage', $result->getCodeCoverage()->getReport()->getNumExecutedLines());
        }

        // Generate performance metrics
        $this->reporter->addCodeQualityMetric('execution_time', $result->time());
        $this->reporter->addCodeQualityMetric('memory_usage', memory_get_peak_usage(true));

        // Generate test statistics
        $this->reporter->addCodeQualityMetric('total_tests', $result->count());
        $this->reporter->addCodeQualityMetric('passed_tests', $result->count() - $result->failureCount() - $result->errorCount());
        $this->reporter->addCodeQualityMetric('failed_tests', $result->failureCount());
        $this->reporter->addCodeQualityMetric('error_tests', $result->errorCount());

        // Generate security checks
        $this->reporter->addSecurityCheck('test_integrity', $result->wasSuccessful() ? 'passed' : 'failed');
        $this->reporter->addSecurityCheck('test_coverage', $result->getCodeCoverage() ? 'passed' : 'failed');
    }
} 