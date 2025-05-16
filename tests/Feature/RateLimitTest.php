<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
    }

    /**
     * Test that rate limiting works for API endpoints
     *
     * @return void
     */
    public function test_api_rate_limiting()
    {
        $maxAttempts = 60;
        $decayMinutes = 1;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->get('/api/test');
            $this->assertEquals(200, $response->status());
        }

        $response = $this->get('/api/test');
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that rate limiting resets after decay period
     *
     * @return void
     */
    public function test_rate_limit_reset()
    {
        $maxAttempts = 60;
        $decayMinutes = 1;

        // Hit the rate limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->get('/api/test');
        }

        // Should be rate limited
        $response = $this->get('/api/test');
        $this->assertEquals(429, $response->status());

        // Travel forward in time
        $this->travel($decayMinutes + 1)->minutes();

        // Should work again
        $response = $this->get('/api/test');
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test that rate limiting works per IP address
     *
     * @return void
     */
    public function test_rate_limit_per_ip()
    {
        $maxAttempts = 60;

        // First IP hits the limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
                 ->get('/api/test');
        }

        // First IP should be rate limited
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
                        ->get('/api/test');
        $this->assertEquals(429, $response->status());

        // Second IP should still work
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2'])
                        ->get('/api/test');
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test that rate limiting works with different keys
     *
     * @return void
     */
    public function test_rate_limit_different_keys()
    {
        $maxAttempts = 60;

        // Hit limit on first endpoint
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->get('/api/test1');
        }

        // First endpoint should be rate limited
        $response = $this->get('/api/test1');
        $this->assertEquals(429, $response->status());

        // Second endpoint should still work
        $response = $this->get('/api/test2');
        $this->assertEquals(200, $response->status());
    }
} 