<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CodespaceService;
use App\Services\DeveloperCredentialService;
use App\Models\DeveloperCredential;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mockery;

class CodespaceServiceTest extends TestCase
{
    protected $service;
    protected $credentialService;
    protected $user;
    protected $credential;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->credentialService = Mockery::mock(DeveloperCredentialService::class);
        $this->service = new CodespaceService($this->credentialService);
        
        // Create test user and credential
        $this->user = User::factory()->create();
        $this->credential = DeveloperCredential::factory()->create([
            'user_id' => $this->user->id,
            'github_token' => encrypt('test-token')
        ]);
        
        Config::set('services.github.token', 'test-token');
        Config::set('codespaces.repository', 'test/repo');
        Config::set('codespaces.branch', 'main');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_codespace()
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'name' => 'test-codespace',
                'state' => 'creating'
            ], 201)
        ]);

        $result = $this->service->create('test-codespace');

        $this->assertEquals('test-codespace', $result['name']);
        $this->assertEquals('creating', $result['state']);
    }

    public function test_it_lists_codespaces()
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'codespaces' => [
                    [
                        'name' => 'test-codespace',
                        'git_status' => ['ref' => 'main'],
                        'state' => 'available',
                        'location' => 'us-east-1'
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->list();

        $this->assertCount(1, $result);
        $this->assertEquals('test-codespace', $result[0]['name']);
        $this->assertEquals('main', $result[0]['branch']);
        $this->assertEquals('available', $result[0]['status']);
        $this->assertEquals('us-east-1', $result[0]['region']);
    }

    public function test_it_deletes_codespace()
    {
        Http::fake([
            'api.github.com/*' => Http::response(null, 204)
        ]);

        $result = $this->service->delete('test-codespace');

        $this->assertTrue($result);
    }

    public function test_it_rebuilds_codespace()
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'name' => 'test-codespace',
                'state' => 'rebuilding'
            ], 200)
        ]);

        $result = $this->service->rebuild('test-codespace');

        $this->assertEquals('test-codespace', $result['name']);
        $this->assertEquals('rebuilding', $result['state']);
    }

    public function test_it_throws_exception_when_no_authenticated_user()
    {
        Auth::shouldReceive('user')->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No authenticated user found');

        $this->service->create('test-codespace');
    }

    public function test_it_throws_exception_when_no_active_credential()
    {
        Auth::shouldReceive('user')->andReturn($this->user);
        $this->credentialService->shouldReceive('getActiveCredential')
            ->with($this->user)
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active GitHub credentials found');

        $this->service->create('test-codespace');
    }

    public function test_it_handles_api_errors()
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'message' => 'API Error'
            ], 400)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create codespace: {"message":"API Error"}');

        $this->service->create('test-codespace');
    }

    public function test_it_handles_network_errors()
    {
        Http::fake([
            'api.github.com/*' => Http::throw(new \Exception('Network Error'))
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create codespace: Network Error');

        $this->service->create('test-codespace');
    }

    public function test_it_handles_invalid_response()
    {
        Http::fake([
            'api.github.com/*' => Http::response('Invalid JSON', 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create codespace: Invalid JSON');

        $this->service->create('test-codespace');
    }

    public function test_it_handles_empty_response()
    {
        Http::fake([
            'api.github.com/*' => Http::response('', 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create codespace: ');

        $this->service->create('test-codespace');
    }

    public function test_it_handles_timeout()
    {
        Http::fake([
            'api.github.com/*' => Http::throw(new \Exception('Connection timed out'))
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create codespace: Connection timed out');

        $this->service->create('test-codespace');
    }
} 