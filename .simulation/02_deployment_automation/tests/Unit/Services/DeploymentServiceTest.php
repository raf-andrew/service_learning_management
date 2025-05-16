<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DeploymentService;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Build;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class DeploymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $deploymentService;
    protected $environment;
    protected $build;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deploymentService = new DeploymentService();

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

        // Create test build
        $this->build = Build::create([
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
    }

    public function test_deploy_creates_deployment()
    {
        $deployment = $this->deploymentService->deploy('test-environment', $this->build);

        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertEquals($this->environment->id, $deployment->environment_id);
        $this->assertEquals($this->build->id, $deployment->build_id);
        $this->assertEquals('success', $deployment->status);
    }

    public function test_deploy_fails_when_environment_not_deployable()
    {
        $this->environment->update(['status' => 'failed']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Environment test-environment is not deployable');

        $this->deploymentService->deploy('test-environment', $this->build);
    }

    public function test_get_deployment_status_returns_correct_status()
    {
        $deployment = $this->deploymentService->deploy('test-environment', $this->build);
        $status = $this->deploymentService->getDeploymentStatus($deployment->id);

        $this->assertEquals('success', $status);
    }

    public function test_rollback_creates_rollback_deployment()
    {
        // Create initial deployment
        $initialDeployment = $this->deploymentService->deploy('test-environment', $this->build);

        // Create new build for failed deployment
        $failedBuild = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'def456',
            'commit_message' => 'Failed commit',
            'status' => 'failed',
            'build_number' => 2,
            'started_at' => now(),
            'completed_at' => now()
        ]);

        // Create failed deployment
        $failedDeployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $failedBuild->id,
            'status' => 'failed',
            'deployed_by' => 'system',
            'deployment_number' => 2,
            'started_at' => now(),
            'completed_at' => now()
        ]);

        // Perform rollback
        $rollbackDeployment = $this->deploymentService->rollback($failedDeployment->id);

        $this->assertInstanceOf(Deployment::class, $rollbackDeployment);
        $this->assertEquals($this->environment->id, $rollbackDeployment->environment_id);
        $this->assertEquals($this->build->id, $rollbackDeployment->build_id);
        $this->assertEquals($initialDeployment->id, $rollbackDeployment->rollback_to);
        $this->assertEquals('success', $rollbackDeployment->status);
    }

    public function test_rollback_fails_when_no_successful_deployment()
    {
        $failedBuild = Build::create([
            'environment_id' => $this->environment->id,
            'branch' => 'develop',
            'commit_hash' => 'def456',
            'commit_message' => 'Failed commit',
            'status' => 'failed',
            'build_number' => 2,
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $failedDeployment = Deployment::create([
            'environment_id' => $this->environment->id,
            'build_id' => $failedBuild->id,
            'status' => 'failed',
            'deployed_by' => 'system',
            'deployment_number' => 1,
            'started_at' => now(),
            'completed_at' => now()
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No successful deployment found to rollback to');

        $this->deploymentService->rollback($failedDeployment->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 