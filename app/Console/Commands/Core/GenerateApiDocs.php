<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

/**
 * Generate API Documentation Command
 * 
 * This command generates comprehensive API documentation from the codebase,
 * including routes, controllers, models, and validation rules.
 * 
 * Features:
 * - Automatic route discovery and documentation
 * - Controller method analysis with PHPDoc parsing
 * - Model relationship documentation
 * - Validation rule extraction
 * - OpenAPI/Swagger format output
 * - Markdown documentation generation
 */
class GenerateApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate-api 
                            {--format=markdown : Output format (markdown, openapi, json)}
                            {--output=docs/api.md : Output file path}
                            {--include-tests : Include test examples in documentation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive API documentation from the codebase';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Generating API documentation...');

        $format = $this->option('format');
        $outputPath = $this->option('output');
        $includeTests = $this->option('include-tests');

        // Collect API information
        $apiData = $this->collectApiData($includeTests);

        // Generate documentation
        $documentation = $this->generateDocumentation($apiData, $format);

        // Write to file
        $this->writeDocumentation($documentation, $outputPath, $format);

        $this->info("API documentation generated successfully: {$outputPath}");
        $this->info("Format: {$format}");
        $this->info("Routes documented: " . count($apiData['routes']));
        $this->info("Controllers documented: " . count($apiData['controllers']));

        return 0;
    }

    /**
     * Collect all API-related data from the codebase.
     *
     * @param bool $includeTests Whether to include test examples
     * @return array Collected API data
     */
    protected function collectApiData(bool $includeTests = false): array
    {
        $data = [
            'routes' => [],
            'controllers' => [],
            'models' => [],
            'validation_rules' => [],
            'examples' => [],
        ];

        // Collect routes
        $data['routes'] = $this->collectRoutes();

        // Collect controllers
        $data['controllers'] = $this->collectControllers();

        // Collect models
        $data['models'] = $this->collectModels();

        // Collect validation rules
        $data['validation_rules'] = $this->collectValidationRules();

        // Collect test examples if requested
        if ($includeTests) {
            $data['examples'] = $this->collectTestExamples();
        }

        return $data;
    }

    /**
     * Collect all API routes with their metadata.
     *
     * @return array Route information
     */
    protected function collectRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                $routes[] = [
                    'method' => $route->methods()[0],
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'controller' => $route->getController(),
                    'action' => $route->getActionMethod(),
                    'middleware' => $route->middleware(),
                ];
            }
        }

        return $routes;
    }

    /**
     * Collect controller information with method documentation.
     *
     * @return array Controller information
     */
    protected function collectControllers(): array
    {
        $controllers = [];
        $controllerFiles = File::glob(app_path('Http/Controllers/*.php'));

        foreach ($controllerFiles as $file) {
            $className = 'App\\Http\\Controllers\\' . basename($file, '.php');
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                $methods = [];

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->class === $className && !$method->isConstructor()) {
                        $methods[] = [
                            'name' => $method->getName(),
                            'doc_comment' => $method->getDocComment(),
                            'parameters' => $this->getMethodParameters($method),
                            'return_type' => $method->getReturnType(),
                        ];
                    }
                }

                $controllers[] = [
                    'name' => $className,
                    'methods' => $methods,
                    'doc_comment' => $reflection->getDocComment(),
                ];
            }
        }

        return $controllers;
    }

    /**
     * Collect model information with relationships and attributes.
     *
     * @return array Model information
     */
    protected function collectModels(): array
    {
        $models = [];
        $modelFiles = File::glob(app_path('Models/*.php'));

        foreach ($modelFiles as $file) {
            $className = 'App\\Models\\' . basename($file, '.php');
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                $model = new $className();

                $models[] = [
                    'name' => $className,
                    'table' => $model->getTable(),
                    'fillable' => $model->getFillable(),
                    'hidden' => $model->getHidden(),
                    'casts' => $model->getCasts(),
                    'relationships' => $this->getModelRelationships($reflection),
                    'doc_comment' => $reflection->getDocComment(),
                ];
            }
        }

        return $models;
    }

    /**
     * Collect validation rules from controllers and form requests.
     *
     * @return array Validation rules
     */
    protected function collectValidationRules(): array
    {
        $rules = [];

        // Collect from controllers
        $controllerFiles = File::glob(app_path('Http/Controllers/*.php'));
        foreach ($controllerFiles as $file) {
            $content = File::get($file);
            if (preg_match_all('/validation_rules\s*=\s*\[(.*?)\]/s', $content, $matches)) {
                $rules[basename($file, '.php')] = $matches[1];
            }
        }

        // Collect from form requests
        $requestFiles = File::glob(app_path('Http/Requests/*.php'));
        foreach ($requestFiles as $file) {
            $className = 'App\\Http\\Requests\\' . basename($file, '.php');
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if ($reflection->hasMethod('rules')) {
                    $request = new $className();
                    $rules[basename($file, '.php')] = $request->rules();
                }
            }
        }

        return $rules;
    }

    /**
     * Collect test examples for API endpoints.
     *
     * @return array Test examples
     */
    protected function collectTestExamples(): array
    {
        $examples = [];
        $testFiles = File::glob(base_path('tests/**/*Test.php'));

        foreach ($testFiles as $file) {
            $content = File::get($file);
            
            // Extract API test examples
            if (preg_match_all('/public function test_(.*?)\(\)(.*?){/s', $content, $matches)) {
                foreach ($matches[1] as $index => $testName) {
                    $examples[] = [
                        'test_name' => $testName,
                        'file' => $file,
                        'content' => $matches[2][$index],
                    ];
                }
            }
        }

        return $examples;
    }

    /**
     * Generate documentation in the specified format.
     *
     * @param array $apiData Collected API data
     * @param string $format Output format
     * @return string Generated documentation
     */
    protected function generateDocumentation(array $apiData, string $format): string
    {
        switch ($format) {
            case 'markdown':
                return $this->generateMarkdown($apiData);
            case 'openapi':
                return $this->generateOpenApi($apiData);
            case 'json':
                return json_encode($apiData, JSON_PRETTY_PRINT);
            default:
                return $this->generateMarkdown($apiData);
        }
    }

    /**
     * Generate Markdown documentation.
     *
     * @param array $apiData Collected API data
     * @return string Markdown documentation
     */
    protected function generateMarkdown(array $apiData): string
    {
        $markdown = "# API Documentation\n\n";
        $markdown .= "Generated on: " . now()->toISOString() . "\n\n";

        // Routes section
        $markdown .= "## Routes\n\n";
        foreach ($apiData['routes'] as $route) {
            $markdown .= "### {$route['method']} {$route['uri']}\n\n";
            $markdown .= "- **Name:** {$route['name']}\n";
            $markdown .= "- **Controller:** {$route['controller']}@{$route['action']}\n";
            $markdown .= "- **Middleware:** " . implode(', ', $route['middleware']) . "\n\n";
        }

        // Controllers section
        $markdown .= "## Controllers\n\n";
        foreach ($apiData['controllers'] as $controller) {
            $markdown .= "### {$controller['name']}\n\n";
            if ($controller['doc_comment']) {
                $markdown .= $this->parseDocComment($controller['doc_comment']) . "\n\n";
            }
            
            foreach ($controller['methods'] as $method) {
                $markdown .= "#### {$method['name']}\n\n";
                if ($method['doc_comment']) {
                    $markdown .= $this->parseDocComment($method['doc_comment']) . "\n\n";
                }
            }
        }

        // Models section
        $markdown .= "## Models\n\n";
        foreach ($apiData['models'] as $model) {
            $markdown .= "### {$model['name']}\n\n";
            $markdown .= "- **Table:** {$model['table']}\n";
            $markdown .= "- **Fillable:** " . implode(', ', $model['fillable']) . "\n";
            $markdown .= "- **Hidden:** " . implode(', ', $model['hidden']) . "\n\n";
        }

        return $markdown;
    }

    /**
     * Generate OpenAPI documentation.
     *
     * @param array $apiData Collected API data
     * @return string OpenAPI documentation
     */
    protected function generateOpenApi(array $apiData): string
    {
        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Service Learning Management API',
                'version' => '1.0.0',
                'description' => 'API documentation for the Service Learning Management System',
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
        ];

        // Add paths from routes
        foreach ($apiData['routes'] as $route) {
            $path = '/' . $route['uri'];
            $method = strtolower($route['method']);
            
            $openapi['paths'][$path][$method] = [
                'summary' => $route['name'] ?? $route['action'],
                'tags' => [$this->extractTagFromController($route['controller'])],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                    ],
                    '401' => [
                        'description' => 'Unauthorized',
                    ],
                    '403' => [
                        'description' => 'Forbidden',
                    ],
                    '404' => [
                        'description' => 'Not found',
                    ],
                    '422' => [
                        'description' => 'Validation error',
                    ],
                ],
            ];
        }

        return json_encode($openapi, JSON_PRETTY_PRINT);
    }

    /**
     * Write documentation to file.
     *
     * @param string $documentation Generated documentation
     * @param string $outputPath Output file path
     * @param string $format Output format
     * @return void
     */
    protected function writeDocumentation(string $documentation, string $outputPath, string $format): void
    {
        $directory = dirname($outputPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($outputPath, $documentation);
    }

    /**
     * Get method parameters information.
     *
     * @param ReflectionMethod $method
     * @return array Parameters information
     */
    protected function getMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = [
                'name' => $parameter->getName(),
                'type' => $parameter->getType(),
                'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                'required' => !$parameter->isOptional(),
            ];
        }

        return $parameters;
    }

    /**
     * Get model relationships information.
     *
     * @param ReflectionClass $reflection
     * @return array Relationships information
     */
    protected function getModelRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            if ($docComment && (
                str_contains($docComment, '@return') && 
                (str_contains($docComment, 'BelongsTo') || 
                 str_contains($docComment, 'HasMany') || 
                 str_contains($docComment, 'HasOne') ||
                 str_contains($docComment, 'BelongsToMany'))
            )) {
                $relationships[] = [
                    'name' => $method->getName(),
                    'type' => $this->extractRelationshipType($docComment),
                    'doc_comment' => $docComment,
                ];
            }
        }

        return $relationships;
    }

    /**
     * Parse PHPDoc comment to readable format.
     *
     * @param string $docComment
     * @return string Parsed documentation
     */
    protected function parseDocComment(string $docComment): string
    {
        $lines = explode("\n", $docComment);
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*/");
            if (!empty($line) && !str_starts_with($line, '@')) {
                $parsed[] = $line;
            }
        }

        return implode(' ', $parsed);
    }

    /**
     * Extract tag from controller class name.
     *
     * @param string $controller
     * @return string Tag
     */
    protected function extractTagFromController(string $controller): string
    {
        $parts = explode('\\', $controller);
        $className = end($parts);
        return str_replace('Controller', '', $className);
    }

    /**
     * Extract relationship type from PHPDoc comment.
     *
     * @param string $docComment
     * @return string Relationship type
     */
    protected function extractRelationshipType(string $docComment): string
    {
        if (str_contains($docComment, 'BelongsTo')) return 'BelongsTo';
        if (str_contains($docComment, 'HasMany')) return 'HasMany';
        if (str_contains($docComment, 'HasOne')) return 'HasOne';
        if (str_contains($docComment, 'BelongsToMany')) return 'BelongsToMany';
        return 'Unknown';
    }
} 