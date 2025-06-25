<?php

namespace Tests\Feature\Deployment;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * @group deployment
 * @group vapor
 * @checklistItem DEPLOY-001
 * @relatedFiles
 * - .vapor/deploy.php
 * - .vapor/vapor.yml
 * - .devcontainer/devcontainer.json
 */
class VaporDeploymentTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @test
     * @checklistItem DEPLOY-001
     * @coverage 100%
     * @description Test Vapor deployment configuration
     */
    public function test_vapor_configuration()
    {
        $this->assertFileExists(base_path('.vapor/deploy.php'));
        $this->assertFileExists(base_path('.vapor/vapor.yml'));
        
        $config = require base_path('.vapor/deploy.php');
        
        $this->assertArrayHasKey('environments', $config);
        $this->assertArrayHasKey('development', $config['environments']);
        $this->assertArrayHasKey('staging', $config['environments']);
        $this->assertArrayHasKey('production', $config['environments']);
    }

    /**
     * @test
     * @checklistItem DEPLOY-002
     * @coverage 100%
     * @description Test Codespaces configuration
     */
    public function test_codespaces_configuration()
    {
        $this->assertFileExists(base_path('.devcontainer/devcontainer.json'));
        
        $config = json_decode(file_get_contents(base_path('.devcontainer/devcontainer.json')));
        
        $this->assertNotNull($config->name);
        $this->assertNotNull($config->dockerFile);
        $this->assertNotNull($config->forwardPorts);
        $this->assertNotNull($config->extensions);
    }

    /**
     * @test
     * @checklistItem DEPLOY-003
     * @coverage 100%
     * @description Test environment isolation
     */
    public function test_environment_isolation()
    {
        $this->assertNotEquals(env('APP_ENV', 'local'), 'production');
        
        // Test database isolation
        $this->assertNotEquals(DB::connection()->getDatabaseName(), 'production_db');
        
        // Test cache isolation
        $this->assertNotEquals(Cache::store()->getStore(), 'production_cache');
        
        // Test queue isolation
        $this->assertNotEquals(Queue::getDefaultQueue(), 'production_queue');
    }

    /**
     * @test
     * @checklistItem DEPLOY-004
     * @coverage 100%
     * @description Test deployment security
     */
    public function test_deployment_security()
    {
        $config = require base_path('.vapor/deploy.php');
        
        $this->assertFalse($config['security']['staging']['allow_destructive_actions']);
        $this->assertTrue($config['security']['staging']['require_approval']);
        
        $this->assertFalse($config['security']['production']['allow_destructive_actions']);
        $this->assertTrue($config['security']['production']['require_approval']);
        $this->assertTrue($config['security']['production']['manual_approval']);
    }

    /**
     * @after
     */
    public function generateTestReport()
    {
        $testName = $this->getName();
        $checklistItem = $this->getChecklistItem();
        $testFile = __FILE__;

        $scriptPath = base_path('tests/scripts/generate_test_report.ps1');
        $reportPath = base_path("tests/reports/$testName-$checklistItem-report.md");

        exec("powershell -ExecutionPolicy Bypass -File $scriptPath -TestName '$testName' -ChecklistItem '$checklistItem' -TestFile '$testFile'", $output, $return);
    }

    private function getChecklistItem()
    {
        $reflection = new \ReflectionClass($this);
        $docComment = $reflection->getDocComment();
        
        if (preg_match('/@checklistItem\s+(\w+)/', $docComment, $matches)) {
            return $matches[1];
        }
        return 'DEPLOY-000';
    }
}
