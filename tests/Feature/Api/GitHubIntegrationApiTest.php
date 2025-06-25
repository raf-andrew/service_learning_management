<?php
/**
 * @fileoverview Feature tests for GitHub Integration API endpoints
 * @tags feature, api, github, integration, laravel, vitest, config, features, repositories
 * @description Tests for /api/github/config, /api/github/features, /api/github/repositories endpoints
 * @coverage api/github/*
 * @since 1.0.0
 * @author System
 */

namespace Tests\Feature\Api;

use Tests\Feature\TestCase;
use App\Models\User;
use App\Models\GitHub\Config;
use App\Models\GitHub\Feature;
use App\Models\GitHub\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GitHubIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     * @group github-config
     */
    public function test_github_config_index_endpoint()
    {
        Sanctum::actingAs($this->user);

        // Create some test configs
        Config::factory()->count(3)->create();

        $response = $this->getJson('/api/github/config');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'key',
                            'value',
                            'group',
                            'is_encrypted',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /**
     * @test
     * @group github-config
     */
    public function test_github_config_store_endpoint()
    {
        Sanctum::actingAs($this->user);

        $configData = [
            'key' => 'test_key',
            'value' => 'test_value',
            'group' => 'github',
            'is_encrypted' => false
        ];

        $response = $this->postJson('/api/github/config', $configData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'key',
                        'value',
                        'group',
                        'is_encrypted'
                    ]
                ]);

        $this->assertDatabaseHas('github_configs', [
            'key' => 'test_key',
            'value' => 'test_value'
        ]);
    }

    /**
     * @test
     * @group github-config
     */
    public function test_github_config_update_endpoint()
    {
        Sanctum::actingAs($this->user);

        $config = Config::factory()->create();

        $updateData = [
            'value' => 'updated_value',
            'is_encrypted' => true
        ];

        $response = $this->putJson("/api/github/config/{$config->key}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'key' => $config->key,
                        'value' => 'updated_value',
                        'is_encrypted' => true
                    ]
                ]);
    }

    /**
     * @test
     * @group github-config
     */
    public function test_github_config_destroy_endpoint()
    {
        Sanctum::actingAs($this->user);

        $config = Config::factory()->create();

        $response = $this->deleteJson("/api/github/config/{$config->key}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Config deleted successfully'
                ]);

        $this->assertDatabaseMissing('github_configs', [
            'id' => $config->id
        ]);
    }

    /**
     * @test
     * @group github-features
     */
    public function test_github_features_index_endpoint()
    {
        Sanctum::actingAs($this->user);

        // Create some test features
        Feature::factory()->count(3)->create();

        $response = $this->getJson('/api/github/features');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'enabled',
                            'conditions',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /**
     * @test
     * @group github-features
     */
    public function test_github_features_store_endpoint()
    {
        Sanctum::actingAs($this->user);

        $featureData = [
            'name' => 'test_feature',
            'description' => 'Test feature description',
            'enabled' => false,
            'conditions' => ['type' => 'environment', 'value' => 'testing']
        ];

        $response = $this->postJson('/api/github/features', $featureData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'enabled',
                        'conditions'
                    ]
                ]);

        $this->assertDatabaseHas('github_features', [
            'name' => 'test_feature',
            'enabled' => false
        ]);
    }

    /**
     * @test
     * @group github-features
     */
    public function test_github_features_update_endpoint()
    {
        Sanctum::actingAs($this->user);

        $feature = Feature::factory()->create();

        $updateData = [
            'description' => 'Updated description',
            'enabled' => true
        ];

        $response = $this->putJson("/api/github/features/{$feature->name}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => $feature->name,
                        'description' => 'Updated description',
                        'enabled' => true
                    ]
                ]);
    }

    /**
     * @test
     * @group github-features
     */
    public function test_github_features_destroy_endpoint()
    {
        Sanctum::actingAs($this->user);

        $feature = Feature::factory()->create();

        $response = $this->deleteJson("/api/github/features/{$feature->name}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Feature deleted successfully'
                ]);

        $this->assertDatabaseMissing('github_features', [
            'id' => $feature->id
        ]);
    }

    /**
     * @test
     * @group github-features
     */
    public function test_github_features_toggle_endpoint()
    {
        Sanctum::actingAs($this->user);

        $feature = Feature::factory()->create(['enabled' => false]);

        $response = $this->postJson("/api/github/features/{$feature->name}/toggle");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => $feature->name,
                        'enabled' => true
                    ]
                ]);

        $this->assertDatabaseHas('github_features', [
            'id' => $feature->id,
            'enabled' => true
        ]);
    }

    /**
     * @test
     * @group github-repositories
     */
    public function test_github_repositories_index_endpoint()
    {
        Sanctum::actingAs($this->user);

        // Create some test repositories
        Repository::factory()->count(3)->create();

        $response = $this->getJson('/api/github/repositories');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'full_name',
                            'name',
                            'description',
                            'private',
                            'settings',
                            'permissions',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /**
     * @test
     * @group github-repositories
     */
    public function test_github_repositories_store_endpoint()
    {
        Sanctum::actingAs($this->user);

        $repoData = [
            'full_name' => 'test/repo',
            'name' => 'repo',
            'description' => 'Test repository',
            'private' => false,
            'settings' => ['auto_merge' => true],
            'permissions' => ['admin' => true, 'push' => true, 'pull' => true]
        ];

        $response = $this->postJson('/api/github/repositories', $repoData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'full_name',
                        'name',
                        'description',
                        'private',
                        'settings',
                        'permissions'
                    ]
                ]);

        $this->assertDatabaseHas('github_repositories', [
            'full_name' => 'test/repo',
            'name' => 'repo'
        ]);
    }

    /**
     * @test
     * @group github-repositories
     */
    public function test_github_repositories_update_endpoint()
    {
        Sanctum::actingAs($this->user);

        $repo = Repository::factory()->create();

        $updateData = [
            'description' => 'Updated description',
            'private' => true
        ];

        $response = $this->putJson("/api/github/repositories/{$repo->name}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => $repo->name,
                        'description' => 'Updated description',
                        'private' => true
                    ]
                ]);
    }

    /**
     * @test
     * @group github-repositories
     */
    public function test_github_repositories_destroy_endpoint()
    {
        Sanctum::actingAs($this->user);

        $repo = Repository::factory()->create();

        $response = $this->deleteJson("/api/github/repositories/{$repo->name}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Repository deleted successfully'
                ]);

        $this->assertDatabaseMissing('github_repositories', [
            'id' => $repo->id
        ]);
    }

    /**
     * @test
     * @group github-repositories
     */
    public function test_github_repositories_sync_endpoint()
    {
        Sanctum::actingAs($this->user);

        $repo = Repository::factory()->create();

        $response = $this->postJson("/api/github/repositories/{$repo->name}/sync");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Repository synced successfully'
                ]);
    }

    /**
     * @test
     * @group github-auth
     */
    public function test_github_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/github/config');
        $response->assertStatus(401);

        $response = $this->getJson('/api/github/features');
        $response->assertStatus(401);

        $response = $this->getJson('/api/github/repositories');
        $response->assertStatus(401);
    }

    /**
     * @test
     * @group github-validation
     */
    public function test_github_config_store_validates_input()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/github/config', [
            'key' => '',
            'value' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['key', 'value']);
    }

    /**
     * @test
     * @group github-validation
     */
    public function test_github_features_store_validates_input()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/github/features', [
            'name' => '',
            'description' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /**
     * @test
     * @group github-validation
     */
    public function test_github_repositories_store_validates_input()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/github/repositories', [
            'full_name' => '',
            'name' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['full_name', 'name']);
    }

    /**
     * @test
     * @group github-error-handling
     */
    public function test_github_config_update_handles_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/github/config/non-existent-key', [
            'value' => 'test'
        ]);

        $response->assertStatus(404);
    }

    /**
     * @test
     * @group github-error-handling
     */
    public function test_github_features_update_handles_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/github/features/non-existent-feature', [
            'description' => 'test'
        ]);

        $response->assertStatus(404);
    }

    /**
     * @test
     * @group github-error-handling
     */
    public function test_github_repositories_update_handles_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/github/repositories/non-existent-repo', [
            'description' => 'test'
        ]);

        $response->assertStatus(404);
    }
} 