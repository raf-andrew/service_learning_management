<?php

namespace Tests\Feature\Services;

use App\Services\UserService;
use App\Services\CourseService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Tests\TestCase;

class ServiceDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private array $services = [
        UserService::class,
        CourseService::class,
        PaymentService::class,
        NotificationService::class,
        AnalyticsService::class
    ];

    public function test_service_methods_have_documentation()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                // Skip inherited methods
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                    continue;
                }

                $docComment = $method->getDocComment();
                $this->assertNotEmpty(
                    $docComment,
                    "Method {$method->getName()} in {$serviceClass} is missing documentation"
                );

                // Verify method documentation includes:
                // 1. Description
                // 2. Parameters
                // 3. Return type
                // 4. Exceptions
                $this->assertStringContainsString(
                    '@param',
                    $docComment,
                    "Method {$method->getName()} in {$serviceClass} is missing parameter documentation"
                );

                $this->assertStringContainsString(
                    '@return',
                    $docComment,
                    "Method {$method->getName()} in {$serviceClass} is missing return type documentation"
                );

                $this->assertStringContainsString(
                    '@throws',
                    $docComment,
                    "Method {$method->getName()} in {$serviceClass} is missing exception documentation"
                );
            }
        }
    }

    public function test_service_parameters_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
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

    public function test_service_return_types_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
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

    public function test_service_exceptions_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
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

    public function test_service_class_has_documentation()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $docComment = $reflection->getDocComment();

            $this->assertNotEmpty(
                $docComment,
                "Service class {$serviceClass} is missing documentation"
            );

            // Verify class documentation includes:
            // 1. Description
            // 2. Usage examples
            // 3. Dependencies
            $this->assertStringContainsString(
                '@package',
                $docComment,
                "Service class {$serviceClass} is missing package documentation"
            );

            $this->assertStringContainsString(
                '@author',
                $docComment,
                "Service class {$serviceClass} is missing author documentation"
            );
        }
    }
} 