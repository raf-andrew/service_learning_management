<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\Traits\CodespacesTestTrait;
use Tests\Traits\ModuleTestTrait;
use Tests\Traits\SecurityTestTrait;
use Tests\Traits\PerformanceTestTrait;

abstract class BaseTestCase extends LaravelTestCase
{
    use CreatesApplication,
        RefreshDatabase,
        CodespacesTestTrait,
        ModuleTestTrait,
        SecurityTestTrait,
        PerformanceTestTrait;

    /**
     * Test configuration
     */
    protected array $testConfig = [
        'database' => 'sqlite',
        'cache' => 'array',
        'queue' => 'sync',
        'session' => 'array',
    ];

    /**
     * Modules to load for testing
     */
    protected array $testModules = [];

    /**
     * Performance thresholds
     */
    protected array $performanceThresholds = [
        'response_time' => 200, // ms
        'memory_usage' => 512, // MB
        'database_queries' => 10,
    ];

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTestEnvironment();
        $this->loadTestModules();
        $this->setupTestDatabase();
        $this->setupTestCache();
        $this->setupTestViews();
    }

    /**
     * Tear down the test environment
     */
    protected function tearDown(): void
    {
        $this->cleanupTestData();
        $this->resetPerformanceMetrics();
        
        parent::tearDown();
    }

    /**
     * Configure test environment
     */
    protected function configureTestEnvironment(): void
    {
        // Set test environment
        Config::set('app.env', 'testing');
        Config::set('app.debug', true);

        // Configure test services
        foreach ($this->testConfig as $service => $driver) {
            Config::set("{$service}.default", $driver);
        }

        // Disable external services in testing
        Config::set('mail.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('cache.default', 'array');
        Config::set('session.driver', 'array');

        // Configure codespaces for testing
        if (Config::get('codespaces.enabled', false)) {
            $this->setUpCodespacesTest();
        }
    }

    /**
     * Load test modules
     */
    protected function loadTestModules(): void
    {
        foreach ($this->testModules as $module) {
            $this->loadTestModule($module);
        }
    }

    /**
     * Load a specific test module
     */
    protected function loadTestModule(string $moduleName): void
    {
        $modulePath = base_path("modules/{$moduleName}");
        
        if (!file_exists($modulePath)) {
            $this->markTestSkipped("Module {$moduleName} not found");
            return;
        }

        // Load module configuration
        $configPath = "{$modulePath}/config";
        if (is_dir($configPath)) {
            $this->loadModuleConfig($moduleName, $configPath);
        }

        // Load module routes
        $routesPath = "{$modulePath}/routes";
        if (is_dir($routesPath)) {
            $this->loadModuleRoutes($moduleName, $routesPath);
        }

        // Load module views
        $viewsPath = "{$modulePath}/views";
        if (is_dir($viewsPath)) {
            View::addNamespace($moduleName, $viewsPath);
        }
    }

    /**
     * Load module configuration
     */
    protected function loadModuleConfig(string $moduleName, string $configPath): void
    {
        $files = glob("{$configPath}/*.php");
        
        foreach ($files as $file) {
            $configKey = basename($file, '.php');
            $config = require $file;
            Config::set("modules.{$moduleName}.{$configKey}", $config);
        }
    }

    /**
     * Load module routes
     */
    protected function loadModuleRoutes(string $moduleName, string $routesPath): void
    {
        $files = glob("{$routesPath}/*.php");
        
        foreach ($files as $file) {
            $routeGroup = basename($file, '.php');
            $this->app['router']->group([
                'prefix' => "modules/{$moduleName}",
                'namespace' => "App\\Modules\\{$moduleName}\\Controllers",
            ], function () use ($file) {
                require $file;
            });
        }
    }

    /**
     * Setup test database
     */
    protected function setupTestDatabase(): void
    {
        // Use SQLite for testing by default
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Run migrations
        Artisan::call('migrate:fresh', ['--env' => 'testing']);
    }

    /**
     * Setup test cache
     */
    protected function setupTestCache(): void
    {
        Config::set('cache.default', 'array');
        Cache::flush();
    }

    /**
     * Setup test views
     */
    protected function setupTestViews(): void
    {
        View::addLocation(resource_path('views'));
        View::addLocation(base_path('resources/views'));
    }

    /**
     * Cleanup test data
     */
    protected function cleanupTestData(): void
    {
        // Clear cache
        Cache::flush();

        // Reset database
        DB::disconnect();

        // Clean up codespaces if enabled
        if (Config::get('codespaces.enabled', false)) {
            $this->tearDownCodespacesTest();
        }
    }

    /**
     * Assert performance metrics
     */
    protected function assertPerformanceMetrics(array $metrics): void
    {
        if (isset($metrics['response_time'])) {
            $this->assertLessThan(
                $this->performanceThresholds['response_time'],
                $metrics['response_time'],
                'Response time exceeded threshold'
            );
        }

        if (isset($metrics['memory_usage'])) {
            $this->assertLessThan(
                $this->performanceThresholds['memory_usage'],
                $metrics['memory_usage'],
                'Memory usage exceeded threshold'
            );
        }

        if (isset($metrics['database_queries'])) {
            $this->assertLessThan(
                $this->performanceThresholds['database_queries'],
                $metrics['database_queries'],
                'Database queries exceeded threshold'
            );
        }
    }

    /**
     * Assert security requirements
     */
    protected function assertSecurityRequirements(array $requirements): void
    {
        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'authentication':
                    $this->assertTrue($value, 'Authentication required');
                    break;
                case 'authorization':
                    $this->assertTrue($value, 'Authorization required');
                    break;
                case 'encryption':
                    $this->assertTrue($value, 'Encryption required');
                    break;
                case 'input_validation':
                    $this->assertTrue($value, 'Input validation required');
                    break;
                case 'csrf_protection':
                    $this->assertTrue($value, 'CSRF protection required');
                    break;
            }
        }
    }

    /**
     * Create test user
     */
    protected function createTestUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Create test data
     */
    protected function createTestData(string $model, array $attributes = []): mixed
    {
        $modelClass = "App\\Models\\{$model}";
        
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model {$model} not found");
        }

        return $modelClass::factory()->create($attributes);
    }

    /**
     * Mock service
     */
    protected function mockService(string $service, array $methods = []): void
    {
        $mock = $this->createMock($service);
        
        foreach ($methods as $method => $returnValue) {
            $mock->method($method)->willReturn($returnValue);
        }

        $this->app->instance($service, $mock);
    }

    /**
     * Assert database has record
     */
    protected function assertDatabaseHasRecord(string $table, array $data): void
    {
        $this->assertDatabaseHas($table, $data);
    }

    /**
     * Assert database missing record
     */
    protected function assertDatabaseMissingRecord(string $table, array $data): void
    {
        $this->assertDatabaseMissing($table, $data);
    }

    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure(array $structure, $response = null): void
    {
        if ($response === null) {
            $response = $this->response;
        }

        $response->assertJsonStructure($structure);
    }

    /**
     * Assert API response
     */
    protected function assertApiResponse(int $statusCode, array $data = []): void
    {
        $this->response->assertStatus($statusCode);
        
        if (!empty($data)) {
            $this->response->assertJson($data);
        }
    }

    /**
     * Reset performance metrics
     */
    protected function resetPerformanceMetrics(): void
    {
        // Reset any performance tracking
        if (method_exists($this, 'resetMetrics')) {
            $this->resetMetrics();
        }
    }

    /**
     * Get test configuration
     */
    protected function getTestConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->testConfig;
        }

        return data_get($this->testConfig, $key, $default);
    }

    /**
     * Set test configuration
     */
    protected function setTestConfig(string $key, $value): void
    {
        data_set($this->testConfig, $key, $value);
    }
} 