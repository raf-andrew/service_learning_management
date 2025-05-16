<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

class GenerateServiceDocumentation extends Command
{
    protected $signature = 'docs:generate-service {service? : The service class to document}';
    protected $description = 'Generate documentation for service classes';

    private array $services = [
        \App\Services\UserService::class,
        \App\Services\CourseService::class,
        \App\Services\PaymentService::class,
        \App\Services\NotificationService::class,
        \App\Services\AnalyticsService::class
    ];

    public function handle()
    {
        $service = $this->argument('service');

        if ($service) {
            $this->documentService($service);
        } else {
            foreach ($this->services as $serviceClass) {
                $this->documentService($serviceClass);
            }
        }
    }

    private function documentService(string $serviceClass)
    {
        if (!class_exists($serviceClass)) {
            $this->error("Service class {$serviceClass} does not exist");
            return;
        }

        $reflection = new ReflectionClass($serviceClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $doc = "/**\n";
        $doc .= " * {$reflection->getShortName()} Service\n";
        $doc .= " *\n";
        $doc .= " * @package App\\Services\n";
        $doc .= " * @author Service Learning Management Team\n";
        $doc .= " */\n";
        $doc .= "class {$reflection->getShortName()}\n{\n";

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                continue;
            }

            $doc .= $this->generateMethodDocumentation($method);
        }

        $doc .= "}\n";

        $filePath = $reflection->getFileName();
        $content = file_get_contents($filePath);
        $content = preg_replace('/\/\*\*.*?\*\/\s*class/s', $doc, $content, 1);
        file_put_contents($filePath, $content);

        $this->info("Documentation generated for {$serviceClass}");
    }

    private function generateMethodDocumentation(ReflectionMethod $method): string
    {
        $doc = "    /**\n";
        $doc .= "     * " . $this->getMethodDescription($method) . "\n";
        $doc .= "     *\n";

        // Document parameters
        foreach ($method->getParameters() as $parameter) {
            $type = $this->getParameterType($parameter);
            $doc .= "     * @param {$type} \${$parameter->getName()} " . $this->getParameterDescription($parameter) . "\n";
        }

        // Document return type
        $returnType = $this->getReturnType($method);
        $doc .= "     * @return {$returnType} " . $this->getReturnDescription($method) . "\n";

        // Document exceptions
        $doc .= "     * @throws \\App\\Exceptions\\ServiceException\n";

        $doc .= "     */\n";
        $doc .= "    public function {$method->getName()}(";
        $doc .= $this->getMethodSignature($method);
        $doc .= ")\n    {\n";
        $doc .= "        // TODO: Implement method\n";
        $doc .= "    }\n\n";

        return $doc;
    }

    private function getMethodDescription(ReflectionMethod $method): string
    {
        // Convert method name to readable description
        $name = preg_replace('/(?<!^)[A-Z]/', ' $0', $method->getName());
        return ucfirst(strtolower($name));
    }

    private function getParameterType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();
        if ($type instanceof ReflectionType) {
            return $type->getName();
        }
        return 'mixed';
    }

    private function getParameterDescription(ReflectionParameter $parameter): string
    {
        // Convert parameter name to readable description
        $name = preg_replace('/(?<!^)[A-Z]/', ' $0', $parameter->getName());
        return ucfirst(strtolower($name));
    }

    private function getReturnType(ReflectionMethod $method): string
    {
        $returnType = $method->getReturnType();
        if ($returnType instanceof ReflectionType) {
            return $returnType->getName();
        }
        return 'mixed';
    }

    private function getReturnDescription(ReflectionMethod $method): string
    {
        $returnType = $this->getReturnType($method);
        return "The {$returnType} result";
    }

    private function getMethodSignature(ReflectionMethod $method): string
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $param = '';
            if ($parameter->getType()) {
                $param .= $parameter->getType()->getName() . ' ';
            }
            $param .= '$' . $parameter->getName();
            if ($parameter->isDefaultValueAvailable()) {
                $param .= ' = ' . var_export($parameter->getDefaultValue(), true);
            }
            $parameters[] = $param;
        }
        return implode(', ', $parameters);
    }
} 