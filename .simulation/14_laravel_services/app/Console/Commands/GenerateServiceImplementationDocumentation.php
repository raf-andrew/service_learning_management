<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class GenerateServiceImplementationDocumentation extends Command
{
    protected $signature = 'docs:generate-service-implementation {service? : The service class to document}';
    protected $description = 'Generate implementation documentation for service classes';

    private array $services = [
        \App\Services\UserService::class,
        \App\Services\CourseService::class,
        \App\Services\PaymentService::class,
        \App\Services\NotificationService::class,
        \App\Services\AnalyticsService::class
    ];

    public function handle()
    {
        $serviceClass = $this->argument('service');

        if ($serviceClass) {
            $this->documentService($serviceClass);
        } else {
            foreach ($this->services as $serviceClass) {
                $this->documentService($serviceClass);
            }
        }
    }

    private function documentService(string $serviceClass)
    {
        $reflection = new ReflectionClass($serviceClass);
        $docComment = $reflection->getDocComment();

        if (!$docComment) {
            $docComment = "/**\n";
            $docComment .= " * {$reflection->getShortName()} Service\n";
            $docComment .= " *\n";
            $docComment .= " * @implementation This service implements the business logic for {$reflection->getShortName()} operations\n";
            $docComment .= " * @dependencies\n";
            $docComment .= " *   - Repository: {$this->getRepositoryDependency($serviceClass)}\n";
            $docComment .= " *   - External Services: {$this->getExternalDependencies($serviceClass)}\n";
            $docComment .= " *\n";
            $docComment .= " * @example\n";
            $docComment .= " * ```php\n";
            $docComment .= " * \$service = app({$reflection->getShortName()}::class);\n";
            $docComment .= " * \$result = \$service->{$this->getBasicExampleMethod($reflection)};\n";
            $docComment .= " * ```\n";
            $docComment .= " *\n";
            $docComment .= " * @example-advanced\n";
            $docComment .= " * ```php\n";
            $docComment .= " * \$service = app({$reflection->getShortName()}::class);\n";
            $docComment .= " * \$result = \$service->{$this->getAdvancedExampleMethod($reflection)};\n";
            $docComment .= " * ```\n";
            $docComment .= " *\n";
            $docComment .= " * @error-handling\n";
            $docComment .= " * - Uses custom exceptions for error handling\n";
            $docComment .= " * - Implements proper error logging\n";
            $docComment .= " * - Provides detailed error messages\n";
            $docComment .= " *\n";
            $docComment .= " * @recovery\n";
            $docComment .= " * - Implements retry mechanisms for transient failures\n";
            $docComment .= " * - Provides fallback strategies for critical operations\n";
            $docComment .= " * - Maintains data consistency during error recovery\n";
            $docComment .= " */\n";

            $this->updateClassDocComment($reflection, $docComment);
        }

        // Document methods
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                continue;
            }

            $this->documentMethod($method);
        }
    }

    private function documentMethod(ReflectionMethod $method)
    {
        $docComment = $method->getDocComment();

        if (!$docComment) {
            $docComment = "/**\n";
            $docComment .= " * {$this->generateMethodDescription($method)}\n";
            $docComment .= " *\n";
            $docComment .= " * @implementation {$this->generateImplementationDetails($method)}\n";

            if ($this->isComplexMethod($method)) {
                $docComment .= " * @algorithm {$this->generateAlgorithmDescription($method)}\n";
            }

            foreach ($method->getParameters() as $parameter) {
                $docComment .= " * @param {$this->getParameterType($parameter)} \${$parameter->getName()} {$this->generateParameterDescription($parameter)}\n";
            }

            $returnType = $method->getReturnType();
            if ($returnType) {
                $docComment .= " * @return {$returnType->getName()} {$this->generateReturnDescription($method)}\n";
            }

            $docComment .= " * @throws {$this->getExceptionTypes($method)}\n";
            $docComment .= " */\n";

            $this->updateMethodDocComment($method, $docComment);
        }
    }

    private function generateMethodDescription(ReflectionMethod $method): string
    {
        $name = $method->getName();
        $words = preg_split('/(?=[A-Z])/', $name);
        return ucfirst(strtolower(implode(' ', $words)));
    }

    private function generateImplementationDetails(ReflectionMethod $method): string
    {
        $name = strtolower($method->getName());
        
        if (strpos($name, 'create') === 0) {
            return "Creates a new record with validation and error handling";
        } elseif (strpos($name, 'update') === 0) {
            return "Updates an existing record with validation and optimistic locking";
        } elseif (strpos($name, 'delete') === 0) {
            return "Deletes a record with proper cleanup and validation";
        } elseif (strpos($name, 'get') === 0) {
            return "Retrieves data with caching and error handling";
        }

        return "Implements the business logic for {$name} operation";
    }

    private function generateAlgorithmDescription(ReflectionMethod $method): string
    {
        $name = strtolower($method->getName());
        
        if (strpos($name, 'process') === 0) {
            return "Implements a multi-step processing algorithm with validation at each step";
        } elseif (strpos($name, 'calculate') === 0) {
            return "Uses mathematical formulas and business rules for calculations";
        } elseif (strpos($name, 'validate') === 0) {
            return "Implements comprehensive validation rules and error collection";
        }

        return "Uses standard business logic implementation";
    }

    private function generateParameterDescription(ReflectionParameter $parameter): string
    {
        $name = strtolower($parameter->getName());
        
        if (strpos($name, 'id') !== false) {
            return "The unique identifier";
        } elseif (strpos($name, 'data') !== false) {
            return "The data to be processed";
        } elseif (strpos($name, 'options') !== false) {
            return "Configuration options";
        }

        return "The {$name} parameter";
    }

    private function generateReturnDescription(ReflectionMethod $method): string
    {
        $name = strtolower($method->getName());
        
        if (strpos($name, 'get') === 0) {
            return "The retrieved data";
        } elseif (strpos($name, 'create') === 0) {
            return "The created entity";
        } elseif (strpos($name, 'update') === 0) {
            return "The updated entity";
        }

        return "The operation result";
    }

    private function getParameterType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();
        return $type ? $type->getName() : 'mixed';
    }

    private function getExceptionTypes(ReflectionMethod $method): string
    {
        $serviceName = $method->getDeclaringClass()->getShortName();
        return "\\App\\Exceptions\\{$serviceName}Exception";
    }

    private function isComplexMethod(ReflectionMethod $method): bool
    {
        $complexKeywords = ['process', 'calculate', 'validate', 'transform'];
        return count($method->getParameters()) > 3 ||
            in_array(strtolower($method->getName()), $complexKeywords);
    }

    private function getRepositoryDependency(string $serviceClass): string
    {
        $serviceName = basename(str_replace('\\', '/', $serviceClass));
        return str_replace('Service', 'Repository', $serviceName);
    }

    private function getExternalDependencies(string $serviceClass): string
    {
        $dependencies = [];
        
        if (strpos($serviceClass, 'Payment') !== false) {
            $dependencies[] = 'PaymentGateway';
        }
        if (strpos($serviceClass, 'Notification') !== false) {
            $dependencies[] = 'EmailService';
            $dependencies[] = 'SmsService';
        }
        if (strpos($serviceClass, 'Analytics') !== false) {
            $dependencies[] = 'AnalyticsProvider';
        }

        return implode(', ', $dependencies) ?: 'None';
    }

    private function getBasicExampleMethod(ReflectionClass $reflection): string
    {
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'get') === 0) {
                return $method->getName() . '()';
            }
        }
        return $methods[0]->getName() . '()';
    }

    private function getAdvancedExampleMethod(ReflectionClass $reflection): string
    {
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'process') === 0) {
                return $method->getName() . '()';
            }
        }
        return $methods[count($methods) - 1]->getName() . '()';
    }

    private function updateClassDocComment(ReflectionClass $reflection, string $docComment)
    {
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);
        
        $classDeclaration = "class {$reflection->getShortName()}";
        $pos = strpos($content, $classDeclaration);
        
        if ($pos !== false) {
            $content = substr_replace($content, $docComment . "\n" . $classDeclaration, $pos, strlen($classDeclaration));
            file_put_contents($filename, $content);
        }
    }

    private function updateMethodDocComment(ReflectionMethod $method, string $docComment)
    {
        $filename = $method->getDeclaringClass()->getFileName();
        $content = file_get_contents($filename);
        
        $methodDeclaration = "public function {$method->getName()}";
        $pos = strpos($content, $methodDeclaration);
        
        if ($pos !== false) {
            $content = substr_replace($content, $docComment . "\n    " . $methodDeclaration, $pos, strlen($methodDeclaration));
            file_put_contents($filename, $content);
        }
    }
} 