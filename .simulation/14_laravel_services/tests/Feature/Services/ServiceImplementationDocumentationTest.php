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

class ServiceImplementationDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private array $services = [
        UserService::class,
        CourseService::class,
        PaymentService::class,
        NotificationService::class,
        AnalyticsService::class
    ];

    public function test_service_implementation_details_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $docComment = $reflection->getDocComment();

            // Check for implementation details
            $this->assertStringContainsString(
                '@implementation',
                $docComment,
                "Service class {$serviceClass} is missing implementation documentation"
            );

            // Check for dependencies
            $this->assertStringContainsString(
                '@dependencies',
                $docComment,
                "Service class {$serviceClass} is missing dependencies documentation"
            );

            // Check for usage examples
            $this->assertStringContainsString(
                '@example',
                $docComment,
                "Service class {$serviceClass} is missing usage examples"
            );
        }
    }

    public function test_service_method_implementations_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                    continue;
                }

                $docComment = $method->getDocComment();

                // Check for implementation details
                $this->assertStringContainsString(
                    '@implementation',
                    $docComment,
                    "Method {$method->getName()} in {$serviceClass} is missing implementation documentation"
                );

                // Check for algorithm description if applicable
                if ($this->isComplexMethod($method)) {
                    $this->assertStringContainsString(
                        '@algorithm',
                        $docComment,
                        "Complex method {$method->getName()} in {$serviceClass} is missing algorithm documentation"
                    );
                }
            }
        }
    }

    public function test_service_dependencies_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $docComment = $reflection->getDocComment();

            // Check for repository dependencies
            $this->assertStringContainsString(
                '@repository',
                $docComment,
                "Service class {$serviceClass} is missing repository dependencies documentation"
            );

            // Check for external service dependencies
            $this->assertStringContainsString(
                '@external',
                $docComment,
                "Service class {$serviceClass} is missing external service dependencies documentation"
            );
        }
    }

    public function test_service_usage_examples_are_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $docComment = $reflection->getDocComment();

            // Check for basic usage example
            $this->assertStringContainsString(
                '@example',
                $docComment,
                "Service class {$serviceClass} is missing basic usage example"
            );

            // Check for advanced usage example
            $this->assertStringContainsString(
                '@example-advanced',
                $docComment,
                "Service class {$serviceClass} is missing advanced usage example"
            );
        }
    }

    public function test_service_error_handling_is_documented()
    {
        foreach ($this->services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $docComment = $reflection->getDocComment();

            // Check for error handling documentation
            $this->assertStringContainsString(
                '@error-handling',
                $docComment,
                "Service class {$serviceClass} is missing error handling documentation"
            );

            // Check for recovery strategies
            $this->assertStringContainsString(
                '@recovery',
                $docComment,
                "Service class {$serviceClass} is missing recovery strategies documentation"
            );
        }
    }

    private function isComplexMethod(ReflectionMethod $method): bool
    {
        // Consider a method complex if it has more than 3 parameters
        // or if its name contains certain keywords
        $complexKeywords = ['process', 'calculate', 'validate', 'transform'];
        
        return count($method->getParameters()) > 3 ||
            in_array(strtolower($method->getName()), $complexKeywords);
    }
} 