<?php

namespace Tests\Unit\Models;

use App\Models\Route;
use App\Models\ApiKey;
use App\Models\AccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessLogTest extends TestCase
{
    use RefreshDatabase;

    private Route $route;
    private ApiKey $apiKey;
    private AccessLog $accessLog;

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

        $this->accessLog = AccessLog::create([
            'route_id' => $this->route->id,
            'api_key_id' => $this->apiKey->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'request_method' => 'GET',
            'request_path' => '/api/test',
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_body' => ['test' => 'data'],
            'response_status' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
            'response_body' => ['success' => true],
            'response_time' => 0.5,
        ]);
    }

    public function test_access_log_belongs_to_route(): void
    {
        $this->assertEquals($this->route->id, $this->accessLog->route->id);
    }

    public function test_access_log_belongs_to_api_key(): void
    {
        $this->assertEquals($this->apiKey->id, $this->accessLog->apiKey->id);
    }

    public function test_access_log_can_check_successful_response(): void
    {
        $this->assertTrue($this->accessLog->isSuccessful());

        $this->accessLog->update(['response_status' => 400]);
        $this->assertFalse($this->accessLog->isSuccessful());
    }

    public function test_access_log_can_format_response_time(): void
    {
        $this->assertEquals('0.50ms', $this->accessLog->getFormattedResponseTime());
    }

    public function test_access_log_can_get_error_message(): void
    {
        $this->assertNull($this->accessLog->getErrorMessage());

        $this->accessLog->update(['error_message' => 'Test error']);
        $this->assertEquals('Test error', $this->accessLog->getErrorMessage());
    }

    public function test_access_log_can_format_request_headers(): void
    {
        $expected = json_encode(['Content-Type' => 'application/json'], JSON_PRETTY_PRINT);
        $this->assertEquals($expected, $this->accessLog->getRequestHeadersString());
    }

    public function test_access_log_can_format_response_headers(): void
    {
        $expected = json_encode(['Content-Type' => 'application/json'], JSON_PRETTY_PRINT);
        $this->assertEquals($expected, $this->accessLog->getResponseHeadersString());
    }
} 