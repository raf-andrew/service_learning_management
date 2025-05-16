<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeveloperCredential;
use App\Services\CodespaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;

class CodespaceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $developerCredential;
    protected $codespaceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->developerCredential = DeveloperCredential::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        $this->codespaceService = Mockery::mock(CodespaceService::class);
        $this->app->instance(CodespaceService::class, $this->codespaceService);
    }

    public function test_can_list_codespaces()
    {
        $this->codespaceService
            ->shouldReceive('list')
            ->once()
            ->andReturn([
                [
                    'name' => 'test-codespace',
                    'branch' => 'main',
                    'status' => 'active',
                    'region' => 'us-east-1'
                ]
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/codespaces');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'codespaces',
                    'regions',
                    'machines'
                ]
            ]);
    }

    public function test_can_create_codespace()
    {
        $codespaceData = [
            'name' => 'test-codespace',
            'branch' => 'main',
            'region' => 'us-east-1',
            'machine' => 'basic'
        ];

        $this->codespaceService
            ->shouldReceive('create')
            ->once()
            ->with($codespaceData['name'], $codespaceData['branch'], $codespaceData['region'], $codespaceData['machine'])
            ->andReturn([
                'name' => $codespaceData['name'],
                'status' => 'creating'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/codespaces', $codespaceData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);
    }

    public function test_can_delete_codespace()
    {
        $this->codespaceService
            ->shouldReceive('delete')
            ->once()
            ->with('test-codespace')
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/codespaces/test-codespace');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Codespace test-codespace deleted successfully'
            ]);
    }

    public function test_can_rebuild_codespace()
    {
        $this->codespaceService
            ->shouldReceive('rebuild')
            ->once()
            ->with('test-codespace')
            ->andReturn([
                'name' => 'test-codespace',
                'status' => 'rebuilding'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/codespaces/test-codespace/rebuild');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);
    }

    public function test_can_get_codespace_status()
    {
        $this->codespaceService
            ->shouldReceive('getStatus')
            ->once()
            ->with('test-codespace')
            ->andReturn([
                'name' => 'test-codespace',
                'status' => 'active',
                'last_used' => '2024-01-01T00:00:00Z'
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/codespaces/test-codespace/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);
    }

    public function test_can_connect_to_codespace()
    {
        $this->codespaceService
            ->shouldReceive('connect')
            ->once()
            ->with('test-codespace')
            ->andReturn([
                'name' => 'test-codespace',
                'status' => 'connected',
                'connection_url' => 'https://test-codespace.example.com'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/codespaces/test-codespace/connect');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);
    }

    public function test_requires_developer_credentials()
    {
        $this->developerCredential->update(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/codespaces');

        $response->assertStatus(403);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 