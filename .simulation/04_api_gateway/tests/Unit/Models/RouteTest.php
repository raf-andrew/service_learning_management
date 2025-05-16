<?php

namespace Tests\Unit\Models;

use App\Models\Route;
use App\Models\AccessLog;
use App\Models\RateLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = Route::create([
            'name' => 'Test Route',
            'path' => '/api/test',
            'method' => 'GET',
            'target_url' => 'http://test-service.com',
            'service_name' => 'test-service',
            'is_active' => true,
            'rate_limit' => 60,
            'timeout' => 30,
            'retry_count' => 3,
            'circuit_breaker_threshold' => 5,
            'circuit_breaker_timeout' => 60,
        ]);
    }

    public function test_route_can_have_access_logs(): void
    {
        $accessLog = AccessLog::create([
            'route_id' => $this->route->id,
            'api_key_id' => 1,
            'ip_address' => '127.0.0.1',
            'request_method' => 'GET',
            'request_path' => '/api/test',
            'response_status' => 200,
            'response_time' => 0.5,
        ]);

        $this->assertTrue($this->route->accessLogs->contains($accessLog));
    }

    public function test_route_can_have_rate_limits(): void
    {
        $rateLimit = RateLimit::create([
            'route_id' => $this->route->id,
            'api_key_id' => 1,
            'window_start' => now(),
            'window_end' => now()->addMinutes(1),
        ]);

        $this->assertTrue($this->route->rateLimits->contains($rateLimit));
    }

    public function test_route_is_active_by_default(): void
    {
        $this->assertTrue($this->route->isActive());
    }

    public function test_route_can_be_deactivated(): void
    {
        $this->route->update(['is_active' => false]);
        $this->assertFalse($this->route->isActive());
    }

    public function test_route_returns_full_target_url(): void
    {
        $this->assertEquals(
            'http://test-service.com/api/test',
            $this->route->getFullTargetUrl()
        );
    }

    public function test_route_handles_trailing_slashes_correctly(): void
    {
        $this->route->update([
            'target_url' => 'http://test-service.com/',
            'path' => '/api/test/',
        ]);

        $this->assertEquals(
            'http://test-service.com/api/test',
            $this->route->getFullTargetUrl()
        );
    }
} 