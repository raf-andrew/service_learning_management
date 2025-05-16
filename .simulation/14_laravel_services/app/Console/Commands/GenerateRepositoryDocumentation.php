<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

class GenerateRepositoryDocumentation extends Command
{
    protected $signature = 'docs:generate-repository {repository? : The repository class to document}';
    protected $description = 'Generate documentation for repository classes';

    private array $repositories = [
        \App\Repositories\UserRepository::class,
        \App\Repositories\CourseRepository::class,
        \App\Repositories\PaymentRepository::class,
        \App\Repositories\NotificationRepository::class
    ];

    public function handle()
    {
        $repository = $this->argument('repository');

        if ($repository) {
            $this->documentRepository($repository);
        } else {
            foreach ($this->repositories as $repositoryClass) {
                $this->documentRepository($repositoryClass);
            }
        }
    }

    private function documentRepository(string $repositoryClass)
    {
        if (!class_exists($repositoryClass)) {
            $this->error("Repository class {$repositoryClass} does not exist");
            return;
        }

        $reflection = new ReflectionClass($repositoryClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $doc = "/**\n";
        $doc .= " * {$reflection->getShortName()} Repository\n";
        $doc .= " *\n";
        $doc .= " * @package App\\Repositories\n";
        $doc .= " * @author Service Learning Management Team\n";
        $doc .= " *\n";
        $doc .= " * @property-read \\App\\Models\\" . str_replace('Repository', '', $reflection->getShortName()) . " \$model\n";
        $doc .= " */\n";
        $doc .= "class {$reflection->getShortName()}\n{\n";

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $repositoryClass) {
                continue;
            }

            $doc .= $this->generateMethodDocumentation($method);
        }

        $doc .= "}\n";

        $filePath = $reflection->getFileName();
        $content = file_get_contents($filePath);
        $content = preg_replace('/\/\*\*.*?\*\/\s*class/s', $doc, $content, 1);
        file_put_contents($filePath, $content);

        $this->info("Documentation generated for {$repositoryClass}");
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
        $doc .= "     * @throws \\Illuminate\\Database\\QueryException\n";
        $doc .= "     * @throws \\Illuminate\\Database\\Eloquent\\ModelNotFoundException\n";

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