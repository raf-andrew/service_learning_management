<?php

namespace MCP\Agents\Development\CodeGeneration;

use MCP\Core\Services\HealthMonitor;
use MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\BuilderFactory;
use PhpParser\Node;

/**
 * Code Generation Agent
 * 
 * This agent is responsible for generating code artifacts such as:
 * - Interfaces
 * - Test cases
 * - Documentation
 * - Boilerplate code
 * - Code templates
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class CodeGenerationAgent extends BaseCodeAnalysisAgent
{
    private BuilderFactory $factory;
    private PrettyPrinter\Standard $printer;
    private array $templates = [];
    private array $metrics = [];
    private array $report = [];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
        
        $this->factory = new BuilderFactory;
        $this->printer = new PrettyPrinter\Standard;
        
        $this->metrics = [
            'interfaces_generated' => 0,
            'tests_generated' => 0,
            'docs_generated' => 0,
            'boilerplate_generated' => 0,
            'templates_used' => 0
        ];

        $this->initializeTemplates();
    }

    private function initializeTemplates(): void
    {
        $this->templates = [
            'interface' => [
                'pattern' => 'Interface.php',
                'template' => <<<'EOT'
<?php

namespace {namespace};

interface {name}
{
    {methods}
}
EOT
            ],
            'test' => [
                'pattern' => 'Test.php',
                'template' => <<<'EOT'
<?php

namespace {namespace}\Tests;

use PHPUnit\Framework\TestCase;
use {namespace}\{class};

class {class}Test extends TestCase
{
    protected {class} ${instance};

    protected function setUp(): void
    {
        parent::setUp();
        $this->{instance} = new {class}();
    }

    {methods}
}
EOT
            ],
            'class' => [
                'pattern' => '.php',
                'template' => <<<'EOT'
<?php

namespace {namespace};

class {name}
{
    {properties}

    {methods}
}
EOT
            ]
        ];
    }

    public function generateInterface(string $className, array $methods): string
    {
        $this->logger->info("Generating interface for {$className}");

        try {
            $interfaceName = "I{$className}";
            $namespace = $this->extractNamespace($className);
            
            $interfaceBuilder = $this->factory->interface($interfaceName);
            
            foreach ($methods as $method) {
                $interfaceBuilder->addStmt(
                    $this->factory->method($method['name'])
                        ->makePublic()
                        ->addParams($method['params'] ?? [])
                        ->setReturnType($method['return'] ?? 'void')
                );
            }

            $node = $interfaceBuilder->getNode();
            $code = $this->printer->prettyPrintFile([$node]);
            
            $this->metrics['interfaces_generated']++;
            
            return $code;
        } catch (\Exception $e) {
            $this->logger->error("Error generating interface: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateTest(string $className, array $methods): string
    {
        $this->logger->info("Generating test for {$className}");

        try {
            $testName = "{$className}Test";
            $namespace = $this->extractNamespace($className);
            $instance = lcfirst($className);
            
            $testBuilder = $this->factory->class($testName)
                ->extend('TestCase');
            
            // Add test methods
            foreach ($methods as $method) {
                $testBuilder->addStmt(
                    $this->factory->method("test{$method['name']}")
                        ->makePublic()
                        ->addStmt(
                            // Add test implementation
                            new Node\Stmt\Expression(
                                $this->factory->methodCall(
                                    new Node\Expr\Variable('this'),
                                    'markTestIncomplete',
                                    [new Node\Scalar\String_('Test not implemented yet')]
                                )
                            )
                        )
                );
            }

            $node = $testBuilder->getNode();
            $code = $this->printer->prettyPrintFile([$node]);
            
            $this->metrics['tests_generated']++;
            
            return $code;
        } catch (\Exception $e) {
            $this->logger->error("Error generating test: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateDocumentation(string $className): string
    {
        $this->logger->info("Generating documentation for {$className}");

        try {
            $reflection = new \ReflectionClass($className);
            $documentation = [];
            
            // Class documentation
            $documentation[] = "# {$reflection->getShortName()}";
            $documentation[] = "";
            $documentation[] = $this->extractDocComment($reflection->getDocComment());
            $documentation[] = "";
            
            // Properties documentation
            $documentation[] = "## Properties";
            $documentation[] = "";
            foreach ($reflection->getProperties() as $property) {
                $documentation[] = "### {$property->getName()}";
                $documentation[] = "";
                $documentation[] = $this->extractDocComment($property->getDocComment());
                $documentation[] = "";
            }
            
            // Methods documentation
            $documentation[] = "## Methods";
            $documentation[] = "";
            foreach ($reflection->getMethods() as $method) {
                $documentation[] = "### {$method->getName()}";
                $documentation[] = "";
                $documentation[] = $this->extractDocComment($method->getDocComment());
                $documentation[] = "";
                $documentation[] = "```php";
                $documentation[] = $this->getMethodSignature($method);
                $documentation[] = "```";
                $documentation[] = "";
            }
            
            $this->metrics['docs_generated']++;
            
            return implode("\n", $documentation);
        } catch (\Exception $e) {
            $this->logger->error("Error generating documentation: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateBoilerplate(string $type, array $config): string
    {
        $this->logger->info("Generating boilerplate code of type {$type}");

        try {
            if (!isset($this->templates[$type])) {
                throw new \InvalidArgumentException("Unknown template type: {$type}");
            }

            $template = $this->templates[$type];
            $code = $template['template'];
            
            foreach ($config as $key => $value) {
                $code = str_replace("{{$key}}", $value, $code);
            }
            
            $this->metrics['boilerplate_generated']++;
            
            return $code;
        } catch (\Exception $e) {
            $this->logger->error("Error generating boilerplate: " . $e->getMessage());
            throw $e;
        }
    }

    private function extractNamespace(string $className): string
    {
        $parts = explode('\\', $className);
        array_pop($parts);
        return implode('\\', $parts);
    }

    private function extractDocComment(?string $docComment): string
    {
        if (!$docComment) {
            return 'No documentation available.';
        }

        $lines = explode("\n", $docComment);
        $doc = [];
        
        foreach ($lines as $line) {
            $line = trim($line, "/* \t");
            if ($line && !preg_match('/^@\w+/', $line)) {
                $doc[] = $line;
            }
        }
        
        return implode("\n", $doc);
    }

    private function getMethodSignature(\ReflectionMethod $method): string
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $paramStr = '';
            if ($param->hasType()) {
                $paramStr .= $param->getType() . ' ';
            }
            $paramStr .= '$' . $param->getName();
            if ($param->isDefaultValueAvailable()) {
                $paramStr .= ' = ' . var_export($param->getDefaultValue(), true);
            }
            $params[] = $paramStr;
        }
        
        $returnType = $method->hasReturnType() ? ': ' . $method->getReturnType() : '';
        
        return sprintf(
            'public function %s(%s)%s',
            $method->getName(),
            implode(', ', $params),
            $returnType
        );
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getReport(): array
    {
        return $this->report;
    }

    public function analyze(array $files): array
    {
        $this->logger->info('Starting code generation analysis');
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning("File not found: {$file}");
                continue;
            }

            $this->analyzeFile($file);
        }

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

            // Analyze the file for potential code generation opportunities
            $this->analyzeForInterfaceGeneration($ast, $file);
            $this->analyzeForTestGeneration($ast, $file);
            $this->analyzeForDocumentationGeneration($ast, $file);
        } catch (\Exception $e) {
            $this->logger->error("Error analyzing file {$file}: " . $e->getMessage());
        }
    }

    private function analyzeForInterfaceGeneration(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) {
            return $node instanceof Node\Stmt\Class_;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function analyzeForTestGeneration(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) {
            return $node instanceof Node\Stmt\Class_;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function analyzeForDocumentationGeneration(array $ast, string $file): void
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor(function($node) {
            return $node instanceof Node\Stmt\Class_;
        });
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
    }

    private function generateReport(): void
    {
        $this->report = [
            'metrics' => $this->metrics,
            'summary' => [
                'total_generated' => array_sum($this->metrics),
                'generation_types' => array_keys($this->metrics),
                'templates_available' => array_keys($this->templates)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
} 