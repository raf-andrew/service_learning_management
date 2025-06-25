<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Generate API Documentation Command
 * 
 * Automatically generates comprehensive API documentation.
 */
class GenerateApiDocumentationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate-api {--format=markdown : Output format (markdown, json, html)} {--output=docs/api.md : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive API documentation';

    /**
     * API documentation data
     *
     * @var array<string, mixed>
     */
    protected array $documentation = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📚 Generating API Documentation...');
        
        $this->analyzeRoutes();
        $this->analyzeControllers();
        $this->analyzeModels();
        $this->generateDocumentation();
        
        $this->info('✅ API documentation generated successfully');
        
        return Command::SUCCESS;
    }

    /**
     * Analyze routes
     */
    private function analyzeRoutes(): void
    {
        $routes = Route::getRoutes();
        $apiRoutes = [];
        
        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            $middleware = $route->middleware();
            
            // Focus on API routes
            if (str_starts_with($uri, 'api/') || in_array('api', $middleware)) {
                $controller = $route->getController();
                $action = $route->getActionMethod();
                
                $apiRoutes[] = [
                    'uri' => $uri,
                    'methods' => $methods,
                    'middleware' => $middleware,
                    'controller' => $controller ? get_class($controller) : null,
                    'action' => $action,
                    'name' => $route->getName(),
                ];
            }
        }
        
        $this->documentation['routes'] = $apiRoutes;
    }

    /**
     * Analyze controllers
     */
    private function analyzeControllers(): void
    {
        $controllers = [];
        $controllerFiles = File::glob(base_path('app/Http/Controllers/**/*.php'));
        
        foreach ($controllerFiles as $file) {
            $className = $this->extractClassName($file);
            $reflection = new ReflectionClass($className);
            
            $methods = [];
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class === $className && !$method->isConstructor()) {
                    $methods[] = [
                        'name' => $method->getName(),
                        'parameters' => $this->analyzeMethodParameters($method),
                        'return_type' => $method->getReturnType()?->getName(),
                        'doc_comment' => $method->getDocComment(),
                        'route_binding' => $this->findRouteBinding($className, $method->getName()),
                    ];
                }
            }
            
            $controllers[] = [
                'class' => $className,
                'file' => $file,
                'methods' => $methods,
                'doc_comment' => $reflection->getDocComment(),
            ];
        }
        
        $this->documentation['controllers'] = $controllers;
    }

    /**
     * Analyze models
     */
    private function analyzeModels(): void
    {
        $models = [];
        $modelFiles = File::glob(base_path('app/Models/**/*.php'));
        
        foreach ($modelFiles as $file) {
            $className = $this->extractClassName($file);
            $reflection = new ReflectionClass($className);
            
            // Check if it's a Laravel model
            if ($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
                $model = new $className();
                
                $models[] = [
                    'class' => $className,
                    'table' => $model->getTable(),
                    'fillable' => $model->getFillable(),
                    'hidden' => $model->getHidden(),
                    'casts' => $model->getCasts(),
                    'relationships' => $this->analyzeModelRelationships($reflection),
                    'scopes' => $this->analyzeModelScopes($reflection),
                    'doc_comment' => $reflection->getDocComment(),
                ];
            }
        }
        
        $this->documentation['models'] = $models;
    }

    /**
     * Generate documentation
     */
    private function generateDocumentation(): void
    {
        $format = $this->option('format');
        $output = $this->option('output');
        
        switch ($format) {
            case 'markdown':
                $content = $this->generateMarkdownDocumentation();
                break;
            case 'json':
                $content = json_encode($this->documentation, JSON_PRETTY_PRINT);
                break;
            case 'html':
                $content = $this->generateHtmlDocumentation();
                break;
            default:
                $content = $this->generateMarkdownDocumentation();
        }
        
        // Ensure directory exists
        $directory = dirname($output);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        File::put($output, $content);
        
        $this->info("Documentation generated: {$output}");
    }

    /**
     * Generate Markdown documentation
     *
     * @return string
     */
    private function generateMarkdownDocumentation(): string
    {
        $content = "# API Documentation\n\n";
        $content .= "Generated: " . now()->toISOString() . "\n\n";
        
        // Overview
        $content .= "## Overview\n\n";
        $content .= "This document provides comprehensive API documentation for the Service Learning Management system.\n\n";
        
        // Authentication
        $content .= "## Authentication\n\n";
        $content .= "The API uses Laravel Sanctum for authentication. Include the following header in your requests:\n\n";
        $content .= "```\nAuthorization: Bearer {your-token}\n```\n\n";
        
        // Rate Limiting
        $content .= "## Rate Limiting\n\n";
        $content .= "API requests are rate limited to prevent abuse. Limits are:\n\n";
        $content .= "- **Authenticated requests**: 60 requests per minute\n";
        $content .= "- **Unauthenticated requests**: 30 requests per minute\n\n";
        
        // Endpoints
        $content .= "## API Endpoints\n\n";
        
        if (isset($this->documentation['routes'])) {
            foreach ($this->documentation['routes'] as $route) {
                $content .= $this->generateRouteDocumentation($route);
            }
        }
        
        // Models
        $content .= "## Data Models\n\n";
        
        if (isset($this->documentation['models'])) {
            foreach ($this->documentation['models'] as $model) {
                $content .= $this->generateModelDocumentation($model);
            }
        }
        
        // Error Codes
        $content .= "## Error Codes\n\n";
        $content .= "| Code | Description |\n";
        $content .= "|------|-------------|\n";
        $content .= "| 200 | Success |\n";
        $content .= "| 201 | Created |\n";
        $content .= "| 400 | Bad Request |\n";
        $content .= "| 401 | Unauthorized |\n";
        $content .= "| 403 | Forbidden |\n";
        $content .= "| 404 | Not Found |\n";
        $content .= "| 422 | Validation Error |\n";
        $content .= "| 429 | Too Many Requests |\n";
        $content .= "| 500 | Internal Server Error |\n\n";
        
        return $content;
    }

    /**
     * Generate route documentation
     *
     * @param array<string, mixed> $route
     * @return string
     */
    private function generateRouteDocumentation(array $route): string
    {
        $content = "### " . strtoupper(implode(', ', $route['methods'])) . " {$route['uri']}\n\n";
        
        if ($route['name']) {
            $content .= "**Route Name**: `{$route['name']}`\n\n";
        }
        
        if ($route['controller']) {
            $content .= "**Controller**: `{$route['controller']}@{$route['action']}`\n\n";
        }
        
        // Middleware
        if (!empty($route['middleware'])) {
            $content .= "**Middleware**: " . implode(', ', $route['middleware']) . "\n\n";
        }
        
        // Parameters
        $content .= "#### Parameters\n\n";
        $content .= "| Parameter | Type | Required | Description |\n";
        $content .= "|-----------|------|----------|-------------|\n";
        
        // Extract parameters from URI
        preg_match_all('/\{([^}]+)\}/', $route['uri'], $matches);
        foreach ($matches[1] as $param) {
            $required = !str_contains($param, '?');
            $paramName = str_replace('?', '', $param);
            $content .= "| {$paramName} | string | " . ($required ? 'Yes' : 'No') . " | Route parameter |\n";
        }
        
        $content .= "\n";
        
        // Example request
        $content .= "#### Example Request\n\n";
        $content .= "```bash\n";
        $method = $route['methods'][0] ?? 'GET';
        $content .= "curl -X {$method} \\\n";
        $content .= "  'http://localhost/api{$route['uri']}' \\\n";
        $content .= "  -H 'Authorization: Bearer {your-token}' \\\n";
        $content .= "  -H 'Content-Type: application/json'\n";
        $content .= "```\n\n";
        
        // Example response
        $content .= "#### Example Response\n\n";
        $content .= "```json\n";
        $content .= "{\n";
        $content .= "  \"success\": true,\n";
        $content .= "  \"data\": {},\n";
        $content .= "  \"message\": \"Success\"\n";
        $content .= "}\n";
        $content .= "```\n\n";
        
        return $content;
    }

    /**
     * Generate model documentation
     *
     * @param array<string, mixed> $model
     * @return string
     */
    private function generateModelDocumentation(array $model): string
    {
        $content = "### {$model['class']}\n\n";
        
        if ($model['doc_comment']) {
            $content .= $this->parseDocComment($model['doc_comment']) . "\n\n";
        }
        
        $content .= "**Table**: `{$model['table']}`\n\n";
        
        // Fillable fields
        if (!empty($model['fillable'])) {
            $content .= "#### Fillable Fields\n\n";
            $content .= "| Field | Type | Description |\n";
            $content .= "|-------|------|-------------|\n";
            
            foreach ($model['fillable'] as $field) {
                $cast = $model['casts'][$field] ?? 'string';
                $content .= "| {$field} | {$cast} | - |\n";
            }
            
            $content .= "\n";
        }
        
        // Relationships
        if (!empty($model['relationships'])) {
            $content .= "#### Relationships\n\n";
            $content .= "| Relationship | Type | Related Model |\n";
            $content .= "|--------------|------|---------------|\n";
            
            foreach ($model['relationships'] as $relationship) {
                $content .= "| {$relationship['name']} | {$relationship['type']} | {$relationship['model']} |\n";
            }
            
            $content .= "\n";
        }
        
        return $content;
    }

    /**
     * Generate HTML documentation
     *
     * @return string
     */
    private function generateHtmlDocumentation(): string
    {
        $content = "<!DOCTYPE html>\n<html>\n<head>\n";
        $content .= "<title>API Documentation</title>\n";
        $content .= "<style>\n";
        $content .= "body { font-family: Arial, sans-serif; margin: 40px; }\n";
        $content .= "h1 { color: #333; }\n";
        $content .= "h2 { color: #666; border-bottom: 1px solid #ccc; }\n";
        $content .= "h3 { color: #888; }\n";
        $content .= "code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }\n";
        $content .= "pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }\n";
        $content .= "table { border-collapse: collapse; width: 100%; }\n";
        $content .= "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\n";
        $content .= "th { background-color: #f2f2f2; }\n";
        $content .= "</style>\n</head>\n<body>\n";
        
        $content .= "<h1>API Documentation</h1>\n";
        $content .= "<p><strong>Generated:</strong> " . now()->toISOString() . "</p>\n";
        
        // Convert markdown to HTML
        $markdown = $this->generateMarkdownDocumentation();
        $html = $this->markdownToHtml($markdown);
        
        $content .= $html;
        $content .= "</body>\n</html>";
        
        return $content;
    }

    // Helper methods...

    /**
     * Extract class name from file
     *
     * @param string $file
     * @return string
     */
    private function extractClassName(string $file): string
    {
        $content = File::get($file);
        preg_match('/namespace\s+([^;]+)/', $content, $namespaceMatches);
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        
        if (isset($namespaceMatches[1]) && isset($classMatches[1])) {
            return $namespaceMatches[1] . '\\' . $classMatches[1];
        }
        
        return '';
    }

    /**
     * Analyze method parameters
     *
     * @param \ReflectionMethod $method
     * @return array<string, mixed>
     */
    private function analyzeMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = [
                'name' => $parameter->getName(),
                'type' => $parameter->getType()?->getName(),
                'required' => !$parameter->isOptional(),
                'default' => $parameter->isOptional() ? $parameter->getDefaultValue() : null,
            ];
        }
        
        return $parameters;
    }

    /**
     * Find route binding
     *
     * @param string $controller
     * @param string $method
     * @return array<string, mixed>|null
     */
    private function findRouteBinding(string $controller, string $method): ?array
    {
        foreach ($this->documentation['routes'] as $route) {
            if ($route['controller'] === $controller && $route['action'] === $method) {
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Analyze model relationships
     *
     * @param \ReflectionClass $reflection
     * @return array<string, mixed>
     */
    private function analyzeModelRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            if ($docComment && preg_match('/@return\s+([^\\s]+)/', $docComment, $matches)) {
                $returnType = $matches[1];
                if (str_contains($returnType, 'BelongsTo') || 
                    str_contains($returnType, 'HasMany') || 
                    str_contains($returnType, 'HasOne') || 
                    str_contains($returnType, 'BelongsToMany')) {
                    
                    $relationships[] = [
                        'name' => $method->getName(),
                        'type' => $returnType,
                        'model' => 'Unknown', // Would need more sophisticated analysis
                    ];
                }
            }
        }
        
        return $relationships;
    }

    /**
     * Analyze model scopes
     *
     * @param \ReflectionClass $reflection
     * @return array<string>
     */
    private function analyzeModelScopes(ReflectionClass $reflection): array
    {
        $scopes = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'scope')) {
                $scopes[] = lcfirst(substr($method->getName(), 5));
            }
        }
        
        return $scopes;
    }

    /**
     * Parse doc comment
     *
     * @param string $docComment
     * @return string
     */
    private function parseDocComment(string $docComment): string
    {
        $lines = explode("\n", $docComment);
        $description = [];
        
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*/");
            if (!empty($line) && !str_starts_with($line, '@')) {
                $description[] = $line;
            }
        }
        
        return implode(' ', $description);
    }

    /**
     * Convert markdown to HTML
     *
     * @param string $markdown
     * @return string
     */
    private function markdownToHtml(string $markdown): string
    {
        // Simple markdown to HTML conversion
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // Code blocks
        $html = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code>$2</code></pre>', $html);
        
        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        
        // Tables
        $html = preg_replace('/\|(.*)\|/', '<tr><td>' . str_replace('|', '</td><td>', '$1') . '</td></tr>', $html);
        
        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // Lists
        $html = preg_replace('/^- (.*$)/m', '<li>$1</li>', $html);
        
        return $html;
    }
} 