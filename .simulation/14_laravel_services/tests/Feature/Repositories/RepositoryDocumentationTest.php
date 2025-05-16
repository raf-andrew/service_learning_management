<?php

namespace Tests\Feature\Repositories;

use App\Repositories\UserRepository;
use App\Repositories\CourseRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Tests\TestCase;

class RepositoryDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private array $repositories = [
        UserRepository::class,
        CourseRepository::class,
        PaymentRepository::class,
        NotificationRepository::class
    ];

    public function test_repository_methods_have_documentation()
    {
        foreach ($this->repositories as $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                // Skip inherited methods
                if ($method->getDeclaringClass()->getName() !== $repositoryClass) {
                    continue;
                }

                $docComment = $method->getDocComment();
                $this->assertNotEmpty(
                    $docComment,
                    "Method {$method->getName()} in {$repositoryClass} is missing documentation"
                );

                // Verify method documentation includes:
                // 1. Description
                // 2. Parameters
                // 3. Return type
                // 4. Exceptions
                $this->assertStringContainsString(
                    '@param',
                    $docComment,
                    "Method {$method->getName()} in {$repositoryClass} is missing parameter documentation"
                );

                $this->assertStringContainsString(
                    '@return',
                    $docComment,
                    "Method {$method->getName()} in {$repositoryClass} is missing return type documentation"
                );

                $this->assertStringContainsString(
                    '@throws',
                    $docComment,
                    "Method {$method->getName()} in {$repositoryClass} is missing exception documentation"
                );
            }
        }
    }

    public function test_repository_parameters_are_documented()
    {
        foreach ($this->repositories as $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $repositoryClass) {
                    continue;
                }

                $parameters = $method->getParameters();
                $docComment = $method->getDocComment();

                foreach ($parameters as $parameter) {
                    $this->assertStringContainsString(
                        $parameter->getName(),
                        $docComment,
                        "Parameter {$parameter->getName()} in {$method->getName()} is not documented"
                    );
                }
            }
        }
    }

    public function test_repository_return_types_are_documented()
    {
        foreach ($this->repositories as $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $repositoryClass) {
                    continue;
                }

                $returnType = $method->getReturnType();
                $docComment = $method->getDocComment();

                if ($returnType) {
                    $this->assertStringContainsString(
                        $returnType->getName(),
                        $docComment,
                        "Return type for {$method->getName()} is not documented"
                    );
                }
            }
        }
    }

    public function test_repository_exceptions_are_documented()
    {
        foreach ($this->repositories as $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $repositoryClass) {
                    continue;
                }

                $docComment = $method->getDocComment();
                $this->assertStringContainsString(
                    '@throws',
                    $docComment,
                    "Method {$method->getName()} is missing exception documentation"
                );
            }
        }
    }

    public function test_repository_class_has_documentation()
    {
        foreach ($this->repositories as $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            $docComment = $reflection->getDocComment();

            $this->assertNotEmpty(
                $docComment,
                "Repository class {$repositoryClass} is missing documentation"
            );

            // Verify class documentation includes:
            // 1. Description
            // 2. Usage examples
            // 3. Dependencies
            $this->assertStringContainsString(
                '@package',
                $docComment,
                "Repository class {$repositoryClass} is missing package documentation"
            );

            $this->assertStringContainsString(
                '@author',
                $docComment,
                "Repository class {$repositoryClass} is missing author documentation"
            );
        }
    }

    public function test_repository_relationships_are_documented()
    {
        foreach ($this->repositories as $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            $docComment = $reflection->getDocComment();

            $this->assertStringContainsString(
                '@property',
                $docComment,
                "Repository class {$repositoryClass} is missing relationship documentation"
            );
        }
    }
} 