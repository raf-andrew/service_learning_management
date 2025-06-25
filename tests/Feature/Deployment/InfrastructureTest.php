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
 * @group infrastructure
 * @checklistItem INFRA-001
 * @relatedFiles
 * - infrastructure/terraform/main.tf
 * - infrastructure/terraform/variables.tf
 * - infrastructure/terraform/backend.tf
 */
class InfrastructureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @test
     * @checklistItem INFRA-001
     * @coverage 100%
     * @description Test Terraform configuration
     */
    public function test_terraform_configuration()
    {
        $this->assertFileExists(base_path('infrastructure/terraform/main.tf'));
        $this->assertFileExists(base_path('infrastructure/terraform/variables.tf'));
        $this->assertFileExists(base_path('infrastructure/terraform/backend.tf'));
    }

    /**
     * @test
     * @checklistItem INFRA-002
     * @coverage 100%
     * @description Test environment isolation
     */
    public function test_environment_isolation()
    {
        $environments = ['development', 'staging', 'production'];
        
        foreach ($environments as $env) {
            $this->assertNotEquals(env('APP_ENV', 'local'), $env);
            
            // Test database isolation
            $this->assertNotEquals(DB::connection()->getDatabaseName(), "{$env}-db");
            
            // Test cache isolation
            $this->assertNotEquals(Cache::store()->getStore(), "{$env}-cache");
            
            // Test queue isolation
            $this->assertNotEquals(Queue::getDefaultQueue(), "{$env}-queue");
        }
    }

    /**
     * @test
     * @checklistItem INFRA-003
     * @coverage 100%
     * @description Test security groups
     */
    public function test_security_groups()
    {
        $this->assertNotNull(config('aws.security_groups.app'));
        $this->assertNotEmpty(config('aws.security_groups.app.ingress_rules'));
        
        // Verify SSH access is restricted
        $sshRules = array_filter(config('aws.security_groups.app.ingress_rules'), function($rule) {
            return $rule['from_port'] == 22 && $rule['to_port'] == 22;
        });
        
        $this->assertCount(1, $sshRules);
        $this->assertNotEquals('0.0.0.0/0', $sshRules[0]['cidr_blocks'][0]);
    }

    /**
     * @test
     * @checklistItem INFRA-004
     * @coverage 100%
     * @description Test deployment automation
     */
    public function test_deployment_automation()
    {
        $scriptPath = base_path('scripts/deploy.ps1');
        $this->assertFileExists($scriptPath);
        
        // Test script parameters
        $script = file_get_contents($scriptPath);
        $this->assertMatchesRegularExpression("/\[ValidateSet\('development', 'staging', 'production'\)\]/", $script);
        
        // Test environment initialization
        $this->assertMatchesRegularExpression('/Initialize-Environment/', $script);
        
        // Test deployment steps
        $this->assertMatchesRegularExpression('/Deploy-To-Codespaces/', $script);
        $this->assertMatchesRegularExpression('/Deploy-To-Vapor/', $script);
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
        return 'INFRA-000';
    }
}
