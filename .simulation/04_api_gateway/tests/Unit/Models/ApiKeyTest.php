<?php

namespace Tests\Unit\Models;

use App\Models\ApiKey;
use App\Models\AccessLog;
use App\Models\RateLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = ApiKey::create([
            'name' => 'Test API Key',
            'is_active' => true,
        ]);
    }

    public function test_api_key_has_generated_key_and_secret(): void
    {
        $this->assertNotNull($this->apiKey->key);
        $this->assertNotNull($this->apiKey->secret);
        $this->assertEquals(32, strlen($this->apiKey->key));
        $this->assertEquals(64, strlen($this->apiKey->secret));
    }

    public function test_api_key_can_have_access_logs(): void
    {
        $accessLog = AccessLog::create([
            'route_id' => 1,
            'api_key_id' => $this->apiKey->id,
            'ip_address' => '127.0.0.1',
            'request_method' => 'GET',
            'request_path' => '/api/test',
            'response_status' => 200,
            'response_time' => 0.5,
        ]);

        $this->assertTrue($this->apiKey->accessLogs->contains($accessLog));
    }

    public function test_api_key_can_have_rate_limits(): void
    {
        $rateLimit = RateLimit::create([
            'route_id' => 1,
            'api_key_id' => $this->apiKey->id,
            'window_start' => now(),
            'window_end' => now()->addMinutes(1),
        ]);

        $this->assertTrue($this->apiKey->rateLimits->contains($rateLimit));
    }

    public function test_api_key_is_active_by_default(): void
    {
        $this->assertTrue($this->apiKey->isActive());
    }

    public function test_api_key_can_be_deactivated(): void
    {
        $this->apiKey->update(['is_active' => false]);
        $this->assertFalse($this->apiKey->isActive());
    }

    public function test_api_key_expires(): void
    {
        $this->apiKey->update(['expires_at' => now()->subMinute()]);
        $this->assertFalse($this->apiKey->isActive());
    }

    public function test_api_key_can_update_last_used(): void
    {
        $this->apiKey->updateLastUsed();
        $this->assertNotNull($this->apiKey->fresh()->last_used_at);
    }

    public function test_api_key_can_generate_new_key_pair(): void
    {
        $keyPair = ApiKey::generate();
        $this->assertArrayHasKey('key', $keyPair);
        $this->assertArrayHasKey('secret', $keyPair);
        $this->assertEquals(32, strlen($keyPair['key']));
        $this->assertEquals(64, strlen($keyPair['secret']));
    }
} 