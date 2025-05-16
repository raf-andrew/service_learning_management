<?php

namespace Tests\Feature\Controllers;

use App\Models\Deployment;
use App\Models\Build;
use App\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessDeployment;

class DeploymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $environment;
    protected $build;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        // Create test environment
        $this->environment = Environment::create([
            'name' => 'staging',
            'type' => 'staging',
            'config' => [
                'host' => 'staging.example.com',
                'port' => 22,
                'user' => 'deploy',
                'path' => '/var/www/staging'
            ]
        ]);

        // Create test build
        $this->build = Build::create([
            'version' => '1.0.0',
            'commit_hash' => 'abc123',
            'branch' => 'main',
            'status' => 'completed',
            'artifacts' => [
                'path' => '/builds/1.0.0',
                'files' => ['app.zip', 'config.zip']
            ]
        ]);
    }

    public function test_can_deploy_build()
    {
        $response = $this->postJson('/api/deployments', [
            'environment' => $this->environment->name,
            'build_id' => $this->build->id
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'deployment' => [
                    'id',
                    'environment_id',
                    'build_id',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'pending'
        ]);

        Queue::assertPushed(ProcessDeployment::class);
    }

    public function test_can_get_deployment_status()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'in_progress'
        ]);

        $response = $this->getJson("/api/deployments/{$deployment->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status'
            ]);
    }

    public function test_can_rollback_deployment()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'completed'
        ]);

        $response = $this->postJson("/api/deployments/{$deployment->id}/rollback");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'deployment' => [
                    'id',
                    'environment_id',
                    'build_id',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'rollback_pending'
        ]);

        Queue::assertPushed(ProcessDeployment::class);
    }

    public function test_can_list_deployments()
    {
        // Create multiple deployments
        Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'completed'
        ]);

        Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'failed'
        ]);

        $response = $this->getJson('/api/deployments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'environment_id',
                        'build_id',
                        'status',
                        'created_at',
                        'updated_at',
                        'environment',
                        'build'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    public function test_can_filter_deployments_by_environment()
    {
        // Create deployments for different environments
        $production = Environment::create([
            'name' => 'production',
            'type' => 'production',
            'config' => [
                'host' => 'prod.example.com',
                'port' => 22,
                'user' => 'deploy',
                'path' => '/var/www/production'
            ]
        ]);

        Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'completed'
        ]);

        Deployment::create([
            'environment_id' => $production->id,
            'build_id' => $this->build->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/deployments?environment=staging');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_deployments_by_status()
    {
        Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'completed'
        ]);

        Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'failed'
        ]);

        $response = $this->getJson('/api/deployments?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_validates_deployment_request()
    {
        $response = $this->postJson('/api/deployments', [
            'environment' => 'nonexistent',
            'build_id' => 999
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['environment', 'build_id']);
    }

    public function test_handles_nonexistent_deployment()
    {
        $response = $this->getJson('/api/deployments/999/status');
        $response->assertStatus(404);
    }

    public function test_handles_rollback_of_nonexistent_deployment()
    {
        $response = $this->postJson('/api/deployments/999/rollback');
        $response->assertStatus(404);
    }
} 