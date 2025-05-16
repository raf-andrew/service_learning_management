<?php

namespace Tests\Feature\Services;

use App\Models\Deployment;
use App\Models\Build;
use App\Models\Environment;
use App\Services\DeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessDeployment;
use App\Events\DeploymentStarted;
use App\Events\DeploymentCompleted;
use App\Events\DeploymentFailed;
use App\Events\DeploymentRolledBack;
use Illuminate\Support\Facades\Event;

class DeploymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $deploymentService;
    protected $environment;
    protected $build;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Event::fake();
        $this->deploymentService = app(DeploymentService::class);

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

        $deployment = $this->deploymentService->deploy($environment->name, $build);

        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertEquals($environment->id, $deployment->environment_id);
        $this->assertEquals($build->id, $deployment->build_id);
        $this->assertEquals('pending', $deployment->status);

        Event::assertDispatched(DeploymentStarted::class);
    }

    public function test_can_start_deployment()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'pending'
        ]);

        $this->deploymentService->startDeployment($deployment->id);

        $deployment->refresh();
        $this->assertEquals('in_progress', $deployment->status);
    }

    public function test_can_complete_deployment()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'in_progress'
        ]);

        $this->deploymentService->completeDeployment($deployment->id);

        $deployment->refresh();
        $this->assertEquals('completed', $deployment->status);
    }

    public function test_can_fail_deployment()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'in_progress'
        ]);

        $error = 'Deployment failed due to timeout';
        $this->deploymentService->failDeployment($deployment->id, $error);

        $deployment->refresh();
        $this->assertEquals('failed', $deployment->status);
        $this->assertEquals($error, $deployment->error);
    }

    public function test_can_rollback_deployment()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'failed'
        ]);

        $rollbackDeployment = $this->deploymentService->rollbackDeployment($deployment->id);

        $this->assertInstanceOf(Deployment::class, $rollbackDeployment);
        $this->assertEquals($this->environment->id, $rollbackDeployment->environment_id);
        $this->assertEquals($this->build->id, $rollbackDeployment->build_id);
        $this->assertEquals('rollback_pending', $rollbackDeployment->status);

        Queue::assertPushed(ProcessDeployment::class);
    }

    public function test_can_get_deployment_status()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'in_progress'
        ]);

        $status = $this->deploymentService->getDeploymentStatus($deployment->id);

        $this->assertEquals('in_progress', $status);
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

        $deployments = $this->deploymentService->listDeployments();

        $this->assertCount(2, $deployments);
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

        $deployments = $this->deploymentService->listDeployments([
            'environment' => 'staging'
        ]);

        $this->assertCount(1, $deployments);
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

        $deployments = $this->deploymentService->listDeployments([
            'status' => 'completed'
        ]);

        $this->assertCount(1, $deployments);
    }

    public function test_throws_exception_for_nonexistent_deployment()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->deploymentService->getDeploymentStatus(999);
    }

    public function test_throws_exception_for_invalid_rollback()
    {
        $deployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $this->build->id,
            'status' => 'in_progress'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->deploymentService->rollbackDeployment($deployment->id);
    }

    public function test_validates_environment_status()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'inactive'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'completed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->deploymentService->deploy($environment->name, $build);
    }

    public function test_validates_build_status()
    {
        $environment = Environment::create([
            'name' => 'staging',
            'description' => 'Staging Environment',
            'status' => 'active'
        ]);

        $build = Build::create([
            'version' => '1.0.0',
            'status' => 'failed',
            'artifacts' => ['path' => '/builds/1.0.0'],
            'metadata' => ['commit' => 'abc123']
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->deploymentService->deploy($environment->name, $build);
    }

    public function test_prevents_rollback_of_failed_deployment()
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
            'status' => 'failed'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->deploymentService->rollbackDeployment($deployment->id);
    }

    public function test_prevents_rollback_of_pending_deployment()
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
            'status' => 'pending'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->deploymentService->rollbackDeployment($deployment->id);
    }
} 