<?php

namespace Tests\Feature\Api;

use Tests\Feature\BaseRouteTest;
use App\Models\User;
use App\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class RoutesTest extends BaseRouteTest
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function shouldTestRoute($route): bool
    {
        return str_starts_with($route->uri(), 'api/');
    }

    public function test_api_status_route()
    {
        $response = $this->get('/api/status');

        $this->recordTestResult('/api/status', 'GET', $response);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'version',
                    'environment',
                ]);
    }

    public function test_api_health_check_route()
    {
        $response = $this->get('/api/health');

        $this->recordTestResult('/api/health', 'GET', $response);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'services' => [
                        'database',
                        'redis',
                        'mail',
                        'queue',
                        'storage',
                    ],
                ]);
    }

    public function test_api_metrics_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get('/api/metrics');

        $this->recordTestResult('/api/metrics', 'GET', $response, [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'system' => [
                        'cpu_usage',
                        'memory_usage',
                        'disk_usage',
                    ],
                    'application' => [
                        'requests_per_minute',
                        'average_response_time',
                        'error_rate',
                    ],
                ]);
    }

    public function test_api_logs_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get('/api/logs');

        $this->recordTestResult('/api/logs', 'GET', $response, [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'level',
                            'message',
                            'context',
                            'timestamp',
                        ],
                    ],
                ]);
    }

    public function test_api_config_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get('/api/config');

        $this->recordTestResult('/api/config', 'GET', $response, [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'app' => [
                        'name',
                        'env',
                        'debug',
                        'url',
                    ],
                    'services' => [
                        'database',
                        'redis',
                        'mail',
                        'queue',
                        'storage',
                    ],
                ]);
    }

    public function test_route_coverage()
    {
        $this->assertRouteCoverage();
    }

    public function test_api_authentication_requires_token()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_api_authentication_with_valid_token()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'email',
                ]);
    }

    public function test_api_login_route_returns_token()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ]);
    }

    public function test_api_login_route_validates_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_api_logout_route_revokes_token()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/logout');
        $response->assertStatus(200);

        // Verify token is revoked
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_api_refresh_token_route()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/refresh');
        $response->assertStatus(200)
                ->assertJsonStructure(['token']);
    }

    public function test_api_user_profile_route()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/profile');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'email',
                    'profile' => [
                        'bio',
                        'avatar',
                        'preferences',
                    ],
                ]);
    }

    public function test_api_update_profile_route()
    {
        Sanctum::actingAs($this->user);

        $data = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ];

        $response = $this->putJson('/api/profile', $data);
        $response->assertStatus(200)
                ->assertJson([
                    'name' => 'Updated Name',
                    'profile' => [
                        'bio' => 'Updated bio',
                    ],
                ]);
    }

    public function test_api_user_settings_route()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/settings');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'notifications',
                    'appearance',
                    'privacy',
                ]);
    }

    public function test_api_update_settings_route()
    {
        Sanctum::actingAs($this->user);

        $settings = [
            'notifications' => [
                'email' => true,
                'push' => false,
            ],
            'appearance' => [
                'theme' => 'dark',
            ],
        ];

        $response = $this->putJson('/api/settings', $settings);
        $response->assertStatus(200)
                ->assertJson($settings);
    }

    public function test_api_user_activity_route()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/activity');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'description',
                            'created_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'total',
                    ],
                ]);
    }

    public function test_api_rate_limiting()
    {
        Sanctum::actingAs($this->user);

        // Make multiple requests in quick succession
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/user');
        }

        // The 61st request should be rate limited
        $response = $this->getJson('/api/user');
        $response->assertStatus(429);
    }

    public function test_api_validation_errors()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/profile', [
            'email' => 'invalid-email',
            'name' => '',
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'email',
                        'name',
                    ],
                ]);
    }

    public function test_api_not_found_handling()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/non-existent-route');
        $response->assertStatus(404)
                ->assertJsonStructure([
                    'message',
                    'error',
                ]);
    }

    public function test_api_method_not_allowed_handling()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/user');
        $response->assertStatus(405)
                ->assertJsonStructure([
                    'message',
                    'error',
                ]);
    }
} 