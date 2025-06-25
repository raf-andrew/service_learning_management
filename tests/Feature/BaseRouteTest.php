<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

abstract class BaseRouteTest extends TestCase
{
    protected array $testResults = [];
    protected string $reportPath;
    protected array $routes = [];
    protected array $coverage = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportPath = storage_path('app/test-reports/route-tests/' . $this->getReportFileName());
        $this->ensureReportDirectoryExists();
        $this->collectRoutes();
    }

    protected function tearDown(): void
    {
        $this->generateReport();
        parent::tearDown();
    }

    protected function getReportFileName(): string
    {
        $class = class_basename($this);
        return strtolower(str_replace('Test', '', $class)) . '-report.json';
    }

    protected function ensureReportDirectoryExists(): void
    {
        $directory = dirname($this->reportPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    protected function collectRoutes(): void
    {
        $this->routes = collect(Route::getRoutes())->filter(function ($route) {
            return $this->shouldTestRoute($route);
        })->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        })->values()->all();
    }

    protected function shouldTestRoute($route): bool
    {
        return true; // Override in child classes to filter routes
    }

    protected function recordTestResult(string $route, string $method, TestResponse $response, array $data = []): void
    {
        $this->testResults[] = [
            'route' => $route,
            'method' => $method,
            'status' => $response->status(),
            'success' => $response->successful(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->coverage[$route] = true;
    }

    protected function generateReport(): void
    {
        $report = [
            'name' => class_basename($this),
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'results' => $this->testResults,
            'coverage' => [
                'total_routes' => count($this->routes),
                'tested_routes' => count($this->coverage),
                'coverage_percentage' => count($this->routes) > 0 
                    ? round((count($this->coverage) / count($this->routes)) * 100, 2)
                    : 0,
                'untested_routes' => array_diff(
                    array_column($this->routes, 'uri'),
                    array_keys($this->coverage)
                ),
            ],
            'summary' => [
                'total_tests' => count($this->testResults),
                'passed' => count(array_filter($this->testResults, fn($result) => $result['success'])),
                'failed' => count(array_filter($this->testResults, fn($result) => !$result['success'])),
            ],
        ];

        File::put($this->reportPath, json_encode($report, JSON_PRETTY_PRINT));
    }

    protected function assertRouteCoverage(): void
    {
        $this->assertEquals(
            count($this->routes),
            count($this->coverage),
            'Not all routes were tested. Missing: ' . implode(', ', array_diff(
                array_column($this->routes, 'uri'),
                array_keys($this->coverage)
            ))
        );
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }
} 