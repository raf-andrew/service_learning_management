<?php

namespace Tests\Feature;

use App\Models\Build;
use App\Models\Deployment;
use App\Models\Environment;
use App\Services\DeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\DeploymentStarted;
use App\Events\DeploymentCompleted;
use App\Events\DeploymentRolledBack;

class DeploymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $deploymentService;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->deploymentService = app(DeploymentService::class);
    }

    public function test_can_deploy_to_environment()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        $response = $this->postJson('/api/deployments', [
            'environment' => $environment->name,
            'build_id' => $build->id
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'deployment' => [
                    'id',
                    'environment_id',
                    'build_id',
                    'status',
                    'created_at'
                ]
            ]);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $environment->id,
            'build_id' => $build->id,
            'status' => 'pending'
        ]);

        Event::assertDispatched(DeploymentStarted::class);
    }

    public function test_can_get_deployment_status()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        $deployment = Deployment::create([
            'environment_id' => $environment->id,
            'build_id' => $build->id,
            'status' => 'in_progress'
        ]);

        $response = $this->getJson("/api/deployments/{$deployment->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status'
            ])
            ->assertJson([
                'status' => 'in_progress'
            ]);
    }

    public function test_can_rollback_deployment()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        $deployment = Deployment::create([
            'environment_id' => $environment->id,
            'build_id' => $build->id,
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
                    'created_at'
                ]
            ]);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $environment->id,
            'build_id' => $build->id,
            'status' => 'rolling_back'
        ]);

        Event::assertDispatched(DeploymentRolledBack::class);
    }

    public function test_can_list_deployments()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        Deployment::create([
            'environment_id' => $environment->id,
            'build_id' => $build->id,
            'status' => 'completed'
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
        $staging = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $production = Environment::create([
            'name' => 'production',
            'description' => 'Production Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        Deployment::create([
            'environment_id' => $staging->id,
            'build_id' => $build->id,
            'status' => 'completed'
        ]);

        Deployment::create([
            'environment_id' => $production->id,
            'build_id' => $build->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/deployments?environment=staging');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_deployments_by_status()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        Deployment::create([
            'environment_id' => $environment->id,
            'build_id' => $build->id,
            'status' => 'completed'
        ]);

        Deployment::create([
            'environment_id' => $environment->id,
            'build_id' => $build->id,
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

        $response->assertStatus(400)
            ->assertJsonStructure([
                'message',
                'error'
            ]);
    }

    public function test_handles_nonexistent_deployment()
    {
        $response = $this->getJson('/api/deployments/999/status');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message',
                'error'
            ]);
    }
} 