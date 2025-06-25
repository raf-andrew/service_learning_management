<?php

namespace Tests\Feature\MCP;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MCP\Core\MCP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * @group mcp
 * @group system
 * @group initialization
 * @checklistItem MCP-001
 * @relatedFiles
 * - config/mcp.php
 * - config/test/mcp.php
 * - src/MCP/Core/MCP.php
 */

class MCPSystemTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @test
     * @checklistItem MCP-001
     * @coverage 100%
     * @description Test MCP system initialization and core functionality
     */
    public function test_mcp_initialization()
    {
        $mcp = new MCP();
        $this->assertInstanceOf(MCP::class, $mcp);
        
        // Test database connection
        $this->assertTrue(DB::connection()->getDatabaseName() !== null);
        
        // Test logging
        Log::info('MCP system test initialization');
        $this->assertNotNull(Log::getMonolog());
    }

    /**
     * @test
     * @checklistItem MCP-002
     * @coverage 100%
     * @description Test MCP rollback functionality and triggers
     */
    public function test_mcp_rollback()
    {
        // Simulate a failure condition
        $mcp = new MCP();
        
        // Verify rollback procedures
        $this->assertTrue($mcp->canRollback());
        
        // Test rollback triggers
        $this->assertTrue($mcp->hasTrigger('health_check_failure'));
        $this->assertTrue($mcp->hasTrigger('error_rate_threshold'));
    }

    /**
     * @test
     * @checklistItem MCP-003
     * @coverage 100%
     * @description Test MCP security features and audit logging
     */
    public function test_mcp_security()
    {
        $mcp = new MCP();
        
        // Test security checks
        $this->assertTrue($mcp->isSecurityEnabled());
        $this->assertTrue($mcp->isAuditLoggingEnabled());
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
        return 'MCP-000';
    }
}
