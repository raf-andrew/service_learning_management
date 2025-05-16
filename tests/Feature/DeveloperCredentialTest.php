<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeveloperCredential;
use App\Services\DeveloperCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Foundation\Testing\WithFaker;

class DeveloperCredentialTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $credentialService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialService = $this->app->make(DeveloperCredentialService::class);
        $this->user = User::factory()->create();
    }

    public function test_can_list_developer_credentials()
    {
        DeveloperCredential::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/developer-credentials');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'github_username',
                        'is_active',
                        'last_used_at',
                        'expires_at',
                        'permissions'
                    ]
                ]
            ]);
    }

    public function test_can_create_developer_credential()
    {
        $credentialData = [
            'github_token' => $this->faker->uuid,
            'github_username' => $this->faker->userName,
            'permissions' => [
                'codespaces' => true,
                'repositories' => true,
                'workflows' => true
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/developer-credentials', $credentialData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'github_username',
                    'is_active',
                    'permissions'
                ]
            ]);

        $this->assertDatabaseHas('developer_credentials', [
            'user_id' => $this->user->id,
            'github_username' => $credentialData['github_username']
        ]);
    }

    public function test_can_update_developer_credential()
    {
        $credential = DeveloperCredential::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'github_username' => $this->faker->userName,
            'permissions' => [
                'codespaces' => false,
                'repositories' => true,
                'workflows' => false
            ]
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/developer-credentials/{$credential->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'github_username',
                    'permissions'
                ]
            ]);

        $this->assertDatabaseHas('developer_credentials', [
            'id' => $credential->id,
            'github_username' => $updateData['github_username']
        ]);
    }

    public function test_can_delete_developer_credential()
    {
        $credential = DeveloperCredential::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/developer-credentials/{$credential->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Developer credential deleted successfully'
            ]);

        $this->assertSoftDeleted('developer_credentials', [
            'id' => $credential->id
        ]);
    }

    public function test_can_activate_developer_credential()
    {
        $credential = DeveloperCredential::factory()->inactive()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/developer-credentials/{$credential->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => true
                ]
            ]);
    }

    public function test_can_deactivate_developer_credential()
    {
        $credential = DeveloperCredential::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/developer-credentials/{$credential->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => false
                ]
            ]);
    }

    public function test_cannot_access_expired_credential()
    {
        $credential = DeveloperCredential::factory()->expired()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/codespaces');

        $response->assertStatus(403);
    }

    public function test_cannot_access_with_insufficient_permissions()
    {
        $credential = DeveloperCredential::factory()->withLimitedPermissions()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/codespaces');

        $response->assertStatus(403);
    }

    public function test_token_validation()
    {
        $validToken = 'valid_token';
        $invalidToken = 'invalid_token';

        // Mock the GitHub CLI response for valid token
        $this->mockProcess('gh auth status --token ' . $validToken, true);
        
        // Mock the GitHub CLI response for invalid token
        $this->mockProcess('gh auth status --token ' . $invalidToken, false);

        $this->assertTrue($this->credentialService->validateToken($validToken));
        $this->assertFalse($this->credentialService->validateToken($invalidToken));
    }

    private function mockProcess($command, $success)
    {
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('run')->once();
        $process->shouldReceive('isSuccessful')->andReturn($success);
        
        Process::shouldReceive('fromShellCommandline')
            ->with($command)
            ->andReturn($process);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 