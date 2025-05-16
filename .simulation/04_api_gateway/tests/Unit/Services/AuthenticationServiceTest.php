<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthenticationService;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Mockery;

class AuthenticationServiceTest extends TestCase
{
    protected $service;
    protected $request;
    protected $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuthenticationService();
        $this->request = new Request();
        $this->route = [
            'auth_required' => true,
            'permissions' => ['test.permission']
        ];
    }

    public function test_skips_validation_for_public_routes()
    {
        $route = ['auth_required' => false];
        $this->assertTrue($this->service->validateRequest($this->request, $route));
    }

    public function test_throws_exception_for_missing_auth_header()
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('No authorization header');

        $this->service->validateRequest($this->request, $this->route);
    }

    public function test_validates_api_key()
    {
        $apiKey = 'test_api_key_123456789012345678901234';
        $this->request->headers->set('Authorization', 'Bearer ' . $apiKey);

        // Mock API key in database
        $key = new ApiKey();
        $key->key = $apiKey;
        $key->is_active = true;
        $key->permissions = ['test.permission'];
        $key->save();

        $this->assertTrue($this->service->validateRequest($this->request, $this->route));

        // Verify caching
        $this->assertNotNull(Cache::get('api_key:' . $apiKey));
    }

    public function test_throws_exception_for_invalid_api_key()
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid API key');

        $apiKey = 'invalid_api_key_123456789012345678901234';
        $this->request->headers->set('Authorization', 'Bearer ' . $apiKey);

        $this->service->validateRequest($this->request, $this->route);
    }

    public function test_validates_jwt_token()
    {
        $payload = [
            'sub' => '123',
            'name' => 'Test User',
            'permissions' => ['test.permission']
        ];

        $token = $this->service->generateJwt($payload);
        $this->request->headers->set('Authorization', 'Bearer ' . $token);

        $this->assertTrue($this->service->validateRequest($this->request, $this->route));
        $this->assertNotNull($this->request->attributes->get('jwt_payload'));
    }

    public function test_throws_exception_for_expired_jwt_token()
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Token has expired');

        $payload = [
            'sub' => '123',
            'name' => 'Test User',
            'exp' => time() - 3600 // Expired 1 hour ago
        ];

        $token = $this->service->generateJwt($payload);
        $this->request->headers->set('Authorization', 'Bearer ' . $token);

        $this->service->validateRequest($this->request, $this->route);
    }

    public function test_validates_oauth_token()
    {
        $this->markTestSkipped('OAuth provider needs to be mocked');

        $token = 'test_oauth_token';
        $this->request->headers->set('Authorization', 'OAuth ' . $token);

        $this->assertTrue($this->service->validateRequest($this->request, $this->route));
        $this->assertNotNull($this->request->attributes->get('oauth_user'));
    }

    public function test_throws_exception_for_invalid_auth_type()
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid authorization header format');

        $this->request->headers->set('Authorization', 'Invalid auth type');

        $this->service->validateRequest($this->request, $this->route);
    }

    public function test_generates_valid_jwt_token()
    {
        $payload = ['test' => 'data'];
        $token = $this->service->generateJwt($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verify token can be decoded
        $decoded = JWT::decode($token, new Key(config('auth.jwt.secret'), config('auth.jwt.algorithm')));
        $this->assertEquals('data', $decoded->test);
    }

    public function test_generates_valid_api_key()
    {
        $apiKey = $this->service->generateApiKey();

        $this->assertIsString($apiKey);
        $this->assertEquals(32, strlen($apiKey));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $apiKey);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 