<?php

namespace Tests\MCP\Infrastructure;

use Tests\MCP\BaseTestCase;
use Symfony\Component\Yaml\Yaml;

class DeploymentPipelineTest extends BaseTestCase
{
    protected string $workflowFile;
    protected array $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflowFile = base_path('.github/workflows/deploy.yml');
        $this->workflow = Yaml::parseFile($this->workflowFile);
    }

    public function test_workflow_file_exists(): void
    {
        $this->assertFileExists($this->workflowFile);
    }

    public function test_workflow_has_required_jobs(): void
    {
        $jobs = array_keys($this->workflow['jobs']);
        $this->assertContains('test', $jobs);
        $this->assertContains('security', $jobs);
        $this->assertContains('deploy-staging', $jobs);
        $this->assertContains('deploy-production', $jobs);
    }

    public function test_test_job_has_required_services(): void
    {
        $services = array_keys($this->workflow['jobs']['test']['services']);
        $this->assertContains('mysql', $services);
        $this->assertContains('redis', $services);
    }

    public function test_test_job_has_required_steps(): void
    {
        $steps = array_column($this->workflow['jobs']['test']['steps'], 'name');
        $this->assertContains('Setup PHP', $steps);
        $this->assertContains('Install dependencies', $steps);
        $this->assertContains('Run tests', $steps);
        $this->assertContains('Check test coverage', $steps);
    }

    public function test_security_job_has_required_steps(): void
    {
        $steps = array_column($this->workflow['jobs']['security']['steps'], 'name');
        $this->assertContains('Setup PHP', $steps);
        $this->assertContains('Install dependencies', $steps);
        $this->assertContains('Run security scan', $steps);
    }

    public function test_deploy_staging_job_has_required_steps(): void
    {
        $steps = array_column($this->workflow['jobs']['deploy-staging']['steps'], 'name');
        $this->assertContains('Setup PHP', $steps);
        $this->assertContains('Install dependencies', $steps);
        $this->assertContains('Deploy to staging', $steps);
        $this->assertContains('Run post-deployment tests', $steps);
    }

    public function test_deploy_production_job_has_required_steps(): void
    {
        $steps = array_column($this->workflow['jobs']['deploy-production']['steps'], 'name');
        $this->assertContains('Setup PHP', $steps);
        $this->assertContains('Install dependencies', $steps);
        $this->assertContains('Deploy to production', $steps);
        $this->assertContains('Run post-deployment tests', $steps);
        $this->assertContains('Notify deployment status', $steps);
    }

    public function test_deploy_staging_requires_test_and_security(): void
    {
        $needs = $this->workflow['jobs']['deploy-staging']['needs'];
        $this->assertContains('test', $needs);
        $this->assertContains('security', $needs);
    }

    public function test_deploy_production_requires_test_and_security(): void
    {
        $needs = $this->workflow['jobs']['deploy-production']['needs'];
        $this->assertContains('test', $needs);
        $this->assertContains('security', $needs);
    }

    public function test_deploy_staging_only_runs_on_staging_branch(): void
    {
        $condition = $this->workflow['jobs']['deploy-staging']['if'];
        $this->assertEquals("github.ref == 'refs/heads/staging'", $condition);
    }

    public function test_deploy_production_only_runs_on_main_branch(): void
    {
        $condition = $this->workflow['jobs']['deploy-production']['if'];
        $this->assertEquals("github.ref == 'refs/heads/main'", $condition);
    }

    public function test_deploy_staging_has_staging_environment(): void
    {
        $environment = $this->workflow['jobs']['deploy-staging']['environment'];
        $this->assertEquals('staging', $environment);
    }

    public function test_deploy_production_has_production_environment(): void
    {
        $environment = $this->workflow['jobs']['deploy-production']['environment'];
        $this->assertEquals('production', $environment);
    }

    public function test_test_job_has_coverage_check(): void
    {
        $steps = $this->workflow['jobs']['test']['steps'];
        $coverageStep = array_filter($steps, function($step) {
            return $step['name'] === 'Check test coverage';
        });
        $this->assertNotEmpty($coverageStep);
    }

    public function test_security_job_has_required_tools(): void
    {
        $setupPhpStep = array_filter($this->workflow['jobs']['security']['steps'], function($step) {
            return $step['name'] === 'Setup PHP';
        });
        $this->assertNotEmpty($setupPhpStep);
        $this->assertArrayHasKey('tools', reset($setupPhpStep)['with']);
    }
} 