<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Environment;
use App\Models\Build;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BuildControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $environment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test environment
        $this->environment = Environment::create([
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ],
            'status' => 'ready'
        ]);
    }

    public function test_create_build()
    {
        $response = $this->postJson('/api/builds', [
            'branch' => 'develop'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'build' => [
                    'id',
                    'branch',
                    'commit_hash',
                    'commit_message',
                    'status',
                    'build_number',
                    'artifacts',
                    'started_at'
                ]
            ]);

        $this->assertDatabaseHas('builds', [
            'branch' => 'develop',
            'status' => 'success'
        ]);
    }

    public function test_get_build_status()
    {
        $build = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [
                'app' => 'app.zip',
                'vendor' => 'vendor.zip',
                'public' => 'public.zip'
            ],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $response = $this->getJson("/api/builds/{$build->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_validate_build()
    {
        $build = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [
                'app' => 'app.zip',
                'vendor' => 'vendor.zip',
                'public' => 'public.zip'
            ],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $response = $this->postJson("/api/builds/{$build->id}/validate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'valid'
            ]);
    }

    public function test_validate_build_fails_with_missing_artifacts()
    {
        $build = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [], // Empty artifacts
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $response = $this->postJson("/api/builds/{$build->id}/validate");

        $response->assertStatus(400)
            ->assertJsonStructure([
                'message',
                'error'
            ]);
    }

    public function test_list_builds()
    {
        // Create multiple builds
        Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit 1',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [
                'app' => 'app.zip',
                'vendor' => 'vendor.zip',
                'public' => 'public.zip'
            ],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'def456',
            'commit_message' => 'Test commit 2',
            'status' => 'failed',
            'build_number' => 2,
            'artifacts' => [],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $response = $this->getJson('/api/builds');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'environment_id',
                        'branch',
                        'commit_hash',
                        'commit_message',
                        'status',
                        'build_number',
                        'artifacts',
                        'started_at',
                        'completed_at',
                        'environment'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    public function test_list_builds_with_filters()
    {
        // Create builds with different statuses
        Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit 1',
            'status' => 'success',
            'build_number' => 1,
            'artifacts' => [
                'app' => 'app.zip',
                'vendor' => 'vendor.zip',
                'public' => 'public.zip'
            ],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'main',
            'commit_hash' => 'def456',
            'commit_message' => 'Test commit 2',
            'status' => 'failed',
            'build_number' => 2,
            'artifacts' => [],
            'started_at' => now(),
            'completed_at' => now()
        ]);

        // Test filtering by branch
        $response = $this->getJson('/api/builds?branch=develop');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.branch', 'develop');

        // Test filtering by status
        $response = $this->getJson('/api/builds?status=failed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'failed');
    }
} 