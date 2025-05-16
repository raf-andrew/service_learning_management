<?php

namespace Tests\Feature\Services;

use App\Models\RateLimit;
use App\Services\RateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class RateLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private RateLimitService $rateLimitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitService = new RateLimitService();
    }

    public function test_is_within_limits_returns_true_for_first_request()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 10,
            'time_window' => 60,
        ]);

        $isWithinLimits = $this->rateLimitService->isWithinLimits('test-key', $rateLimit);

        $this->assertTrue($isWithinLimits);
    }

    public function test_is_within_limits_returns_false_when_limit_exceeded()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 2,
            'time_window' => 60,
        ]);

        // Make first request
        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);
        // Make second request
        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);
        // Make third request (should fail)
        $isWithinLimits = $this->rateLimitService->isWithinLimits('test-key', $rateLimit);

        $this->assertFalse($isWithinLimits);
    }

    public function test_get_remaining_requests_returns_correct_count()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 10,
            'time_window' => 60,
        ]);

        // Make 3 requests
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimitService->isWithinLimits('test-key', $rateLimit);
        }

        $remaining = $this->rateLimitService->getRemainingRequests('test-key', $rateLimit);

        $this->assertEquals(7, $remaining);
    }

    public function test_get_reset_time_returns_correct_timestamp()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 10,
            'time_window' => 60,
        ]);

        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);

        $resetTime = $this->rateLimitService->getResetTime('test-key');

        $this->assertNotNull($resetTime);
        $this->assertIsInt($resetTime);
        $this->assertGreaterThan(time(), $resetTime);
    }

    public function test_can_create_rate_limit()
    {
        $rateLimitData = [
            'name' => 'Test Rate Limit',
            'max_requests' => 100,
            'time_window' => 3600,
        ];

        $rateLimit = $this->rateLimitService->createRateLimit($rateLimitData);

        $this->assertDatabaseHas('rate_limits', $rateLimitData);
        $this->assertEquals($rateLimitData['name'], $rateLimit->name);
    }

    public function test_can_update_rate_limit()
    {
        $rateLimit = RateLimit::factory()->create();
        $updateData = [
            'max_requests' => 200,
            'time_window' => 7200,
        ];

        $updatedRateLimit = $this->rateLimitService->updateRateLimit($rateLimit, $updateData);

        $this->assertEquals($updateData['max_requests'], $updatedRateLimit->max_requests);
        $this->assertEquals($updateData['time_window'], $updatedRateLimit->time_window);
    }

    public function test_can_delete_rate_limit()
    {
        $rateLimit = RateLimit::factory()->create();

        $deleted = $this->rateLimitService->deleteRateLimit($rateLimit);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('rate_limits', ['id' => $rateLimit->id]);
    }

    public function test_get_rate_limit_headers_returns_correct_headers()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 10,
            'time_window' => 60,
        ]);

        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);

        $headers = $this->rateLimitService->getRateLimitHeaders('test-key', $rateLimit);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertEquals(10, $headers['X-RateLimit-Limit']);
        $this->assertEquals(9, $headers['X-RateLimit-Remaining']);
    }

    public function test_clear_rate_limit_removes_cache()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 10,
            'time_window' => 60,
        ]);

        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);
        $this->assertTrue(Cache::has('rate_limit:test-key'));

        $this->rateLimitService->clearRateLimit('test-key');
        $this->assertFalse(Cache::has('rate_limit:test-key'));
    }

    public function test_rate_limit_expires_after_time_window()
    {
        $rateLimit = RateLimit::factory()->create([
            'max_requests' => 2,
            'time_window' => 1, // 1 second
        ]);

        // Make first request
        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);
        // Make second request
        $this->rateLimitService->isWithinLimits('test-key', $rateLimit);
        // Should be at limit
        $this->assertFalse($this->rateLimitService->isWithinLimits('test-key', $rateLimit));

        // Wait for time window to expire
        sleep(2);

        // Should be able to make requests again
        $this->assertTrue($this->rateLimitService->isWithinLimits('test-key', $rateLimit));
    }
} 