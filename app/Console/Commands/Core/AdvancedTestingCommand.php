<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Advanced Testing Command
 * 
 * Analyzes and improves test coverage.
 */
class AdvancedTestingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testing:analyze {--generate : Generate missing tests} {--coverage : Show coverage details} {--detailed : Show detailed analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and improve test coverage';

    /**
     * Analysis results
     *
     * @var array<string, mixed>
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧪 Starting Advanced Testing Analysis...');
        
        $this->analyzeTesting();
        $this->displayResults();
        
        if ($this->option('generate')) {
            $this->generateMissingTests();
        }
        
        $this->info('✅ Advanced testing analysis completed');
        
        return Command::SUCCESS;
    }

    /**
     * Analyze testing
     */
    private function analyzeTesting(): void
    {
        $this->results = [
            'timestamp' => now()->toISOString(),
            'coverage' => $this->analyzeCoverage(),
            'test_quality' => $this->analyzeTestQuality(),
            'missing_tests' => $this->analyzeMissingTests(),
            'test_patterns' => $this->analyzeTestPatterns(),
            'performance' => $this->analyzeTestPerformance(),
            'recommendations' => $this->generateTestRecommendations(),
        ];
    }

    /**
     * Analyze test coverage
     *
     * @return array<string, mixed>
     */
    private function analyzeCoverage(): array
    {
        $classes = $this->findClasses();
        $testFiles = $this->findTestFiles();
        $testedClasses = [];
        $untestedClasses = [];
        
        // Map test files to classes
        foreach ($testFiles as $testFile) {
            $content = File::get($testFile);
            preg_match_all('/class\s+(\w+Test)/', $content, $matches);
            foreach ($matches[1] as $testClass) {
                $testedClasses[] = $testClass;
            }
        }
        
        // Find untested classes
        foreach ($classes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            $testClass = $shortName . 'Test';
            
            if (!in_array($testClass, $testedClasses)) {
                $untestedClasses[] = [
                    'class' => $class,
                    'test_class' => $testClass,
                    'type' => $this->getClassType($class),
                ];
            }
        }
        
        $coveragePercentage = count($classes) > 0 ? ((count($classes) - count($untestedClasses)) / count($classes)) * 100 : 100;
        
        // Analyze coverage by type
        $coverageByType = $this->analyzeCoverageByType($classes, $untestedClasses);
        
        return [
            'total_classes' => count($classes),
            'tested_classes' => count($classes) - count($untestedClasses),
            'untested_classes' => count($untestedClasses),
            'coverage_percentage' => round($coveragePercentage, 2),
            'untested_classes_list' => $untestedClasses,
            'coverage_by_type' => $coverageByType,
            'grade' => $this->calculateCoverageGrade($coveragePercentage),
        ];
    }

    /**
     * Analyze test quality
     *
     * @return array<string, mixed>
     */
    private function analyzeTestQuality(): array
    {
        $testFiles = $this->findTestFiles();
        $qualityIssues = [];
        $qualityStrengths = [];
        
        foreach ($testFiles as $testFile) {
            $content = File::get($testFile);
            $className = $this->extractClassName($testFile);
            
            // Check for test methods
            preg_match_all('/public function test(\w+)/', $content, $matches);
            $testMethods = $matches[1] ?? [];
            
            if (empty($testMethods)) {
                $qualityIssues[] = [
                    'type' => 'no_test_methods',
                    'file' => $testFile,
                    'description' => 'Test class has no test methods',
                ];
            } else {
                $qualityStrengths[] = "Test class {$className} has " . count($testMethods) . " test methods";
            }
            
            // Check for assertions
            $assertionCount = substr_count($content, 'assert');
            if ($assertionCount === 0) {
                $qualityIssues[] = [
                    'type' => 'no_assertions',
                    'file' => $testFile,
                    'description' => 'Test class has no assertions',
                ];
            }
            
            // Check for proper test naming
            foreach ($testMethods as $method) {
                if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $method)) {
                    $qualityIssues[] = [
                        'type' => 'poor_test_naming',
                        'file' => $testFile,
                        'method' => $method,
                        'description' => 'Test method has poor naming convention',
                    ];
                }
            }
            
            // Check for test isolation
            if (strpos($content, 'setUp()') === false && strpos($content, 'tearDown()') === false) {
                $qualityIssues[] = [
                    'type' => 'no_test_isolation',
                    'file' => $testFile,
                    'description' => 'Test class lacks proper setup/teardown',
                ];
            } else {
                $qualityStrengths[] = "Test class {$className} has proper isolation";
            }
        }
        
        return [
            'quality_issues' => $qualityIssues,
            'quality_strengths' => $qualityStrengths,
            'total_issues' => count($qualityIssues),
            'grade' => $this->calculateQualityGrade(count($qualityIssues)),
        ];
    }

    /**
     * Analyze missing tests
     *
     * @return array<string, mixed>
     */
    private function analyzeMissingTests(): array
    {
        $classes = $this->findClasses();
        $testFiles = $this->findTestFiles();
        $missingTests = [];
        
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $shortName = $reflection->getShortName();
            $testClass = $shortName . 'Test';
            $testFile = base_path("tests/Unit/{$testClass}.php");
            
            if (!File::exists($testFile)) {
                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $testableMethods = array_filter($methods, function($method) {
                    return !$method->isConstructor() && !$method->isDestructor() && !$method->isStatic();
                });
                
                $missingTests[] = [
                    'class' => $class,
                    'test_class' => $testClass,
                    'test_file' => $testFile,
                    'methods' => array_map(fn($m) => $m->getName(), $testableMethods),
                    'method_count' => count($testableMethods),
                    'type' => $this->getClassType($class),
                    'priority' => $this->getTestPriority($class),
                ];
            }
        }
        
        return [
            'missing_tests' => $missingTests,
            'total_missing' => count($missingTests),
            'high_priority' => count(array_filter($missingTests, fn($t) => $t['priority'] === 'high')),
            'medium_priority' => count(array_filter($missingTests, fn($t) => $t['priority'] === 'medium')),
            'low_priority' => count(array_filter($missingTests, fn($t) => $t['priority'] === 'low')),
        ];
    }

    /**
     * Analyze test patterns
     *
     * @return array<string, mixed>
     */
    private function analyzeTestPatterns(): array
    {
        $testFiles = $this->findTestFiles();
        $patterns = [
            'unit_tests' => 0,
            'integration_tests' => 0,
            'feature_tests' => 0,
            'mocking_usage' => 0,
            'data_providers' => 0,
            'assertions' => 0,
        ];
        
        foreach ($testFiles as $testFile) {
            $content = File::get($testFile);
            $className = $this->extractClassName($testFile);
            
            // Determine test type
            if (strpos($className, 'Unit') !== false || strpos($testFile, '/Unit/') !== false) {
                $patterns['unit_tests']++;
            } elseif (strpos($className, 'Integration') !== false || strpos($testFile, '/Integration/') !== false) {
                $patterns['integration_tests']++;
            } elseif (strpos($className, 'Feature') !== false || strpos($testFile, '/Feature/') !== false) {
                $patterns['feature_tests']++;
            }
            
            // Check for mocking
            if (strpos($content, 'Mockery') !== false || strpos($content, 'mock(') !== false) {
                $patterns['mocking_usage']++;
            }
            
            // Check for data providers
            if (preg_match('/@dataProvider/', $content)) {
                $patterns['data_providers']++;
            }
            
            // Count assertions
            $patterns['assertions'] += substr_count($content, 'assert');
        }
        
        return [
            'patterns' => $patterns,
            'total_test_files' => count($testFiles),
            'average_assertions_per_test' => count($testFiles) > 0 ? round($patterns['assertions'] / count($testFiles), 2) : 0,
            'grade' => $this->calculatePatternGrade($patterns),
        ];
    }

    /**
     * Analyze test performance
     *
     * @return array<string, mixed>
     */
    private function analyzeTestPerformance(): array
    {
        $testFiles = $this->findTestFiles();
        $performanceIssues = [];
        $performanceStrengths = [];
        
        foreach ($testFiles as $testFile) {
            $content = File::get($testFile);
            $className = $this->extractClassName($testFile);
            
            // Check for slow operations
            $slowOperations = [
                'database' => strpos($content, 'DB::') !== false,
                'file_operations' => strpos($content, 'File::') !== false || strpos($content, 'file_') !== false,
                'network' => strpos($content, 'Http::') !== false || strpos($content, 'curl') !== false,
                'heavy_computation' => strpos($content, 'for(') !== false && strpos($content, 'foreach(') !== false,
            ];
            
            $slowCount = array_sum($slowOperations);
            if ($slowCount > 2) {
                $performanceIssues[] = [
                    'type' => 'slow_test',
                    'file' => $testFile,
                    'description' => 'Test contains multiple slow operations',
                    'operations' => array_keys(array_filter($slowOperations)),
                ];
            } else {
                $performanceStrengths[] = "Test class {$className} has good performance";
            }
            
            // Check for proper mocking
            if (array_sum($slowOperations) > 0 && strpos($content, 'Mockery') === false) {
                $performanceIssues[] = [
                    'type' => 'missing_mocking',
                    'file' => $testFile,
                    'description' => 'Slow operations not properly mocked',
                ];
            }
        }
        
        return [
            'performance_issues' => $performanceIssues,
            'performance_strengths' => $performanceStrengths,
            'total_issues' => count($performanceIssues),
            'grade' => $this->calculatePerformanceGrade(count($performanceIssues)),
        ];
    }

    /**
     * Generate test recommendations
     *
     * @return array<string, mixed>
     */
    private function generateTestRecommendations(): array
    {
        $recommendations = [];
        
        // Coverage recommendations
        if (isset($this->results['coverage']['untested_classes_list'])) {
            foreach ($this->results['coverage']['untested_classes_list'] as $untested) {
                $recommendations[] = [
                    'type' => 'missing_test',
                    'priority' => $this->getTestPriority($untested['class']),
                    'description' => "Missing test for {$untested['class']}",
                    'action' => "Create {$untested['test_class']}",
                    'class' => $untested['class'],
                    'methods' => $untested['methods'] ?? [],
                ];
            }
        }
        
        // Quality recommendations
        if (isset($this->results['test_quality']['quality_issues'])) {
            foreach ($this->results['test_quality']['quality_issues'] as $issue) {
                $recommendations[] = [
                    'type' => 'quality_issue',
                    'priority' => 'medium',
                    'description' => $issue['description'],
                    'action' => $this->getQualityAction($issue['type']),
                    'file' => $issue['file'],
                ];
            }
        }
        
        // Performance recommendations
        if (isset($this->results['test_performance']['performance_issues'])) {
            foreach ($this->results['test_performance']['performance_issues'] as $issue) {
                $recommendations[] = [
                    'type' => 'performance_issue',
                    'priority' => 'medium',
                    'description' => $issue['description'],
                    'action' => $this->getPerformanceAction($issue['type']),
                    'file' => $issue['file'],
                ];
            }
        }
        
        return [
            'recommendations' => $recommendations,
            'total_recommendations' => count($recommendations),
            'missing_tests' => count(array_filter($recommendations, fn($r) => $r['type'] === 'missing_test')),
            'quality_issues' => count(array_filter($recommendations, fn($r) => $r['type'] === 'quality_issue')),
            'performance_issues' => count(array_filter($recommendations, fn($r) => $r['type'] === 'performance_issue')),
        ];
    }

    /**
     * Display results
     */
    private function displayResults(): void
    {
        $this->newLine();
        $this->info('🧪 Testing Analysis Results');
        $this->info('Generated: ' . $this->results['timestamp']);
        $this->newLine();

        $this->displaySection('Test Coverage', $this->results['coverage']);
        $this->displaySection('Test Quality', $this->results['test_quality']);
        $this->displaySection('Missing Tests', $this->results['missing_tests']);
        $this->displaySection('Test Patterns', $this->results['test_patterns']);
        $this->displaySection('Test Performance', $this->results['test_performance']);
        $this->displaySection('Test Recommendations', $this->results['recommendations']);

        if ($this->option('detailed')) {
            $this->displayDetailedResults();
        }
    }

    /**
     * Display a section of results
     *
     * @param string $title
     * @param array<string, mixed> $data
     */
    private function displaySection(string $title, array $data): void
    {
        $this->info("🧪 {$title}");
        
        if (isset($data['grade'])) {
            $grade = $data['grade'];
            $color = $this->getGradeColor($grade);
            $this->line("  Grade: {$color}{$grade}{$this->resetColor()}");
        }

        if (isset($data['coverage_percentage'])) {
            $this->line("  Coverage: {$data['coverage_percentage']}%");
        }

        if (isset($data['total_classes'])) {
            $this->line("  Total Classes: {$data['total_classes']}");
        }

        if (isset($data['tested_classes'])) {
            $this->line("  Tested Classes: {$data['tested_classes']}");
        }

        if (isset($data['untested_classes'])) {
            $this->line("  Untested Classes: {$data['untested_classes']}");
        }

        if (isset($data['total_missing']) && $data['total_missing'] > 0) {
            $this->warn("  ⚠️  Found {$data['total_missing']} missing tests");
        }

        if (isset($data['total_issues']) && $data['total_issues'] > 0) {
            $this->warn("  ⚠️  Found {$data['total_issues']} quality issues");
        }

        $this->newLine();
    }

    /**
     * Display detailed results
     */
    private function displayDetailedResults(): void
    {
        $this->info('📋 Detailed Testing Analysis');
        
        // Display untested classes
        if (isset($this->results['coverage']['untested_classes_list'])) {
            $this->info('  Untested Classes:');
            foreach ($this->results['coverage']['untested_classes_list'] as $untested) {
                $priorityColor = $this->getPriorityColor($untested['priority']);
                $this->line("    {$priorityColor}[{$untested['priority']}]{$this->resetColor()} {$untested['class']}");
                $this->line("      Type: {$untested['type']}");
                $this->line("      Methods: " . implode(', ', $untested['methods'] ?? []));
            }
        }
        
        // Display quality issues
        if (isset($this->results['test_quality']['quality_issues'])) {
            $this->info('  Quality Issues:');
            foreach ($this->results['test_quality']['quality_issues'] as $issue) {
                $this->line("    - {$issue['description']}");
                $this->line("      File: {$issue['file']}");
            }
        }
        
        // Display recommendations
        if (isset($this->results['recommendations']['recommendations'])) {
            $this->info('  Recommendations:');
            foreach ($this->results['recommendations']['recommendations'] as $recommendation) {
                $priorityColor = $this->getPriorityColor($recommendation['priority']);
                $this->line("    {$priorityColor}[{$recommendation['priority']}]{$this->resetColor()} {$recommendation['description']}");
                $this->line("      Action: {$recommendation['action']}");
            }
        }
    }

    /**
     * Generate missing tests
     */
    private function generateMissingTests(): void
    {
        $this->info('🔧 Generating missing tests...');
        
        $generated = 0;
        
        if (isset($this->results['recommendations']['recommendations'])) {
            foreach ($this->results['recommendations']['recommendations'] as $recommendation) {
                if ($recommendation['type'] === 'missing_test' && $recommendation['priority'] === 'high') {
                    $this->generateTest($recommendation);
                    $generated++;
                }
            }
        }
        
        $this->info("✅ Generated {$generated} test files");
    }

    /**
     * Generate a test file
     *
     * @param array<string, mixed> $recommendation
     */
    private function generateTest(array $recommendation): void
    {
        $className = $recommendation['class'];
        $testClassName = $recommendation['action'];
        $methods = $recommendation['methods'] ?? [];
        
        $testContent = $this->generateTestContent($className, $testClassName, $methods);
        $testFile = base_path("tests/Unit/{$testClassName}.php");
        
        if (!File::exists($testFile)) {
            File::put($testFile, $testContent);
            Log::info("Generated test file: {$testFile}");
        }
    }

    /**
     * Generate test content
     *
     * @param string $className
     * @param string $testClassName
     * @param array<string> $methods
     * @return string
     */
    private function generateTestContent(string $className, string $testClassName, array $methods): string
    {
        $namespace = 'Tests\Unit';
        $shortName = (new \ReflectionClass($className))->getShortName();
        
        $testMethods = '';
        foreach ($methods as $method) {
            $testMethods .= $this->generateTestMethod($method);
        }
        
        return <<<PHP
<?php

namespace {$namespace};

use Tests\TestCase;
use {$className};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {$testClassName} extends TestCase
{
    use RefreshDatabase;

    protected {$shortName} \${$shortName};

    protected function setUp(): void
    {
        parent::setUp();
        \$this->{$shortName} = new {$shortName}();
    }

{$testMethods}
}
PHP;
    }

    /**
     * Generate test method
     *
     * @param string $method
     * @return string
     */
    private function generateTestMethod(string $method): string
    {
        $testMethodName = 'test' . ucfirst($method);
        
        return <<<PHP
    /**
     * Test {$method} method
     */
    public function {$testMethodName}(): void
    {
        // TODO: Implement test for {$method} method
        \$this->markTestIncomplete('Test not implemented yet');
    }

PHP;
    }

    // Helper methods...

    /**
     * Find all PHP classes
     *
     * @return array<string>
     */
    private function findClasses(): array
    {
        $classes = [];
        $files = File::glob(base_path('app/**/*.php'));
        
        foreach ($files as $file) {
            $content = File::get($file);
            preg_match_all('/class\s+(\w+)/', $content, $matches);
            foreach ($matches[1] as $className) {
                $classes[] = $className;
            }
        }
        
        return $classes;
    }

    /**
     * Find test files
     *
     * @return array<string>
     */
    private function findTestFiles(): array
    {
        return File::glob(base_path('tests/**/*.php'));
    }

    /**
     * Get class type
     *
     * @param string $class
     * @return string
     */
    private function getClassType(string $class): string
    {
        if (strpos($class, 'Controller') !== false) return 'Controller';
        if (strpos($class, 'Service') !== false) return 'Service';
        if (strpos($class, 'Repository') !== false) return 'Repository';
        if (strpos($class, 'Model') !== false) return 'Model';
        return 'Class';
    }

    /**
     * Get test priority
     *
     * @param string $class
     * @return string
     */
    private function getTestPriority(string $class): string
    {
        if (strpos($class, 'Controller') !== false) return 'high';
        if (strpos($class, 'Service') !== false) return 'high';
        if (strpos($class, 'Repository') !== false) return 'medium';
        if (strpos($class, 'Model') !== false) return 'low';
        return 'medium';
    }

    /**
     * Extract class name from file
     *
     * @param string $file
     * @return string
     */
    private function extractClassName(string $file): string
    {
        $content = File::get($file);
        preg_match('/class\s+(\w+)/', $content, $matches);
        return $matches[1] ?? 'Unknown';
    }

    /**
     * Analyze coverage by type
     *
     * @param array<string> $classes
     * @param array<string, mixed> $untestedClasses
     * @return array<string, mixed>
     */
    private function analyzeCoverageByType(array $classes, array $untestedClasses): array
    {
        $coverageByType = [];
        
        foreach ($classes as $class) {
            $type = $this->getClassType($class);
            if (!isset($coverageByType[$type])) {
                $coverageByType[$type] = ['total' => 0, 'tested' => 0];
            }
            $coverageByType[$type]['total']++;
        }
        
        foreach ($untestedClasses as $untested) {
            $type = $untested['type'];
            if (isset($coverageByType[$type])) {
                $coverageByType[$type]['tested'] = $coverageByType[$type]['total'] - 1;
            }
        }
        
        return $coverageByType;
    }

    /**
     * Calculate grades
     */
    private function calculateCoverageGrade(float $percentage): string
    {
        if ($percentage >= 95) return 'A+';
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    private function calculateQualityGrade(int $issues): string
    {
        if ($issues === 0) return 'A+';
        if ($issues <= 2) return 'A';
        if ($issues <= 5) return 'B';
        if ($issues <= 10) return 'C';
        if ($issues <= 20) return 'D';
        return 'F';
    }

    private function calculatePatternGrade(array $patterns): string
    {
        $score = 100;
        
        if ($patterns['unit_tests'] < 5) $score -= 20;
        if ($patterns['integration_tests'] < 2) $score -= 15;
        if ($patterns['feature_tests'] < 2) $score -= 15;
        if ($patterns['mocking_usage'] < 3) $score -= 10;
        
        return $this->scoreToGrade($score);
    }

    private function calculatePerformanceGrade(int $issues): string
    {
        if ($issues === 0) return 'A+';
        if ($issues <= 2) return 'A';
        if ($issues <= 5) return 'B';
        if ($issues <= 10) return 'C';
        if ($issues <= 20) return 'D';
        return 'F';
    }

    private function scoreToGrade(int $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    private function getQualityAction(string $type): string
    {
        return match($type) {
            'no_test_methods' => 'Add test methods to the class',
            'no_assertions' => 'Add assertions to test methods',
            'poor_test_naming' => 'Follow proper test naming conventions',
            'no_test_isolation' => 'Add setUp() and tearDown() methods',
            default => 'Review and improve test quality',
        };
    }

    private function getPerformanceAction(string $type): string
    {
        return match($type) {
            'slow_test' => 'Optimize slow operations or add mocking',
            'missing_mocking' => 'Add proper mocking for slow operations',
            default => 'Review and optimize test performance',
        };
    }

    private function getGradeColor(string $grade): string
    {
        return match($grade) {
            'A+' => "\033[32m", // Green
            'A' => "\033[36m",  // Cyan
            'B' => "\033[33m",  // Yellow
            'C' => "\033[35m",  // Magenta
            'D' => "\033[31m",  // Red
            'F' => "\033[31m",  // Red
            default => "\033[0m", // Reset
        ];
    }

    private function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'high' => "\033[31m", // Red
            'medium' => "\033[33m", // Yellow
            'low' => "\033[36m", // Cyan
            default => "\033[0m", // Reset
        };
    }

    private function resetColor(): string
    {
        return "\033[0m";
    }
} 