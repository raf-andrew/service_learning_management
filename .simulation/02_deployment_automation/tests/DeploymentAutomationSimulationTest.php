<?php

namespace Tests\Simulations\DeploymentAutomation;

use Tests\TestCase;
use App\Simulations\DeploymentAutomation\DeploymentAutomationSimulation;
use App\Services\DeploymentService;
use App\Services\EnvironmentService;
use App\Services\BuildService;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Build;
use Illuminate\Support\Facades\Log;
use Mockery;

class DeploymentAutomationSimulationTest extends TestCase
{
    protected $deploymentService;
    protected $environmentService;
    protected $buildService;
    protected $simulation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->deploymentService = Mockery::mock(DeploymentService::class);
        $this->environmentService = Mockery::mock(EnvironmentService::class);
        $this->buildService = Mockery::mock(BuildService::class);

        // Create simulation instance
        $this->simulation = new DeploymentAutomationSimulation(
            $this->deploymentService,
            $this->environmentService,
            $this->buildService
        );
    }

    public function test_initialization()
    {
        $this->simulation->initialize();
        $results = $this->simulation->getResults();

        $this->assertArrayHasKey('development', $results['environments']);
        $this->assertArrayHasKey('staging', $results['environments']);
        $this->assertArrayHasKey('production', $results['environments']);
    }

    public function test_environment_setup()
    {
        $envName = 'development';
        $config = [
            'name' => 'Development',
            'branch' => 'develop',
            'url' => 'http://dev.example.com',
            'variables' => [
                'APP_ENV' => 'development',
                'APP_DEBUG' => 'true'
            ]
        ];

        $environment = new Environment();
        $environment->id = 1;

        $this->environmentService
            ->shouldReceive('createEnvironment')
            ->with($envName, $config)
            ->andReturn($environment);

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertEquals(1, $results['environments'][$envName]['id']);
    }

    public function test_deployment_process()
    {
        $envName = 'development';
        $build = new Build();
        $build->id = 1;
        $deployment = new Deployment();
        $deployment->id = 1;

        $this->buildService
            ->shouldReceive('createBuild')
            ->with('develop')
            ->andReturn($build);

        $this->deploymentService
            ->shouldReceive('deploy')
            ->with($envName, $build)
            ->andReturn($deployment);

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertEquals($build, $results['builds'][$envName]);
        $this->assertEquals($deployment, $results['deployments'][$envName]);
    }

    public function test_deployment_monitoring()
    {
        $envName = 'development';
        $deployment = new Deployment();
        $deployment->id = 1;

        $this->deploymentService
            ->shouldReceive('getDeploymentStatus')
            ->with($deployment->id)
            ->andReturn('success');

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertArrayHasKey($envName, $results['deployments']);
    }

    public function test_failed_deployment_rollback()
    {
        $envName = 'development';
        $deployment = new Deployment();
        $deployment->id = 1;
        $rollback = new Deployment();
        $rollback->id = 2;

        $this->deploymentService
            ->shouldReceive('getDeploymentStatus')
            ->with($deployment->id)
            ->andReturn('failed');

        $this->deploymentService
            ->shouldReceive('rollback')
            ->with($deployment->id)
            ->andReturn($rollback);

        $this->simulation->initialize();
        $this->simulation->run();
        $results = $this->simulation->getResults();

        $this->assertArrayHasKey($envName, $results['deployments']);
    }

    public function test_error_handling()
    {
        $this->environmentService
            ->shouldReceive('createEnvironment')
            ->andThrow(new \Exception('Environment setup failed'));

        $this->simulation->initialize();
        $result = $this->simulation->run();

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 