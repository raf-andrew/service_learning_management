<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ConcurrentRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that concurrent requests are handled correctly
     *
     * @return void
     */
    public function test_concurrent_requests()
    {
        $user = User::factory()->create();
        $requests = [];
        $concurrentRequests = 10;

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $requests[] = $this->async()->get('/api/test');
        }

        // Wait for all requests to complete
        $responses = $this->await($requests);

        // Verify all requests were successful
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->status());
        }
    }

    /**
     * Test that concurrent database operations are handled correctly
     *
     * @return void
     */
    public function test_concurrent_database_operations()
    {
        $user = User::factory()->create();
        $operations = [];
        $concurrentOperations = 10;

        // Simulate concurrent database operations
        for ($i = 0; $i < $concurrentOperations; $i++) {
            $operations[] = $this->async()->post('/api/update', [
                'user_id' => $user->id,
                'data' => ['count' => $i]
            ]);
        }

        // Wait for all operations to complete
        $responses = $this->await($operations);

        // Verify all operations were successful
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->status());
        }

        // Verify database consistency
        $this->assertDatabaseHas('users', [
            'id' => $user->id
        ]);
    }

    /**
     * Test that concurrent file operations are handled correctly
     *
     * @return void
     */
    public function test_concurrent_file_operations()
    {
        $operations = [];
        $concurrentOperations = 10;

        // Simulate concurrent file operations
        for ($i = 0; $i < $concurrentOperations; $i++) {
            $operations[] = $this->async()->post('/api/file', [
                'content' => "Test content $i"
            ]);
        }

        // Wait for all operations to complete
        $responses = $this->await($operations);

        // Verify all operations were successful
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->status());
        }
    }

    /**
     * Test that concurrent API calls are rate limited correctly
     *
     * @return void
     */
    public function test_concurrent_api_rate_limiting()
    {
        $requests = [];
        $concurrentRequests = 100; // More than the rate limit

        // Simulate concurrent API calls
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $requests[] = $this->async()->get('/api/rate-limited');
        }

        // Wait for all requests to complete
        $responses = $this->await($requests);

        // Count successful and rate-limited responses
        $successful = 0;
        $rateLimited = 0;

        foreach ($responses as $response) {
            if ($response->status() === 200) {
                $successful++;
            } elseif ($response->status() === 429) {
                $rateLimited++;
            }
        }

        // Verify rate limiting worked
        $this->assertLessThanOrEqual(60, $successful); // Assuming 60 requests per minute limit
        $this->assertGreaterThan(0, $rateLimited);
    }

    /**
     * Test that concurrent cache operations are handled correctly
     *
     * @return void
     */
    public function test_concurrent_cache_operations()
    {
        $operations = [];
        $concurrentOperations = 10;
        $key = 'test_key';

        // Simulate concurrent cache operations
        for ($i = 0; $i < $concurrentOperations; $i++) {
            $operations[] = $this->async()->post('/api/cache', [
                'key' => $key,
                'value' => "value_$i"
            ]);
        }

        // Wait for all operations to complete
        $responses = $this->await($operations);

        // Verify all operations were successful
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->status());
        }

        // Verify final cache value
        $this->assertEquals("value_" . ($concurrentOperations - 1), cache()->get($key));
    }
} 