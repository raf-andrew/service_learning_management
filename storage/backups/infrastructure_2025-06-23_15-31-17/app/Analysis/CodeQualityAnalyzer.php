<?php

namespace App\Analysis;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class CodeQualityAnalyzer
{
    protected string $targetPath;
    protected array $results = [];

    public function __construct(string $targetPath)
    {
        $this->targetPath = $targetPath;
    }

    /**
     * Analyze the code quality of PHP files in the target path.
     *
     * @return array
     */
    public function analyze(): array
    {
        $this->results = [
            'timestamp' => now()->toIso8601String(),
            'target_path' => $this->targetPath,
            'classes' => [],
            'metrics' => [
                'total_classes' => 0,
                'total_methods' => 0,
                'total_properties' => 0,
                'total_lines_of_code' => 0,
                'average_complexity' => 0,
                'average_methods_per_class' => 0,
                'average_properties_per_class' => 0,
                'average_lines_per_class' => 0,
            ],
            'suggestions' => [],
        ];

        $this->analyzeDirectory($this->targetPath);
        $this->calculateMetrics();

        return $this->results;
    }

    /**
     * Analyze a directory recursively.
     *
     * @param string $directory
     * @return void
     */
    protected function analyzeDirectory(string $directory): void
    {
        $files = File::files($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $this->analyzeFile($file->getPathname());
            }
        }

        $directories = File::directories($directory);
        foreach ($directories as $subDirectory) {
            $this->analyzeDirectory($subDirectory);
        }
    }

    /**
     * Analyze a PHP file.
     *
     * @param string $filePath
     * @return void
     */
    protected function analyzeFile(string $filePath): void
    {
        try {
            require_once $filePath;
            $classes = $this->getClassesInFile($filePath);

            foreach ($classes as $class) {
                $this->analyzeClass($class);
            }
        } catch (\Throwable $e) {
            // Skip invalid PHP files
        }
    }

    /**
     * Get all classes defined in a file.
     *
     * @param string $filePath
     * @return array
     */
    protected function getClassesInFile(string $filePath): array
    {
        $classes = [];
        $tokens = token_get_all(file_get_contents($filePath));
        $namespace = '';
        $class = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($id, $text) = $token;

                if ($id === T_NAMESPACE) {
                    $namespace = '';
                    $collecting = true;
                } elseif ($id === T_STRING && $collecting) {
                    $namespace .= $text;
                } elseif ($id === T_CLASS) {
                    $collecting = true;
                } elseif ($id === T_STRING && $collecting) {
                    $class = $text;
                    $classes[] = $namespace ? $namespace . '\\' . $class : $class;
                    $collecting = false;
                }
            }
        }

        return $classes;
    }

    /**
     * Analyze a class.
     *
     * @param string $className
     * @return void
     */
    protected function analyzeClass(string $className): void
    {
        try {
            $reflection = new ReflectionClass($className);
            $classData = [
                'name' => $className,
                'metrics' => [],
                'methods' => [],
                'properties' => [],
                'docblock' => $this->parseDocBlock($reflection->getDocComment()),
            ];

            // Analyze methods
            foreach ($reflection->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() === $className) {
                    $classData['methods'][] = $this->analyzeMethod($method);
                }
            }

            // Analyze properties
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() === $className) {
                    $classData['properties'][] = $this->analyzeProperty($property);
                }
            }

            // Calculate class metrics
            $classData['metrics'] = [
                'method_count' => count($classData['methods']),
                'property_count' => count($classData['properties']),
                'complexity' => $this->calculateClassComplexity($classData['methods']),
                'lines_of_code' => $this->calculateClassLinesOfCode($reflection),
            ];

            $this->results['classes'][] = $classData;
            $this->generateSuggestions($classData);
        } catch (\Throwable $e) {
            // Skip classes that can't be analyzed
        }
    }

    /**
     * Analyze a method.
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function analyzeMethod(ReflectionMethod $method): array
    {
        return [
            'name' => $method->getName(),
            'visibility' => $this->getVisibility($method),
            'parameters' => $this->getMethodParameters($method),
            'return_type' => $method->getReturnType() ? $method->getReturnType()->getName() : null,
            'docblock' => $this->parseDocBlock($method->getDocComment()),
            'complexity' => $this->calculateMethodComplexity($method),
            'lines_of_code' => $this->calculateMethodLinesOfCode($method),
        ];
    }

    /**
     * Analyze a property.
     *
     * @param ReflectionProperty $property
     * @return array
     */
    protected function analyzeProperty(ReflectionProperty $property): array
    {
        return [
            'name' => $property->getName(),
            'visibility' => $this->getVisibility($property),
            'type' => $property->getType() ? $property->getType()->getName() : null,
            'docblock' => $this->parseDocBlock($property->getDocComment()),
            'has_default' => $property->hasDefaultValue(),
        ];
    }

    /**
     * Get method parameters.
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function getMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = [
                'name' => $parameter->getName(),
                'type' => $parameter->getType() ? $parameter->getType()->getName() : null,
                'has_default' => $parameter->isDefaultValueAvailable(),
            ];
        }
        return $parameters;
    }

    /**
     * Get visibility of a reflection member.
     *
     * @param \ReflectionMethod|\ReflectionProperty $member
     * @return string
     */
    protected function getVisibility($member): string
    {
        if ($member->isPublic()) {
            return 'public';
        } elseif ($member->isProtected()) {
            return 'protected';
        } else {
            return 'private';
        }
    }

    /**
     * Parse a docblock into an array.
     *
     * @param string|false $docblock
     * @return array
     */
    protected function parseDocBlock($docblock): array
    {
        if (!$docblock) {
            return [];
        }

        $result = [];
        $lines = explode("\n", $docblock);

        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*/");
            if (empty($line)) {
                continue;
            }

            if (strpos($line, '@') === 0) {
                $parts = explode(' ', $line, 2);
                $tag = substr($parts[0], 1);
                $value = $parts[1] ?? '';
                $result[$tag][] = $value;
            } else {
                $result['description'][] = $line;
            }
        }

        return $result;
    }

    /**
     * Calculate method complexity.
     *
     * @param ReflectionMethod $method
     * @return int
     */
    protected function calculateMethodComplexity(ReflectionMethod $method): int
    {
        $complexity = 1; // Base complexity
        $tokens = token_get_all(file_get_contents($method->getFileName()));
        $inMethod = false;
        $braceCount = 0;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            list($id, $text) = $token;

            if ($id === T_FUNCTION && $text === $method->getName()) {
                $inMethod = true;
                continue;
            }

            if ($inMethod) {
                if ($text === '{') {
                    $braceCount++;
                } elseif ($text === '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        break;
                    }
                }

                // Increment complexity for control structures
                if (in_array($id, [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_DO, T_CATCH, T_CASE])) {
                    $complexity++;
                }
            }
        }

        return $complexity;
    }

    /**
     * Calculate class complexity.
     *
     * @param array $methods
     * @return int
     */
    protected function calculateClassComplexity(array $methods): int
    {
        return array_sum(array_column($methods, 'complexity'));
    }

    /**
     * Calculate method lines of code.
     *
     * @param ReflectionMethod $method
     * @return int
     */
    protected function calculateMethodLinesOfCode(ReflectionMethod $method): int
    {
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        return $endLine - $startLine + 1;
    }

    /**
     * Calculate class lines of code.
     *
     * @param ReflectionClass $class
     * @return int
     */
    protected function calculateClassLinesOfCode(ReflectionClass $class): int
    {
        $startLine = $class->getStartLine();
        $endLine = $class->getEndLine();
        return $endLine - $startLine + 1;
    }

    /**
     * Calculate overall metrics.
     *
     * @return void
     */
    protected function calculateMetrics(): void
    {
        $classes = $this->results['classes'];
        $totalClasses = count($classes);

        if ($totalClasses === 0) {
            return;
        }

        $totalMethods = 0;
        $totalProperties = 0;
        $totalComplexity = 0;
        $totalLinesOfCode = 0;

        foreach ($classes as $class) {
            $totalMethods += $class['metrics']['method_count'];
            $totalProperties += $class['metrics']['property_count'];
            $totalComplexity += $class['metrics']['complexity'];
            $totalLinesOfCode += $class['metrics']['lines_of_code'];
        }

        $this->results['metrics'] = [
            'total_classes' => $totalClasses,
            'total_methods' => $totalMethods,
            'total_properties' => $totalProperties,
            'total_lines_of_code' => $totalLinesOfCode,
            'average_complexity' => $totalComplexity / $totalClasses,
            'average_methods_per_class' => $totalMethods / $totalClasses,
            'average_properties_per_class' => $totalProperties / $totalClasses,
            'average_lines_per_class' => $totalLinesOfCode / $totalClasses,
        ];
    }

    /**
     * Generate suggestions for a class.
     *
     * @param array $classData
     * @return void
     */
    protected function generateSuggestions(array $classData): void
    {
        $suggestions = [];

        // Check method complexity
        foreach ($classData['methods'] as $method) {
            if ($method['complexity'] > 10) {
                $suggestions[] = [
                    'type' => 'method_complexity',
                    'message' => "Method {$method['name']} has high complexity ({$method['complexity']})",
                    'severity' => 'warning',
                ];
            }
        }

        // Check class complexity
        if ($classData['metrics']['complexity'] > 50) {
            $suggestions[] = [
                'type' => 'class_complexity',
                'message' => "Class has high complexity ({$classData['metrics']['complexity']})",
                'severity' => 'warning',
            ];
        }

        // Check method count
        if ($classData['metrics']['method_count'] > 20) {
            $suggestions[] = [
                'type' => 'method_count',
                'message' => "Class has many methods ({$classData['metrics']['method_count']})",
                'severity' => 'info',
            ];
        }

        // Check property count
        if ($classData['metrics']['property_count'] > 10) {
            $suggestions[] = [
                'type' => 'property_count',
                'message' => "Class has many properties ({$classData['metrics']['property_count']})",
                'severity' => 'info',
            ];
        }

        // Check documentation
        if (empty($classData['docblock'])) {
            $suggestions[] = [
                'type' => 'documentation',
                'message' => 'Class lacks documentation',
                'severity' => 'warning',
            ];
        }

        if (!empty($suggestions)) {
            $this->results['suggestions'][$classData['name']] = $suggestions;
        }
    }
} 