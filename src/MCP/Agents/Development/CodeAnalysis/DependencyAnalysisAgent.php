<?php

namespace MCP\Agents\Development\CodeAnalysis;

use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use Composer\Autoload\ClassLoader;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\FindingVisitor;

class DependencyAnalysisAgent extends BaseCodeAnalysisAgent
{
    private array $metrics = [];
    private array $recommendations = [];
    private array $report = [];
    private array $dependencies = [];
    private array $circularDependencies = [];
    private array $versionConflicts = [];
    private array $securityVulnerabilities = [];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
        
        $this->metrics = [
            'total_dependencies' => 0,
            'direct_dependencies' => 0,
            'indirect_dependencies' => 0,
            'circular_dependencies' => 0,
            'version_conflicts' => 0,
            'security_vulnerabilities' => 0
        ];
    }

    protected function getMetrics(): array
    {
        return $this->metrics;
    }

    public function analyze(array $files): array
    {
        $this->logger->info('Starting dependency analysis');
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning("File not found: {$file}");
                continue;
            }

            $this->analyzeFile($file);
        }

        $this->analyzeDependencies();
        $this->detectCircularDependencies();
        $this->checkVersionConflicts();
        $this->scanSecurityVulnerabilities();
        $this->calculateMetrics();
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

            $this->extractDependencies($ast, $file);
        } catch (\Exception $e) {
            $this->logger->error("Error analyzing file {$file}: " . $e->getMessage());
        }
    }

    private function extractDependencies(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) use ($file) {
            if ($node instanceof \PhpParser\Node\Stmt\Use_) {
                foreach ($node->uses as $use) {
                    $this->dependencies[$file][] = $use->name->toString();
                }
            } elseif ($node instanceof \PhpParser\Node\Expr\New_) {
                if ($node->class instanceof \PhpParser\Node\Name) {
                    $this->dependencies[$file][] = $node->class->toString();
                }
            } elseif ($node instanceof \PhpParser\Node\Expr\StaticCall) {
                if ($node->class instanceof \PhpParser\Node\Name) {
                    $this->dependencies[$file][] = $node->class->toString();
                }
            }
            return false;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function analyzeDependencies(): void
    {
        foreach ($this->dependencies as $file => $deps) {
            $this->metrics['direct_dependencies'] += count($deps);
            $this->analyzeIndirectDependencies($deps);
        }
    }

    private function analyzeIndirectDependencies(array $deps): void
    {
        foreach ($deps as $dep) {
            if (class_exists($dep)) {
                $reflection = new \ReflectionClass($dep);
                $this->analyzeClassDependencies($reflection);
            }
        }
    }

    private function analyzeClassDependencies(\ReflectionClass $class): void
    {
        $this->metrics['indirect_dependencies']++;
        
        // Analyze parent class
        if ($parent = $class->getParentClass()) {
            $this->analyzeClassDependencies($parent);
        }

        // Analyze interfaces
        foreach ($class->getInterfaces() as $interface) {
            $this->analyzeClassDependencies($interface);
        }

        // Analyze traits
        foreach ($class->getTraits() as $trait) {
            $this->analyzeClassDependencies($trait);
        }
    }

    private function detectCircularDependencies(): void
    {
        $visited = [];
        $recursionStack = [];

        foreach ($this->dependencies as $file => $deps) {
            $this->detectCircularDependency($file, $deps, $visited, $recursionStack);
        }
    }

    private function detectCircularDependency(
        string $file,
        array $deps,
        array &$visited,
        array &$recursionStack
    ): void {
        $visited[$file] = true;
        $recursionStack[$file] = true;

        foreach ($deps as $dep) {
            if (!isset($visited[$dep])) {
                if (isset($this->dependencies[$dep])) {
                    $this->detectCircularDependency(
                        $dep,
                        $this->dependencies[$dep],
                        $visited,
                        $recursionStack
                    );
                }
            } elseif (isset($recursionStack[$dep])) {
                $this->circularDependencies[] = [
                    'file' => $file,
                    'dependency' => $dep,
                    'path' => array_keys($recursionStack)
                ];
                $this->metrics['circular_dependencies']++;
            }
        }

        $recursionStack[$file] = false;
    }

    private function checkVersionConflicts(): void
    {
        if (file_exists('composer.json')) {
            $composerJson = json_decode(file_get_contents('composer.json'), true);
            if (isset($composerJson['require'])) {
                foreach ($composerJson['require'] as $package => $version) {
                    $this->checkPackageVersion($package, $version);
                }
            }
        }
    }

    private function checkPackageVersion(string $package, string $version): void
    {
        // Implement version conflict checking logic
        // This would typically involve checking against a package registry
        // and comparing version constraints
    }

    private function scanSecurityVulnerabilities(): void
    {
        if (file_exists('composer.lock')) {
            $composerLock = json_decode(file_get_contents('composer.lock'), true);
            if (isset($composerLock['packages'])) {
                foreach ($composerLock['packages'] as $package) {
                    $this->checkPackageVulnerabilities($package);
                }
            }
        }
    }

    private function checkPackageVulnerabilities(array $package): void
    {
        // Implement security vulnerability scanning logic
        // This would typically involve checking against a security database
        // and comparing package versions
    }

    private function calculateMetrics(): void
    {
        $this->metrics['total_dependencies'] = 
            $this->metrics['direct_dependencies'] + 
            $this->metrics['indirect_dependencies'];
    }

    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    private function generateRecommendations(): void
    {
        $this->recommendations = [
            'dependencies' => $this->generateDependencyRecommendations(),
            'circular_dependencies' => $this->generateCircularDependencyRecommendations(),
            'version_conflicts' => $this->generateVersionConflictRecommendations(),
            'security_vulnerabilities' => $this->generateSecurityVulnerabilityRecommendations()
        ];
    }

    private function generateDependencyRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->metrics['total_dependencies'] > 100) {
            $recommendations[] = "Consider reducing the number of dependencies";
            $recommendations[] = "Review and remove unused dependencies";
        }

        return $recommendations;
    }

    private function generateCircularDependencyRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->metrics['circular_dependencies'] > 0) {
            $recommendations[] = "Resolve circular dependencies in the following files:";
            foreach ($this->circularDependencies as $circular) {
                $recommendations[] = "- {$circular['file']} depends on {$circular['dependency']}";
            }
        }

        return $recommendations;
    }

    private function generateVersionConflictRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->metrics['version_conflicts'] > 0) {
            $recommendations[] = "Resolve version conflicts in the following packages:";
            foreach ($this->versionConflicts as $conflict) {
                $recommendations[] = "- {$conflict['package']}: {$conflict['message']}";
            }
        }

        return $recommendations;
    }

    private function generateSecurityVulnerabilityRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->metrics['security_vulnerabilities'] > 0) {
            $recommendations[] = "Address security vulnerabilities in the following packages:";
            foreach ($this->securityVulnerabilities as $vulnerability) {
                $recommendations[] = "- {$vulnerability['package']}: {$vulnerability['message']}";
            }
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
            'dependency_health' => $this->calculateDependencyHealth(),
            'critical_issues' => $this->identifyCriticalIssues(),
            'improvement_areas' => $this->identifyImprovementAreas()
        ];
    }

    private function calculateDependencyHealth(): float
    {
        $health = 100.0;
        
        // Reduce health based on various factors
        $health -= $this->metrics['circular_dependencies'] * 10;
        $health -= $this->metrics['version_conflicts'] * 5;
        $health -= $this->metrics['security_vulnerabilities'] * 20;
        
        return max(0.0, $health);
    }

    private function identifyCriticalIssues(): array
    {
        $issues = [];
        
        if ($this->metrics['security_vulnerabilities'] > 0) {
            $issues[] = [
                'type' => 'security',
                'count' => $this->metrics['security_vulnerabilities'],
                'priority' => 'high'
            ];
        }
        
        if ($this->metrics['circular_dependencies'] > 0) {
            $issues[] = [
                'type' => 'circular_dependency',
                'count' => $this->metrics['circular_dependencies'],
                'priority' => 'medium'
            ];
        }
        
        return $issues;
    }

    private function identifyImprovementAreas(): array
    {
        $areas = [];
        
        if ($this->metrics['total_dependencies'] > 100) {
            $areas[] = [
                'type' => 'dependency_count',
                'current' => $this->metrics['total_dependencies'],
                'target' => 100,
                'priority' => 'low'
            ];
        }
        
        if ($this->metrics['version_conflicts'] > 0) {
            $areas[] = [
                'type' => 'version_conflicts',
                'count' => $this->metrics['version_conflicts'],
                'priority' => 'medium'
            ];
        }
        
        return $areas;
    }
} 