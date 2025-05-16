<?php

namespace MCP\Agents\Development\CodeAnalysis;

use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;

class CodeCoverageAnalysisAgent extends BaseCodeAnalysisAgent
{
    private CodeCoverage $coverage;
    private array $metrics = [];
    private array $recommendations = [];
    private array $report = [];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
        
        $filter = new Filter();
        $this->coverage = new CodeCoverage(
            (new Selector)->forLineCoverage($filter),
            $filter
        );

        $this->metrics = [
            'line_coverage' => 0,
            'branch_coverage' => 0,
            'function_coverage' => 0,
            'class_coverage' => 0,
            'method_coverage' => 0
        ];
    }

    protected function getMetrics(): array
    {
        return $this->metrics;
    }

    public function analyze(array $files): array
    {
        $this->logger->info('Starting code coverage analysis');
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning("File not found: {$file}");
                continue;
            }

            $this->analyzeFile($file);
        }

        $this->calculateMetrics();
        $this->generateRecommendations();
        $this->generateReport();

        return $this->report;
    }

    private function analyzeFile(string $file): void
    {
        try {
            $this->coverage->filter()->addFileToWhitelist($file);
            $this->coverage->start($file);
            
            // Execute the file to collect coverage data
            include $file;
            
            $this->coverage->stop();
        } catch (\Exception $e) {
            $this->logger->error("Error analyzing file {$file}: " . $e->getMessage());
        }
    }

    private function calculateMetrics(): void
    {
        $report = $this->coverage->getReport();
        
        $this->metrics['line_coverage'] = $report->getLineCoverage();
        $this->metrics['branch_coverage'] = $report->getBranchCoverage();
        $this->metrics['function_coverage'] = $report->getFunctionCoverage();
        $this->metrics['class_coverage'] = $report->getClassCoverage();
        $this->metrics['method_coverage'] = $report->getMethodCoverage();
    }

    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    private function generateRecommendations(): void
    {
        $this->recommendations = [
            'line_coverage' => $this->generateLineCoverageRecommendations(),
            'branch_coverage' => $this->generateBranchCoverageRecommendations(),
            'function_coverage' => $this->generateFunctionCoverageRecommendations(),
            'class_coverage' => $this->generateClassCoverageRecommendations(),
            'method_coverage' => $this->generateMethodCoverageRecommendations()
        ];
    }

    private function generateLineCoverageRecommendations(): array
    {
        $recommendations = [];
        $targetCoverage = 80; // Target line coverage percentage

        if ($this->metrics['line_coverage'] < $targetCoverage) {
            $recommendations[] = "Increase line coverage to at least {$targetCoverage}%";
            $recommendations[] = "Focus on uncovered lines in critical paths";
            $recommendations[] = "Add test cases for uncovered lines";
        }

        return $recommendations;
    }

    private function generateBranchCoverageRecommendations(): array
    {
        $recommendations = [];
        $targetCoverage = 70; // Target branch coverage percentage

        if ($this->metrics['branch_coverage'] < $targetCoverage) {
            $recommendations[] = "Increase branch coverage to at least {$targetCoverage}%";
            $recommendations[] = "Add test cases for uncovered branches";
            $recommendations[] = "Focus on critical decision points";
        }

        return $recommendations;
    }

    private function generateFunctionCoverageRecommendations(): array
    {
        $recommendations = [];
        $targetCoverage = 90; // Target function coverage percentage

        if ($this->metrics['function_coverage'] < $targetCoverage) {
            $recommendations[] = "Increase function coverage to at least {$targetCoverage}%";
            $recommendations[] = "Add test cases for uncovered functions";
            $recommendations[] = "Focus on utility functions";
        }

        return $recommendations;
    }

    private function generateClassCoverageRecommendations(): array
    {
        $recommendations = [];
        $targetCoverage = 85; // Target class coverage percentage

        if ($this->metrics['class_coverage'] < $targetCoverage) {
            $recommendations[] = "Increase class coverage to at least {$targetCoverage}%";
            $recommendations[] = "Add test cases for uncovered classes";
            $recommendations[] = "Focus on core business logic classes";
        }

        return $recommendations;
    }

    private function generateMethodCoverageRecommendations(): array
    {
        $recommendations = [];
        $targetCoverage = 85; // Target method coverage percentage

        if ($this->metrics['method_coverage'] < $targetCoverage) {
            $recommendations[] = "Increase method coverage to at least {$targetCoverage}%";
            $recommendations[] = "Add test cases for uncovered methods";
            $recommendations[] = "Focus on public methods";
        }

        return $recommendations;
    }

    public function getReport(): array
    {
        return $this->report;
    }

    private function generateReport(): void
    {
        $this->report = [
            'metrics' => $this->metrics,
            'recommendations' => $this->recommendations,
            'summary' => $this->generateSummary(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function generateSummary(): array
    {
        return [
            'overall_coverage' => $this->calculateOverallCoverage(),
            'critical_areas' => $this->identifyCriticalAreas(),
            'improvement_areas' => $this->identifyImprovementAreas()
        ];
    }

    private function calculateOverallCoverage(): float
    {
        return array_sum($this->metrics) / count($this->metrics);
    }

    private function identifyCriticalAreas(): array
    {
        $criticalAreas = [];
        $threshold = 50; // Coverage threshold for critical areas

        foreach ($this->metrics as $type => $coverage) {
            if ($coverage < $threshold) {
                $criticalAreas[] = [
                    'type' => $type,
                    'coverage' => $coverage,
                    'threshold' => $threshold
                ];
            }
        }

        return $criticalAreas;
    }

    private function identifyImprovementAreas(): array
    {
        $improvementAreas = [];
        $threshold = 80; // Coverage threshold for improvement areas

        foreach ($this->metrics as $type => $coverage) {
            if ($coverage < $threshold) {
                $improvementAreas[] = [
                    'type' => $type,
                    'coverage' => $coverage,
                    'threshold' => $threshold
                ];
            }
        }

        return $improvementAreas;
    }
} 