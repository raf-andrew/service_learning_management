<?php

namespace App\MCP\Agents\Development\Documentation;

use App\MCP\Agents\Development\CodeAnalysis\BaseCodeAnalysisAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\FindingVisitor;
use Symfony\Component\Yaml\Yaml;

/**
 * Documentation Agent for analyzing and generating code documentation
 * 
 * This agent is responsible for:
 * - Analyzing code documentation
 * - Generating documentation
 * - Validating documentation
 * - Generating API documentation
 * - Creating usage examples
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class DocumentationAgent extends BaseCodeAnalysisAgent
{
    private BuilderFactory $factory;
    private PrettyPrinter\Standard $printer;
    private array $metrics = [
        'doc_blocks_analyzed' => 0,
        'doc_blocks_generated' => 0,
        'doc_blocks_validated' => 0,
        'api_endpoints_documented' => 0,
        'examples_generated' => 0
    ];
    private array $report = [];
    private array $docBlocks = [];
    private array $apiEndpoints = [];
    private array $usageExamples = [];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
        
        $this->factory = new BuilderFactory;
        $this->printer = new PrettyPrinter\Standard;
    }

    /**
     * Get the agent's metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Analyze code and return results
     */
    public function analyze(array $files): array
    {
        $this->logger->info('Starting documentation analysis for ' . count($files) . ' files');
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning("File not found: $file");
                continue;
            }

            try {
                $this->analyzeFile($file);
            } catch (\Throwable $e) {
                $this->logger->error("Error analyzing file $file: " . $e->getMessage());
            }
        }

        $this->report = [
            'metrics' => $this->metrics,
            'doc_blocks' => $this->docBlocks,
            'api_endpoints' => $this->apiEndpoints,
            'usage_examples' => $this->usageExamples,
            'summary' => $this->generateSummary(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->report;
    }

    /**
     * Get analysis recommendations
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        foreach ($this->docBlocks as $docBlock) {
            if (!$docBlock['is_valid']) {
                $recommendations[] = [
                    'type' => 'documentation',
                    'element' => $docBlock['element'],
                    'message' => $docBlock['issues'][0] ?? 'Documentation needs improvement',
                    'severity' => 'warning'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get analysis report
     */
    public function getReport(): array
    {
        return $this->report;
    }

    /**
     * Analyze a single file
     */
    private function analyzeFile(string $file): void
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException("Could not read file: $file");
        }

        $tokens = token_get_all($content);
        $this->analyzeDocBlocks($tokens, $file);
        $this->analyzeApiEndpoints($tokens, $file);
        $this->generateUsageExamples($tokens, $file);
    }

    /**
     * Analyze documentation blocks in the code
     */
    private function analyzeDocBlocks(array $tokens, string $file): void
    {
        $currentClass = null;
        $currentMethod = null;
        $currentProperty = null;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            list($id, $text) = $token;

            switch ($id) {
                case T_CLASS:
                    $currentClass = $text;
                    break;
                case T_FUNCTION:
                    $currentMethod = $text;
                    break;
                case T_VARIABLE:
                    $currentProperty = $text;
                    break;
                case T_DOC_COMMENT:
                    $this->metrics['doc_blocks_analyzed']++;
                    
                    $docBlock = [
                        'file' => $file,
                        'element' => $currentMethod ?? $currentProperty ?? $currentClass,
                        'type' => $currentMethod ? 'method' : ($currentProperty ? 'property' : 'class'),
                        'content' => $text,
                        'is_valid' => $this->validateDocBlock($text),
                        'issues' => $this->getDocBlockIssues($text)
                    ];

                    $this->docBlocks[] = $docBlock;
                    break;
            }
        }
    }

    /**
     * Analyze API endpoints in the code
     */
    private function analyzeApiEndpoints(array $tokens, string $file): void
    {
        $currentMethod = null;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            list($id, $text) = $token;

            if ($id === T_FUNCTION) {
                $currentMethod = $text;
            } elseif ($id === T_DOC_COMMENT && $currentMethod) {
                if (strpos($text, '@api') !== false) {
                    $this->metrics['api_endpoints_documented']++;
                    $this->apiEndpoints[] = [
                        'file' => $file,
                        'method' => $currentMethod,
                        'documentation' => $text
                    ];
                }
            }
        }
    }

    /**
     * Generate usage examples for documented methods
     */
    private function generateUsageExamples(array $tokens, string $file): void
    {
        $currentMethod = null;
        $currentParams = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            list($id, $text) = $token;

            if ($id === T_FUNCTION) {
                $currentMethod = $text;
            } elseif ($id === T_VARIABLE && $currentMethod) {
                $currentParams[] = $text;
            } elseif ($id === T_DOC_COMMENT && $currentMethod) {
                $this->metrics['examples_generated']++;
                $this->usageExamples[] = [
                    'file' => $file,
                    'method' => $currentMethod,
                    'example' => $this->generateExample($currentMethod, $currentParams)
                ];
                $currentMethod = null;
                $currentParams = [];
            }
        }
    }

    /**
     * Validate a documentation block
     */
    private function validateDocBlock(string $docBlock): bool
    {
        // Basic validation rules
        $rules = [
            'has_description' => strpos($docBlock, '*/') > strpos($docBlock, '/*'),
            'has_params' => strpos($docBlock, '@param') !== false,
            'has_return' => strpos($docBlock, '@return') !== false,
            'has_throws' => strpos($docBlock, '@throws') !== false
        ];

        return count(array_filter($rules)) >= 3;
    }

    /**
     * Get documentation block issues
     */
    private function getDocBlockIssues(string $docBlock): array
    {
        $issues = [];

        if (strpos($docBlock, '*/') <= strpos($docBlock, '/*')) {
            $issues[] = 'Missing description';
        }
        if (strpos($docBlock, '@param') === false) {
            $issues[] = 'Missing @param tags';
        }
        if (strpos($docBlock, '@return') === false) {
            $issues[] = 'Missing @return tag';
        }
        if (strpos($docBlock, '@throws') === false) {
            $issues[] = 'Missing @throws tag';
        }

        return $issues;
    }

    /**
     * Generate a usage example for a method
     */
    private function generateExample(string $method, array $params): string
    {
        $example = "\$instance->$method(";
        $example .= implode(', ', array_map(fn($param) => "\$$param", $params));
        $example .= ");";
        return $example;
    }

    /**
     * Generate a summary of the analysis
     */
    private function generateSummary(): array
    {
        return [
            'total_files_analyzed' => count(array_unique(array_column($this->docBlocks, 'file'))),
            'total_doc_blocks' => count($this->docBlocks),
            'valid_doc_blocks' => count(array_filter($this->docBlocks, fn($doc) => $doc['is_valid'])),
            'total_api_endpoints' => count($this->apiEndpoints),
            'total_examples' => count($this->usageExamples)
        ];
    }

    public function generateDocumentation(string $file): string
    {
        $this->logger->info("Generating documentation for {$file}");

        try {
            $code = file_get_contents($file);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $ast = $parser->parse($code);

            if ($ast === null) {
                throw new \RuntimeException("Failed to parse file: {$file}");
            }

            $documentation = $this->generateFileDocumentation($ast, $file);
            $this->metrics['doc_blocks_generated']++;

            return $documentation;
        } catch (\Exception $e) {
            $this->logger->error("Error generating documentation for {$file}: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateFileDocumentation(array $ast, string $file): string
    {
        $documentation = [];
        
        // File header
        $documentation[] = "# " . basename($file);
        $documentation[] = "";
        $documentation[] = "## Overview";
        $documentation[] = "";
        
        // Namespace
        $namespace = $this->findNamespace($ast);
        if ($namespace) {
            $documentation[] = "**Namespace:** `{$namespace}`";
            $documentation[] = "";
        }
        
        // Classes
        foreach ($this->findClasses($ast) as $class) {
            $documentation = array_merge($documentation, $this->generateClassDocumentation($class));
        }
        
        // Interfaces
        foreach ($this->findInterfaces($ast) as $interface) {
            $documentation = array_merge($documentation, $this->generateInterfaceDocumentation($interface));
        }
        
        // API Documentation
        if (!empty($this->apiEndpoints)) {
            $documentation = array_merge($documentation, $this->generateApiDocumentation());
        }
        
        // Usage Examples
        if (!empty($this->usageExamples)) {
            $documentation = array_merge($documentation, $this->generateExamplesDocumentation());
        }
        
        return implode("\n", $documentation);
    }

    private function findNamespace(array $ast): ?string
    {
        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                return $node->name->toString();
            }
        }
        return null;
    }

    private function findClasses(array $ast): array
    {
        $classes = [];
        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Class_) {
                $classes[] = $node;
            }
        }
        return $classes;
    }

    private function findInterfaces(array $ast): array
    {
        $interfaces = [];
        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Interface_) {
                $interfaces[] = $node;
            }
        }
        return $interfaces;
    }

    private function generateClassDocumentation(Node\Stmt\Class_ $class): array
    {
        $documentation = [];
        
        // Class header
        $documentation[] = "## Class: " . $class->name;
        $documentation[] = "";
        
        // Class description from DocBlock
        if ($class->getDocComment()) {
            $documentation[] = $this->formatDocBlock($class->getDocComment()->getText());
            $documentation[] = "";
        }
        
        // Properties
        if (!empty($class->stmts)) {
            $documentation[] = "### Properties";
            $documentation[] = "";
            foreach ($class->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Property) {
                    $documentation = array_merge($documentation, $this->generatePropertyDocumentation($stmt));
                }
            }
        }
        
        // Methods
        $documentation[] = "### Methods";
        $documentation[] = "";
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $documentation = array_merge($documentation, $this->generateMethodDocumentation($stmt));
            }
        }
        
        return $documentation;
    }

    private function generateInterfaceDocumentation(Node\Stmt\Interface_ $interface): array
    {
        $documentation = [];
        
        // Interface header
        $documentation[] = "## Interface: " . $interface->name;
        $documentation[] = "";
        
        // Interface description from DocBlock
        if ($interface->getDocComment()) {
            $documentation[] = $this->formatDocBlock($interface->getDocComment()->getText());
            $documentation[] = "";
        }
        
        // Methods
        $documentation[] = "### Methods";
        $documentation[] = "";
        foreach ($interface->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $documentation = array_merge($documentation, $this->generateMethodDocumentation($stmt));
            }
        }
        
        return $documentation;
    }

    private function generatePropertyDocumentation(Node\Stmt\Property $property): array
    {
        $documentation = [];
        
        foreach ($property->props as $prop) {
            $documentation[] = "#### \${$prop->name}";
            $documentation[] = "";
            
            if ($property->getDocComment()) {
                $documentation[] = $this->formatDocBlock($property->getDocComment()->getText());
                $documentation[] = "";
            }
            
            $type = $property->type ? $this->getTypeAsString($property->type) : 'mixed';
            $documentation[] = "**Type:** `{$type}`";
            $documentation[] = "";
        }
        
        return $documentation;
    }

    private function generateMethodDocumentation(Node\Stmt\ClassMethod $method): array
    {
        $documentation = [];
        
        // Method header
        $documentation[] = "#### " . $method->name;
        $documentation[] = "";
        
        // Method description from DocBlock
        if ($method->getDocComment()) {
            $documentation[] = $this->formatDocBlock($method->getDocComment()->getText());
            $documentation[] = "";
        }
        
        // Method signature
        $documentation[] = "```php";
        $documentation[] = $this->generateMethodSignature($method);
        $documentation[] = "```";
        $documentation[] = "";
        
        // Parameters
        if (!empty($method->params)) {
            $documentation[] = "**Parameters:**";
            $documentation[] = "";
            foreach ($method->params as $param) {
                $type = $param->type ? $this->getTypeAsString($param->type) : 'mixed';
                $default = $param->default ? ' = ' . $this->printer->prettyPrintExpr($param->default) : '';
                $documentation[] = "- `{$type} \${$param->var->name}{$default}`";
            }
            $documentation[] = "";
        }
        
        // Return type
        if ($method->returnType) {
            $returnType = $this->getTypeAsString($method->returnType);
            $documentation[] = "**Returns:** `{$returnType}`";
            $documentation[] = "";
        }
        
        return $documentation;
    }

    private function generateMethodSignature(Node\Stmt\ClassMethod $method): string
    {
        $visibility = $method->isPublic() ? 'public' : ($method->isProtected() ? 'protected' : 'private');
        $static = $method->isStatic() ? ' static' : '';
        $name = $method->name->toString();
        
        $params = [];
        foreach ($method->params as $param) {
            $paramType = $param->type ? $this->getTypeAsString($param->type) . ' ' : '';
            $paramName = '$' . $param->var->name;
            $paramDefault = $param->default ? ' = ' . $this->printer->prettyPrintExpr($param->default) : '';
            $params[] = $paramType . $paramName . $paramDefault;
        }
        
        $returnType = $method->returnType ? ': ' . $this->getTypeAsString($method->returnType) : '';
        
        return "{$visibility}{$static} function {$name}(" . implode(', ', $params) . "){$returnType}";
    }

    private function generateApiDocumentation(): array
    {
        $documentation = [];
        
        $documentation[] = "## API Documentation";
        $documentation[] = "";
        
        foreach ($this->apiEndpoints as $endpoint) {
            $documentation[] = "### " . $endpoint['method'];
            $documentation[] = "";
            
            if ($endpoint['documentation']) {
                $documentation[] = $this->formatDocBlock($endpoint['documentation']);
                $documentation[] = "";
            }
        }
        
        return $documentation;
    }

    private function generateExamplesDocumentation(): array
    {
        $documentation = [];
        
        $documentation[] = "## Usage Examples";
        $documentation[] = "";
        
        foreach ($this->usageExamples as $example) {
            $documentation[] = "### " . $example['method'];
            $documentation[] = "";
            $documentation[] = "```php";
            $documentation[] = $example['example'];
            $documentation[] = "```";
            $documentation[] = "";
        }
        
        return $documentation;
    }

    private function formatDocBlock(string $docBlock): string
    {
        $lines = explode("\n", $docBlock);
        $formatted = [];
        
        foreach ($lines as $line) {
            $line = trim($line, "/* \t");
            if ($line && !preg_match('/^@\w+/', $line)) {
                $formatted[] = $line;
            }
        }
        
        return implode("\n", $formatted);
    }

    private function getTypeAsString($type): string
    {
        if ($type instanceof Node\Name) {
            return $type->toString();
        } elseif ($type instanceof Node\NullableType) {
            return '?' . $this->getTypeAsString($type->type);
        } elseif ($type instanceof Node\UnionType) {
            return implode('|', array_map([$this, 'getTypeAsString'], $type->types));
        }
        return (string) $type;
    }

    public function validateDocumentation(string $file): array
    {
        $this->logger->info("Validating documentation for {$file}");
        $issues = [];

        try {
            $code = file_get_contents($file);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $ast = $parser->parse($code);

            if ($ast === null) {
                throw new \RuntimeException("Failed to parse file: {$file}");
            }

            $traverser = new NodeTraverser();
            $visitor = new FindingVisitor(function($node) use (&$issues, $file) {
                if ($node instanceof Node\Stmt\Class_ || 
                    $node instanceof Node\Stmt\Interface_ || 
                    $node instanceof Node\Stmt\ClassMethod || 
                    $node instanceof Node\Stmt\Property) {
                    
                    $docComment = $node->getDocComment();
                    if (!$docComment) {
                        $issues[] = [
                            'file' => $file,
                            'line' => $node->getLine(),
                            'type' => 'missing_doc',
                            'element' => $this->getNodeType($node) . ' ' . $this->getNodeName($node),
                            'message' => 'Missing documentation'
                        ];
                    } else {
                        $docIssues = $this->validateDocBlock($docComment->getText());
                        if (!empty($docIssues)) {
                            $issues[] = [
                                'file' => $file,
                                'line' => $node->getLine(),
                                'type' => 'invalid_doc',
                                'element' => $this->getNodeType($node) . ' ' . $this->getNodeName($node),
                                'message' => implode(', ', $docIssues)
                            ];
                        }
                    }
                }
                return false;
            });
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            return $issues;
        } catch (\Exception $e) {
            $this->logger->error("Error validating documentation for {$file}: " . $e->getMessage());
            throw $e;
        }
    }
} 