<?php

namespace App\Analysis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @laravel-simulation
 * @component-type Analysis
 * @test-coverage tests/Feature/Analysis/CodeQualityAnalyzerTest.php
 * @api-docs docs/api/analysis.yaml
 * @security-review docs/security/analysis.md
 * @qa-status Complete
 * @job-code ANA-002
 * @since 1.0.0
 * @author System
 * @package App\Analysis
 * 
 * CodeQualityAnalyzer evaluates code quality metrics and generates reports.
 * Analyzes classes, methods, and properties for best practices and standards.
 * 
 * @OpenAPI\Tag(name="Analysis", description="Code quality analysis system")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"target_path"},
 *     properties={
 *         @OpenAPI\Property(property="target_path", type="string", format="path"),
 *         @OpenAPI\Property(property="include_metrics", type="boolean", default=true),
 *         @OpenAPI\Property(property="include_suggestions", type="boolean", default=true)
 *     }
 * )
 */
class CodeQualityAnalyzer
{
    /**
     * The path to analyze.
     *
     * @var string
     */
    protected string $targetPath;

    /**
     * Whether to include metrics in the analysis.
     *
     * @var bool
     */
    protected bool $includeMetrics;

    /**
     * Whether to include suggestions in the analysis.
     *
     * @var bool
     */
    protected bool $includeSuggestions;

    /**
     * Create a new code quality analyzer instance.
     *
     * @param string $targetPath
     * @param bool $includeMetrics
     * @param bool $includeSuggestions
     * @return void
     */
    public function __construct(
        string $targetPath,
        bool $includeMetrics = true,
        bool $includeSuggestions = true
    ) {
        $this->targetPath = $targetPath;
        $this->includeMetrics = $includeMetrics;
        $this->includeSuggestions = $includeSuggestions;
    }

    /**
     * Analyze the code quality.
     *
     * @return array
     */
    public function analyze(): array
    {
        $results = [
            'timestamp' => now()->toIso8601String(),
            'target_path' => $this->targetPath,
            'classes' => [],
        ];

        $files = $this->getPhpFiles();
        foreach ($files as $file) {
            $classResults = $this->analyzeClass($file);
            if ($classResults) {
                $results['classes'][] = $classResults;
            }
        }

        if ($this->includeMetrics) {
            $results['metrics'] = $this->calculateMetrics($results['classes']);
        }

        if ($this->includeSuggestions) {
            $results['suggestions'] = $this->generateSuggestions($results['classes']);
        }

        return $results;
    }

    /**
     * Get all PHP files in the target path.
     *
     * @return array
     */
    protected function getPhpFiles(): array
    {
        return File::glob($this->targetPath . '/**/*.php');
    }

    /**
     * Analyze a single class file.
     *
     * @param string $file
     * @return array|null
     */
    protected function analyzeClass(string $file): ?array
    {
        try {
            $className = $this->getClassNameFromFile($file);
            if (!$className) {
                return null;
            }

            $reflection = new ReflectionClass($className);
            return [
                'name' => $className,
                'file' => $file,
                'metrics' => $this->analyzeClassMetrics($reflection),
                'methods' => $this->analyzeMethods($reflection),
                'properties' => $this->analyzeProperties($reflection),
                'docblock' => $this->analyzeDocblock($reflection),
            ];
        } catch (\ReflectionException $e) {
            Log::warning('Failed to analyze class', [
                'file' => $file,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the class name from a file.
     *
     * @param string $file
     * @return string|null
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = File::get($file);
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            $namespace = $matches[1];
            if (preg_match('/class\s+(\w+)/i', $content, $matches)) {
                return $namespace . '\\' . $matches[1];
            }
        }
        return null;
    }

    /**
     * Analyze class metrics.
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeClassMetrics(ReflectionClass $reflection): array
    {
        return [
            'lines_of_code' => $this->countLinesOfCode($reflection),
            'method_count' => count($reflection->getMethods()),
            'property_count' => count($reflection->getProperties()),
            'constant_count' => count($reflection->getConstants()),
            'trait_count' => count($reflection->getTraits()),
            'interface_count' => count($reflection->getInterfaces()),
            'inheritance_depth' => $this->calculateInheritanceDepth($reflection),
            'complexity' => $this->calculateComplexity($reflection),
        ];
    }

    /**
     * Analyze class methods.
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            $methods[] = [
                'name' => $method->getName(),
                'visibility' => $this->getMethodVisibility($method),
                'parameters' => $this->analyzeMethodParameters($method),
                'return_type' => $method->getReturnType()?->getName(),
                'docblock' => $method->getDocComment(),
                'complexity' => $this->calculateMethodComplexity($method),
                'lines_of_code' => $this->countMethodLinesOfCode($method),
            ];
        }
        return $methods;
    }

    /**
     * Analyze class properties.
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            $properties[] = [
                'name' => $property->getName(),
                'visibility' => $this->getPropertyVisibility($property),
                'type' => $property->getType()?->getName(),
                'docblock' => $property->getDocComment(),
                'has_default' => $property->hasDefaultValue(),
            ];
        }
        return $properties;
    }

    /**
     * Analyze class docblock.
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeDocblock(ReflectionClass $reflection): array
    {
        $docblock = $reflection->getDocComment();
        return [
            'has_docblock' => (bool) $docblock,
            'has_description' => $this->hasDocblockDescription($docblock),
            'has_params' => $this->hasDocblockParams($docblock),
            'has_return' => $this->hasDocblockReturn($docblock),
            'has_throws' => $this->hasDocblockThrows($docblock),
            'has_see' => $this->hasDocblockSee($docblock),
            'has_since' => $this->hasDocblockSince($docblock),
            'has_author' => $this->hasDocblockAuthor($docblock),
        ];
    }

    /**
     * Calculate overall metrics.
     *
     * @param array $classes
     * @return array
     */
    protected function calculateMetrics(array $classes): array
    {
        $metrics = [
            'total_classes' => count($classes),
            'total_methods' => 0,
            'total_properties' => 0,
            'total_lines_of_code' => 0,
            'average_complexity' => 0,
            'average_methods_per_class' => 0,
            'average_properties_per_class' => 0,
            'average_lines_per_class' => 0,
        ];

        foreach ($classes as $class) {
            $metrics['total_methods'] += count($class['methods']);
            $metrics['total_properties'] += count($class['properties']);
            $metrics['total_lines_of_code'] += $class['metrics']['lines_of_code'];
            $metrics['average_complexity'] += $class['metrics']['complexity'];
        }

        if ($metrics['total_classes'] > 0) {
            $metrics['average_complexity'] /= $metrics['total_classes'];
            $metrics['average_methods_per_class'] = $metrics['total_methods'] / $metrics['total_classes'];
            $metrics['average_properties_per_class'] = $metrics['total_properties'] / $metrics['total_classes'];
            $metrics['average_lines_per_class'] = $metrics['total_lines_of_code'] / $metrics['total_classes'];
        }

        return $metrics;
    }

    /**
     * Generate suggestions for improvement.
     *
     * @param array $classes
     * @return array
     */
    protected function generateSuggestions(array $classes): array
    {
        $suggestions = [];

        foreach ($classes as $class) {
            $classSuggestions = [];

            // Check class complexity
            if ($class['metrics']['complexity'] > 10) {
                $classSuggestions[] = [
                    'type' => 'complexity',
                    'message' => 'Class has high complexity. Consider splitting into smaller classes.',
                    'severity' => 'warning',
                ];
            }

            // Check method count
            if ($class['metrics']['method_count'] > 20) {
                $classSuggestions[] = [
                    'type' => 'method_count',
                    'message' => 'Class has many methods. Consider splitting into smaller classes.',
                    'severity' => 'warning',
                ];
            }

            // Check docblock
            if (!$class['docblock']['has_docblock']) {
                $classSuggestions[] = [
                    'type' => 'documentation',
                    'message' => 'Class lacks documentation. Add PHPDoc block.',
                    'severity' => 'info',
                ];
            }

            // Check methods
            foreach ($class['methods'] as $method) {
                if ($method['complexity'] > 5) {
                    $classSuggestions[] = [
                        'type' => 'method_complexity',
                        'message' => "Method {$method['name']} has high complexity. Consider refactoring.",
                        'severity' => 'warning',
                    ];
                }

                if (!$method['docblock']) {
                    $classSuggestions[] = [
                        'type' => 'method_documentation',
                        'message' => "Method {$method['name']} lacks documentation. Add PHPDoc block.",
                        'severity' => 'info',
                    ];
                }
            }

            if (!empty($classSuggestions)) {
                $suggestions[$class['name']] = $classSuggestions;
            }
        }

        return $suggestions;
    }

    /**
     * Count lines of code in a class.
     *
     * @param ReflectionClass $reflection
     * @return int
     */
    protected function countLinesOfCode(ReflectionClass $reflection): int
    {
        $file = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();
        return $end - $start + 1;
    }

    /**
     * Count lines of code in a method.
     *
     * @param ReflectionMethod $method
     * @return int
     */
    protected function countMethodLinesOfCode(ReflectionMethod $method): int
    {
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        return $end - $start + 1;
    }

    /**
     * Calculate inheritance depth.
     *
     * @param ReflectionClass $reflection
     * @return int
     */
    protected function calculateInheritanceDepth(ReflectionClass $reflection): int
    {
        $depth = 0;
        $parent = $reflection->getParentClass();
        while ($parent) {
            $depth++;
            $parent = $parent->getParentClass();
        }
        return $depth;
    }

    /**
     * Calculate class complexity.
     *
     * @param ReflectionClass $reflection
     * @return int
     */
    protected function calculateComplexity(ReflectionClass $reflection): int
    {
        $complexity = 0;
        foreach ($reflection->getMethods() as $method) {
            $complexity += $this->calculateMethodComplexity($method);
        }
        return $complexity;
    }

    /**
     * Calculate method complexity.
     *
     * @param ReflectionMethod $method
     * @return int
     */
    protected function calculateMethodComplexity(ReflectionMethod $method): int
    {
        $content = file_get_contents($method->getFileName());
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $methodContent = implode('', array_slice(explode("\n", $content), $start - 1, $end - $start + 1));

        $complexity = 1;
        $complexity += substr_count($methodContent, 'if');
        $complexity += substr_count($methodContent, 'else');
        $complexity += substr_count($methodContent, 'for');
        $complexity += substr_count($methodContent, 'foreach');
        $complexity += substr_count($methodContent, 'while');
        $complexity += substr_count($methodContent, 'case');
        $complexity += substr_count($methodContent, 'catch');
        $complexity += substr_count($methodContent, '&&');
        $complexity += substr_count($methodContent, '||');

        return $complexity;
    }

    /**
     * Get method visibility.
     *
     * @param ReflectionMethod $method
     * @return string
     */
    protected function getMethodVisibility(ReflectionMethod $method): string
    {
        if ($method->isPublic()) {
            return 'public';
        }
        if ($method->isProtected()) {
            return 'protected';
        }
        return 'private';
    }

    /**
     * Get property visibility.
     *
     * @param ReflectionProperty $property
     * @return string
     */
    protected function getPropertyVisibility(ReflectionProperty $property): string
    {
        if ($property->isPublic()) {
            return 'public';
        }
        if ($property->isProtected()) {
            return 'protected';
        }
        return 'private';
    }

    /**
     * Analyze method parameters.
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function analyzeMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = [
                'name' => $parameter->getName(),
                'type' => $parameter->getType()?->getName(),
                'has_default' => $parameter->isDefaultValueAvailable(),
                'is_optional' => $parameter->isOptional(),
            ];
        }
        return $parameters;
    }

    /**
     * Check if docblock has description.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockDescription(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/\*\s+[^@\n]+/m', $docblock);
    }

    /**
     * Check if docblock has params.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockParams(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/@param\s+/m', $docblock);
    }

    /**
     * Check if docblock has return.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockReturn(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/@return\s+/m', $docblock);
    }

    /**
     * Check if docblock has throws.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockThrows(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/@throws\s+/m', $docblock);
    }

    /**
     * Check if docblock has see.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockSee(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/@see\s+/m', $docblock);
    }

    /**
     * Check if docblock has since.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockSince(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/@since\s+/m', $docblock);
    }

    /**
     * Check if docblock has author.
     *
     * @param string|null $docblock
     * @return bool
     */
    protected function hasDocblockAuthor(?string $docblock): bool
    {
        if (!$docblock) {
            return false;
        }
        return (bool) preg_match('/@author\s+/m', $docblock);
    }
} 