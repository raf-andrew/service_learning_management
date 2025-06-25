<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeveloperCredential;
use App\Services\DeveloperCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Performance Tests
 * 
 * These tests benchmark critical operations and identify
 * performance bottlenecks in the application.
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected DeveloperCredentialService $credentialService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialService = app(DeveloperCredentialService::class);
    }

    /**
     * Test database query performance.
     */
    public function test_database_query_performance()
    {
        // Create test data
        $users = [];
        for ($i = 0; $i < 100; $i++) {
            $users[] = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        // Create credentials for each user
        foreach ($users as $user) {
            $this->credentialService->createCredential($user, [
                'github_token' => "ghp_token_{$user->id}",
                'github_username' => "user{$user->id}",
            ]);
        }

        // Benchmark query performance
        $startTime = microtime(true);
        
        $credentials = DeveloperCredential::with('user')->get();
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertCount(100, $credentials);
        $this->assertLessThan(100, $duration, "Query took {$duration}ms, expected less than 100ms");

        // Test pagination performance
        $startTime = microtime(true);
        
        $paginatedCredentials = DeveloperCredential::paginate(20);
        
        $endTime = microtime(true);
        $paginationDuration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(50, $paginationDuration, "Pagination took {$paginationDuration}ms, expected less than 50ms");
    }

    /**
     * Test cache performance.
     */
    public function test_cache_performance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);

        // Benchmark cache operations
        $iterations = 1000;
        
        // Test cache write performance
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            Cache::put("test_key_{$i}", "test_value_{$i}", 60);
        }
        
        $endTime = microtime(true);
        $writeDuration = ($endTime - $startTime) * 1000;
        $writePerSecond = $iterations / ($writeDuration / 1000);

        $this->assertGreaterThan(1000, $writePerSecond, "Cache write performance: {$writePerSecond} ops/sec");

        // Test cache read performance
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            Cache::get("test_key_{$i}");
        }
        
        $endTime = microtime(true);
        $readDuration = ($endTime - $startTime) * 1000;
        $readPerSecond = $iterations / ($readDuration / 1000);

        $this->assertGreaterThan(1000, $readPerSecond, "Cache read performance: {$readPerSecond} ops/sec");
    }

    /**
     * Test service method performance.
     */
    public function test_service_method_performance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Benchmark credential creation
        $startTime = microtime(true);
        
        $credential = $this->credentialService->createCredential($user, [
            'github_token' => 'ghp_test_token',
            'github_username' => 'testuser',
        ]);
        
        $endTime = microtime(true);
        $creationDuration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $creationDuration, "Credential creation took {$creationDuration}ms, expected less than 100ms");

        // Benchmark credential retrieval
        $startTime = microtime(true);
        
        $retrievedCredential = $this->credentialService->getActiveCredential($user);
        
        $endTime = microtime(true);
        $retrievalDuration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(50, $retrievalDuration, "Credential retrieval took {$retrievalDuration}ms, expected less than 50ms");
    }

    /**
     * Test memory usage during bulk operations.
     */
    public function test_memory_usage_during_bulk_operations()
    {
        $initialMemory = memory_get_usage();

        // Create bulk data
        $users = [];
        for ($i = 0; $i < 1000; $i++) {
            $users[] = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        $afterUserCreation = memory_get_usage();
        $userCreationMemory = $afterUserCreation - $initialMemory;

        // Create credentials for each user
        foreach ($users as $user) {
            $this->credentialService->createCredential($user, [
                'github_token' => "ghp_token_{$user->id}",
                'github_username' => "user{$user->id}",
            ]);
        }

        $afterCredentialCreation = memory_get_usage();
        $credentialCreationMemory = $afterCredentialCreation - $afterUserCreation;

        // Query all data
        $allCredentials = DeveloperCredential::with('user')->get();
        
        $afterQuery = memory_get_usage();
        $queryMemory = $afterQuery - $afterCredentialCreation;

        // Verify memory usage is reasonable (less than 50MB for 1000 records)
        $totalMemory = $afterQuery - $initialMemory;
        $this->assertLessThan(50 * 1024 * 1024, $totalMemory, "Total memory usage: " . round($totalMemory / 1024 / 1024, 2) . "MB");

        // Clean up
        unset($users, $allCredentials);
        $finalMemory = memory_get_usage();
        
        // Verify memory cleanup
        $this->assertLessThan($afterQuery, $finalMemory, "Memory should be cleaned up after unset");
    }

    /**
     * Test concurrent request performance.
     */
    public function test_concurrent_request_performance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create some test credentials
        for ($i = 0; $i < 10; $i++) {
            $this->credentialService->createCredential($user, [
                'github_token' => "ghp_token_{$i}",
                'github_username' => "user{$i}",
            ]);
        }

        // Simulate concurrent requests
        $concurrentRequests = 10;
        $startTime = microtime(true);

        $results = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $results[] = $this->credentialService->getUserCredentials($user);
        }

        $endTime = microtime(true);
        $totalDuration = ($endTime - $startTime) * 1000;
        $averageDuration = $totalDuration / $concurrentRequests;

        $this->assertLessThan(50, $averageDuration, "Average concurrent request time: {$averageDuration}ms");
        $this->assertCount($concurrentRequests, $results);
    }

    /**
     * Test database connection pool performance.
     */
    public function test_database_connection_pool_performance()
    {
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            DB::connection()->getPdo();
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        $averageDuration = $duration / $iterations;

        $this->assertLessThan(1, $averageDuration, "Average connection time: {$averageDuration}ms");
    }
} 