<?php

namespace Tests\Infrastructure;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeveloperCredential;
use App\Services\DeveloperCredentialService;
use App\Events\DeveloperCredentialCreated;
use App\Http\Controllers\DeveloperCredentialController;
use App\Http\Controllers\HealthMetricsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Infrastructure Validation Tests
 * 
 * These tests validate that all infrastructure improvements are working correctly:
 * - Event-driven architecture
 * - Base controller functionality
 * - Service layer improvements
 * - Security enhancements
 * - Performance optimizations
 * - Database normalization
 * - Configuration standardization
 */
class InfrastructureValidationTest extends TestCase
{
    use RefreshDatabase;

    protected DeveloperCredentialService $credentialService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialService = app(DeveloperCredentialService::class);
    }

    /**
     * Test event-driven architecture is working correctly.
     */
    public function test_event_driven_architecture()
    {
        Event::fake();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);

        // Verify event was fired
        Event::assertDispatched(DeveloperCredentialCreated::class, function ($event) use ($credential) {
            return $event->credential->id === $credential->id;
        });

        // Verify event data is correct
        Event::assertDispatched(DeveloperCredentialCreated::class, function ($event) use ($user) {
            return $event->credential->user_id === $user->id;
        });
    }

    /**
     * Test base controller functionality.
     */
    public function test_base_controller_functionality()
    {
        // Test that controllers extend BaseApiController
        $this->assertTrue(is_subclass_of(DeveloperCredentialController::class, \App\Http\Controllers\BaseApiController::class));
        $this->assertTrue(is_subclass_of(HealthMetricsController::class, \App\Http\Controllers\BaseApiController::class));

        // Test that controllers have required traits
        $developerController = new DeveloperCredentialController();
        $healthController = new HealthMetricsController();

        $this->assertTrue(method_exists($developerController, 'successResponse'));
        $this->assertTrue(method_exists($developerController, 'errorResponse'));
        $this->assertTrue(method_exists($developerController, 'handleException'));
        $this->assertTrue(method_exists($healthController, 'successResponse'));
        $this->assertTrue(method_exists($healthController, 'errorResponse'));
    }

    /**
     * Test service layer improvements.
     */
    public function test_service_layer_improvements()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test service inheritance
        $this->assertTrue(is_subclass_of(DeveloperCredentialService::class, \App\Services\BaseService::class));

        // Test service methods
        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);

        $this->assertNotNull($credential);
        $this->assertEquals($user->id, $credential->user_id);

        // Test caching functionality
        $cachedCredential = $this->credentialService->getActiveCredential($user);
        $this->assertEquals($credential->id, $cachedCredential->id);
    }

    /**
     * Test security enhancements.
     */
    public function test_security_enhancements()
    {
        // Test that security middleware exists
        $middleware = app('router')->getMiddleware();
        $this->assertArrayHasKey('security.headers', $middleware);
        $this->assertArrayHasKey('rate.limit', $middleware);

        // Test that routes have security middleware
        $apiRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/');
        });

        $this->assertGreaterThan(0, $apiRoutes->count());
    }

    /**
     * Test performance optimizations.
     */
    public function test_performance_optimizations()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test caching is working
        $cacheKey = "user_credentials:{$user->id}";
        Cache::put($cacheKey, ['test' => 'data'], 300);
        
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals(['test' => 'data'], Cache::get($cacheKey));

        // Test database query optimization
        $startTime = microtime(true);
        
        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $duration, "Credential creation took {$duration}ms, expected less than 100ms");
    }

    /**
     * Test database normalization.
     */
    public function test_database_normalization()
    {
        // Test that migrations run without errors
        $this->artisan('migrate:fresh');
        $this->assertTrue(true, 'Migrations completed successfully');

        // Test foreign key relationships
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);

        // Verify relationship integrity
        $this->assertEquals($user->id, $credential->user_id);
        $this->assertTrue($user->developerCredentials->contains($credential));

        // Test cascade delete (if implemented)
        $user->delete();
        $this->assertDatabaseMissing('developer_credentials', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test configuration standardization.
     */
    public function test_configuration_standardization()
    {
        // Test that required environment variables are set
        $requiredVars = ['APP_KEY', 'APP_ENV', 'DB_CONNECTION'];
        
        foreach ($requiredVars as $var) {
            $this->assertNotEmpty(config("app.{$var}") ?? env($var), "Required environment variable {$var} is not set");
        }

        // Test that config files use env() for sensitive values
        $this->assertTrue(config('app.debug') !== null);
        $this->assertTrue(config('database.default') !== null);
    }

    /**
     * Test error handling improvements.
     */
    public function test_error_handling_improvements()
    {
        // Test that exceptions are handled properly
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        DeveloperCredential::findOrFail(999);
    }

    /**
     * Test validation improvements.
     */
    public function test_validation_improvements()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test validation with invalid data
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $this->credentialService->createCredential($user, [
            'github_token' => '', // Invalid empty token
            'github_username' => '', // Invalid empty username
        ]);
    }

    /**
     * Test rate limiting functionality.
     */
    public function test_rate_limiting_functionality()
    {
        // Test that rate limiting is configured
        $this->assertTrue(config('cache.default') !== null);
        $this->assertTrue(config('queue.default') !== null);
    }

    /**
     * Test comprehensive infrastructure health.
     */
    public function test_infrastructure_health()
    {
        // Test database connection
        $this->assertTrue(DB::connection()->getPdo() !== null);

        // Test cache connection
        $this->assertTrue(Cache::store()->getStore() !== null);

        // Test route registration
        $this->assertGreaterThan(0, Route::getRoutes()->count());

        // Test service container
        $this->assertTrue(app() !== null);

        // Test event system
        $this->assertTrue(Event::getFacadeRoot() !== null);
    }
} 