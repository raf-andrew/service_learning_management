<?php

namespace MCP\Agents\Development\CodeRefactoring;

use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\FindingVisitor;

/**
 * Code Refactoring Agent
 * 
 * This agent is responsible for:
 * - Detecting code smells
 * - Suggesting refactoring opportunities
 * - Performing automated refactoring
 * - Optimizing code
 * - Improving performance
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class CodeRefactoringAgent extends BaseCodeAnalysisAgent
{
    private BuilderFactory $factory;
    private PrettyPrinter\Standard $printer;
    private array $metrics = [];
    private array $recommendations = [];
    private array $report = [];
    private array $codeSmells = [];
    private array $refactoringOpportunities = [];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
        
        $this->factory = new BuilderFactory;
        $this->printer = new PrettyPrinter\Standard;
        
        $this->metrics = [
            'code_smells_detected' => 0,
            'refactoring_opportunities' => 0,
            'automated_refactorings' => 0,
            'optimizations_applied' => 0,
            'performance_improvements' => 0
        ];

        $this->initializeCodeSmellPatterns();
    }

    private function initializeCodeSmellPatterns(): void
    {
        $this->codeSmells = [
            'long_method' => [
                'pattern' => function(Node\Stmt\ClassMethod $node) {
                    return count($node->stmts) > 20;
                },
                'severity' => 'high',
                'description' => 'Method is too long (> 20 statements)',
                'suggestion' => 'Consider extracting parts into smaller methods'
            ],
            'large_class' => [
                'pattern' => function(Node\Stmt\Class_ $node) {
                    return count($node->stmts) > 30;
                },
                'severity' => 'high',
                'description' => 'Class is too large (> 30 members)',
                'suggestion' => 'Consider splitting into smaller classes'
            ],
            'long_parameter_list' => [
                'pattern' => function(Node\Stmt\ClassMethod $node) {
                    return count($node->params) > 5;
                },
                'severity' => 'medium',
                'description' => 'Method has too many parameters (> 5)',
                'suggestion' => 'Consider using parameter objects'
            ],
            'duplicate_code' => [
                'pattern' => function(Node $node) {
                    // Implement duplicate code detection
                    return false;
                },
                'severity' => 'high',
                'description' => 'Duplicate code detected',
                'suggestion' => 'Extract common code into shared methods'
            ],
            'switch_statements' => [
                'pattern' => function(Node $node) {
                    return $node instanceof Node\Stmt\Switch_;
                },
                'severity' => 'low',
                'description' => 'Switch statement found',
                'suggestion' => 'Consider using polymorphism instead'
            ]
        ];
    }

    public function analyze(array $files): array
    {
        $this->logger->info('Starting code refactoring analysis');
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning("File not found: {$file}");
                continue;
            }

            $this->analyzeFile($file);
        }

        $this->generateRecommendations();
        $this->generateReport();

        return $this->report;
    }

    private function analyzeFile(string $file): void
    {
        try {
            $code = file_get_contents($file);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $ast = $parser->parse($code);

            if ($ast === null) {
                $this->logger->warning("Failed to parse file: {$file}");
                return;
            }

            $this->detectCodeSmells($ast, $file);
            $this->identifyRefactoringOpportunities($ast, $file);
            $this->analyzePerformance($ast, $file);
        } catch (\Exception $e) {
            $this->logger->error("Error analyzing file {$file}: " . $e->getMessage());
        }
    }

    private function detectCodeSmells(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use ($file) {
            foreach ($this->codeSmells as $type => $smell) {
                if ($smell['pattern']($node)) {
                    $this->metrics['code_smells_detected']++;
                    $this->refactoringOpportunities[] = [
                        'type' => $type,
                        'file' => $file,
                        'line' => $node->getLine(),
                        'severity' => $smell['severity'],
                        'description' => $smell['description'],
                        'suggestion' => $smell['suggestion']
                    ];
                }
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function identifyRefactoringOpportunities(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use ($file) {
            if ($node instanceof Node\Stmt\ClassMethod) {
                $this->analyzeMethodComplexity($node, $file);
            } elseif ($node instanceof Node\Stmt\Class_) {
                $this->analyzeClassCohesion($node, $file);
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function analyzeMethodComplexity(Node\Stmt\ClassMethod $node, string $file): void
    {
        $complexity = $this->calculateCyclomaticComplexity($node);
        if ($complexity > 10) {
            $this->metrics['refactoring_opportunities']++;
            $this->refactoringOpportunities[] = [
                'type' => 'high_complexity',
                'file' => $file,
                'line' => $node->getLine(),
                'severity' => 'high',
                'description' => "Method has high cyclomatic complexity ({$complexity})",
                'suggestion' => 'Consider breaking down into smaller methods'
            ];
        }
    }

    private function analyzeClassCohesion(Node\Stmt\Class_ $node, string $file): void
    {
        $cohesion = $this->calculateLackOfCohesion($node);
        if ($cohesion > 0.7) {
            $this->metrics['refactoring_opportunities']++;
            $this->refactoringOpportunities[] = [
                'type' => 'low_cohesion',
                'file' => $file,
                'line' => $node->getLine(),
                'severity' => 'medium',
                'description' => 'Class has low cohesion',
                'suggestion' => 'Consider splitting into more focused classes'
            ];
        }
    }

    private function calculateCyclomaticComplexity(Node\Stmt\ClassMethod $node): int
    {
        $complexity = 1;
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use (&$complexity) {
            if (
                $node instanceof Node\Stmt\If_ ||
                $node instanceof Node\Stmt\ElseIf_ ||
                $node instanceof Node\Stmt\Else_ ||
                $node instanceof Node\Stmt\Switch_ ||
                $node instanceof Node\Stmt\Case_ ||
                $node instanceof Node\Stmt\While_ ||
                $node instanceof Node\Stmt\Do_ ||
                $node instanceof Node\Stmt\For_ ||
                $node instanceof Node\Stmt\Foreach_ ||
                $node instanceof Node\Expr\BinaryOp\LogicalAnd ||
                $node instanceof Node\Expr\BinaryOp\LogicalOr ||
                $node instanceof Node\Expr\BinaryOp\BooleanAnd ||
                $node instanceof Node\Expr\BinaryOp\BooleanOr ||
                $node instanceof Node\Expr\Ternary
            ) {
                $complexity++;
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);
        return $complexity;
    }

    private function calculateLackOfCohesion(Node\Stmt\Class_ $node): float
    {
        $methods = [];
        $fields = [];
        
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $methods[$stmt->name->toString()] = [];
                $traverser = new NodeTraverser();
                $visitor = new FindingVisitor(function($node) use (&$methods, $stmt) {
                    if ($node instanceof Node\Expr\PropertyFetch) {
                        $methods[$stmt->name->toString()][] = $node->name->toString();
                    }
                    return false;
                });
                $traverser->addVisitor($visitor);
                $traverser->traverse([$stmt]);
            } elseif ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    $fields[] = $prop->name->toString();
                }
            }
        }
        
        if (empty($methods) || empty($fields)) {
            return 0;
        }
        
        $totalPairs = 0;
        $disjointPairs = 0;
        
        for ($i = 0; $i < count($methods) - 1; $i++) {
            for ($j = $i + 1; $j < count($methods); $j++) {
                $totalPairs++;
                $intersection = array_intersect(
                    $methods[array_keys($methods)[$i]],
                    $methods[array_keys($methods)[$j]]
                );
                if (empty($intersection)) {
                    $disjointPairs++;
                }
            }
        }
        
        return $totalPairs > 0 ? $disjointPairs / $totalPairs : 0;
    }

    private function analyzePerformance(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use ($file) {
            if ($node instanceof Node\Expr\FuncCall) {
                $this->analyzePerformanceCriticalFunction($node, $file);
            } elseif ($node instanceof Node\Stmt\Foreach_) {
                $this->analyzeLoopPerformance($node, $file);
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function analyzePerformanceCriticalFunction(Node\Expr\FuncCall $node, string $file): void
    {
        $performanceCriticalFunctions = [
            'array_merge' => 'Consider using array spread operator (...) for better performance',
            'in_array' => 'Consider using array_key_exists() or isset() for better performance',
            'array_search' => 'Consider using array_key_exists() for better performance',
            'array_map' => 'Consider using foreach for better performance with small arrays',
            'array_filter' => 'Consider using foreach for better performance with small arrays'
        ];

        if ($node->name instanceof Node\Name) {
            $functionName = $node->name->toString();
            if (isset($performanceCriticalFunctions[$functionName])) {
                $this->metrics['performance_improvements']++;
                $this->refactoringOpportunities[] = [
                    'type' => 'performance',
                    'file' => $file,
                    'line' => $node->getLine(),
                    'severity' => 'medium',
                    'description' => "Performance critical function: {$functionName}",
                    'suggestion' => $performanceCriticalFunctions[$functionName]
                ];
            }
        }
    }

    private function analyzeLoopPerformance(Node\Stmt\Foreach_ $node, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) {
            return $node instanceof Node\Expr\FuncCall ||
                   $node instanceof Node\Expr\MethodCall ||
                   $node instanceof Node\Expr\StaticCall;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        if (!empty($visitor->getFoundNodes())) {
            $this->metrics['performance_improvements']++;
            $this->refactoringOpportunities[] = [
                'type' => 'performance',
                'file' => $file,
                'line' => $node->getLine(),
                'severity' => 'medium',
                'description' => 'Function/method calls inside loop',
                'suggestion' => 'Consider moving function/method calls outside the loop if possible'
            ];
        }
    }

    public function refactor(string $file, array $refactorings): array
    {
        $this->logger->info("Starting refactoring of {$file}");
        $results = [];

        try {
            $code = file_get_contents($file);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $ast = $parser->parse($code);

            if ($ast === null) {
                throw new \RuntimeException("Failed to parse file: {$file}");
            }

            foreach ($refactorings as $refactoring) {
                $result = $this->applyRefactoring($ast, $refactoring);
                if ($result['success']) {
                    $this->metrics['automated_refactorings']++;
                }
                $results[] = $result;
            }

            return $results;
        } catch (\Exception $e) {
            $this->logger->error("Error refactoring file {$file}: " . $e->getMessage());
            throw $e;
        }
    }

    private function applyRefactoring(array &$ast, array $refactoring): array
    {
        $traverser = new NodeTraverser();
        $visitor = new class($refactoring) extends \PhpParser\NodeVisitorAbstract {
            private array $refactoring;
            private bool $modified = false;

            public function __construct(array $refactoring)
            {
                $this->refactoring = $refactoring;
            }

            public function leaveNode(Node $node)
            {
                if ($this->matchesRefactoring($node)) {
                    $this->modified = true;
                    return $this->transformNode($node);
                }
                return null;
            }

            private function matchesRefactoring(Node $node): bool
            {
                // Implement refactoring matching logic
                return false;
            }

            private function transformNode(Node $node): ?Node
            {
                // Implement node transformation logic
                return $node;
            }

            public function wasModified(): bool
            {
                return $this->modified;
            }
        };
        $traverser->addVisitor($visitor);
        $ast = $traverser->traverse($ast);

        return [
            'success' => $visitor->wasModified(),
            'type' => $refactoring['type'],
            'description' => $refactoring['description']
        ];
    }

    public function optimize(string $file): array
    {
        $this->logger->info("Starting optimization of {$file}");
        $optimizations = [];

        try {
            $code = file_get_contents($file);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $ast = $parser->parse($code);

            if ($ast === null) {
                throw new \RuntimeException("Failed to parse file: {$file}");
            }

            $optimizations = array_merge(
                $optimizations,
                $this->optimizeLoops($ast),
                $this->optimizeMethodCalls($ast),
                $this->optimizeMemoryUsage($ast)
            );

            if (!empty($optimizations)) {
                $this->metrics['optimizations_applied'] += count($optimizations);
            }

            return $optimizations;
        } catch (\Exception $e) {
            $this->logger->error("Error optimizing file {$file}: " . $e->getMessage());
            throw $e;
        }
    }

    private function optimizeLoops(array $ast): array
    {
        $optimizations = [];
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use (&$optimizations) {
            if ($node instanceof Node\Stmt\Foreach_) {
                $optimizations[] = $this->optimizeForEachLoop($node);
            } elseif ($node instanceof Node\Stmt\For_) {
                $optimizations[] = $this->optimizeForLoop($node);
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
        return array_filter($optimizations);
    }

    private function optimizeForEachLoop(Node\Stmt\Foreach_ $node): ?array
    {
        // Implement foreach loop optimization
        return null;
    }

    private function optimizeForLoop(Node\Stmt\For_ $node): ?array
    {
        // Implement for loop optimization
        return null;
    }

    private function optimizeMethodCalls(array $ast): array
    {
        $optimizations = [];
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use (&$optimizations) {
            if ($node instanceof Node\Expr\MethodCall) {
                $optimizations[] = $this->optimizeMethodCall($node);
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
        return array_filter($optimizations);
    }

    private function optimizeMethodCall(Node\Expr\MethodCall $node): ?array
    {
        // Implement method call optimization
        return null;
    }

    private function optimizeMemoryUsage(array $ast): array
    {
        $optimizations = [];
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use (&$optimizations) {
            if ($node instanceof Node\Expr\Array_) {
                $optimizations[] = $this->optimizeArrayUsage($node);
            } elseif ($node instanceof Node\Expr\Variable) {
                $optimizations[] = $this->optimizeVariableUsage($node);
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
        return array_filter($optimizations);
    }

    private function optimizeArrayUsage(Node\Expr\Array_ $node): ?array
    {
        // Implement array usage optimization
        return null;
    }

    private function optimizeVariableUsage(Node\Expr\Variable $node): ?array
    {
        // Implement variable usage optimization
        return null;
    }

    private function generateRecommendations(): void
    {
        $this->recommendations = [
            'code_smells' => array_filter($this->refactoringOpportunities, function($opportunity) {
                return $opportunity['type'] !== 'performance';
            }),
            'performance_improvements' => array_filter($this->refactoringOpportunities, function($opportunity) {
                return $opportunity['type'] === 'performance';
            })
        ];
    }

    private function generateReport(): void
    {
        $this->report = [
            'metrics' => $this->metrics,
            'recommendations' => $this->recommendations,
            'summary' => [
                'total_issues' => array_sum($this->metrics),
                'critical_issues' => $this->countCriticalIssues(),
                'improvement_areas' => $this->identifyImprovementAreas()
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function countCriticalIssues(): int
    {
        return count(array_filter($this->refactoringOpportunities, function($opportunity) {
            return $opportunity['severity'] === 'high';
        }));
    }

    private function identifyImprovementAreas(): array
    {
        $areas = [];
        foreach ($this->refactoringOpportunities as $opportunity) {
            if (!isset($areas[$opportunity['type']])) {
                $areas[$opportunity['type']] = 0;
            }
            $areas[$opportunity['type']]++;
        }
        return $areas;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getReport(): array
    {
        return $this->report;
    }
} 