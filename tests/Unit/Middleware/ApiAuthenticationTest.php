<?php

namespace Tests\Unit\Middleware;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    public function test_middleware_returns_401_for_missing_api_key()
    {
        $middleware = new \App\Http\Middleware\ApiAuthentication();
        $request = new Request();

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('API key is required', $response->getData()->message);
        Log::shouldHaveReceived('warning')->with('API request missing key');
    }

    public function test_middleware_returns_401_for_invalid_api_key()
    {
        $middleware = new \App\Http\Middleware\ApiAuthentication();
        $request = new Request();
        $request->headers->set('X-API-Key', 'invalid-key');

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid API key', $response->getData()->message);
        Log::shouldHaveReceived('warning')->with('Invalid API key used: invalid-key');
    }

    public function test_middleware_returns_401_for_inactive_api_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => false
        ]);

        $middleware = new \App\Http\Middleware\ApiAuthentication();
        $request = new Request();
        $request->headers->set('X-API-Key', $apiKey->key);

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('API key is inactive', $response->getData()->message);
        Log::shouldHaveReceived('warning')->with('Inactive API key used: ' . $apiKey->key);
    }

    public function test_middleware_returns_401_for_expired_api_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'expires_at' => now()->subDay()
        ]);

        $middleware = new \App\Http\Middleware\ApiAuthentication();
        $request = new Request();
        $request->headers->set('X-API-Key', $apiKey->key);

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('API key has expired', $response->getData()->message);
        Log::shouldHaveReceived('warning')->with('Expired API key used: ' . $apiKey->key);
    }

    public function test_middleware_passes_for_valid_api_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'expires_at' => now()->addDay()
        ]);

        $middleware = new \App\Http\Middleware\ApiAuthentication();
        $request = new Request();
        $request->headers->set('X-API-Key', $apiKey->key);

        $next = function ($req) {
            return response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
        Log::shouldHaveReceived('info')->with('API request authenticated', [
            'key' => $apiKey->key,
            'name' => $apiKey->name
        ]);
    }

    public function test_middleware_handles_exception()
    {
        $middleware = new \App\Http\Middleware\ApiAuthentication();
        $request = new Request();
        $request->headers->set('X-API-Key', 'test-key');

        // Mock the ApiKey model to throw an exception
        $this->mock(ApiKey::class, function ($mock) {
            $mock->shouldReceive('where->first')->andThrow(new \Exception('Database error'));
        });

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal server error', $response->getData()->message);
        Log::shouldHaveReceived('error')->with('API authentication error', [
            'key' => 'test-key',
            'error' => 'Database error'
        ]);
    }
} 