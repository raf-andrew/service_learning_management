<?php

namespace Tests\Unit\Models;

use App\Models\Route;
use App\Models\ApiKey;
use App\Models\RateLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private Route $route;
    private ApiKey $apiKey;
    private RateLimit $rateLimit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = Route::create([
            'name' => 'Test Route',
            'path' => '/api/test',
            'method' => 'GET',
            'target_url' => 'http://test-service.com',
            'service_name' => 'test-service',
        ]);

        $this->apiKey = ApiKey::create([
            'name' => 'Test API Key',
            'is_active' => true,
        ]);

        $this->rateLimit = RateLimit::create([
            'route_id' => $this->route->id,
            'api_key_id' => $this->apiKey->id,
            'window_start' => now(),
            'window_end' => now()->addMinutes(1),
        ]);
    }

    public function test_rate_limit_belongs_to_route(): void
    {
        $this->assertEquals($this->route->id, $this->rateLimit->route->id);
    }

    public function test_rate_limit_belongs_to_api_key(): void
    {
        $this->assertEquals($this->apiKey->id, $this->rateLimit->apiKey->id);
    }

    public function test_rate_limit_is_not_blocked_by_default(): void
    {
        $this->assertFalse($this->rateLimit->isBlocked());
    }

    public function test_rate_limit_can_be_blocked(): void
    {
        $this->rateLimit->update([
            'is_blocked' => true,
            'blocked_until' => now()->addMinutes(5),
        ]);

        $this->assertTrue($this->rateLimit->isBlocked());
    }

    public function test_rate_limit_block_expires(): void
    {
        $this->rateLimit->update([
            'is_blocked' => true,
            'blocked_until' => now()->subMinute(),
        ]);

        $this->assertFalse($this->rateLimit->isBlocked());
    }

    public function test_rate_limit_can_increment_request_count(): void
    {
        $initialCount = $this->rateLimit->requests_count;
        $this->rateLimit->incrementRequestCount();
        $this->assertEquals($initialCount + 1, $this->rateLimit->fresh()->requests_count);
    }

    public function test_rate_limit_can_reset_request_count(): void
    {
        $this->rateLimit->update(['requests_count' => 10]);
        $this->rateLimit->resetRequestCount();

        $this->assertEquals(0, $this->rateLimit->fresh()->requests_count);
        $this->assertNotNull($this->rateLimit->fresh()->window_start);
        $this->assertNotNull($this->rateLimit->fresh()->window_end);
    }
} 