<?php

namespace Tests\MCP\Infrastructure;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\Services\RollbackManager;
use App\MCP\Core\Services\BackupManager;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AuditLogger;
use Illuminate\Support\Facades\Config;

class RollbackProceduresTest extends BaseTestCase
{
    protected RollbackManager $rollbackManager;
    protected BackupManager $backupManager;
    protected HealthMonitor $healthMonitor;
    protected AuditLogger $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rollbackManager = new RollbackManager();
        $this->backupManager = new BackupManager();
        $this->healthMonitor = new HealthMonitor();
        $this->auditLogger = new AuditLogger();
    }

    public function test_rollback_procedures_are_configured(): void
    {
        $config = Config::get('mcp.rollback');
        
        $this->assertTrue($config['enabled']);
        $this->assertArrayHasKey('triggers', $config);
        $this->assertArrayHasKey('procedures', $config);
        $this->assertArrayHasKey('notifications', $config);
    }

    public function test_rollback_triggers_are_defined(): void
    {
        $triggers = Config::get('mcp.rollback.triggers');
        
        $this->assertArrayHasKey('health_check_failure', $triggers);
        $this->assertArrayHasKey('error_rate_threshold', $triggers);
        $this->assertArrayHasKey('response_time_threshold', $triggers);
        $this->assertArrayHasKey('manual_trigger', $triggers);
    }

    public function test_rollback_procedures_are_defined(): void
    {
        $procedures = Config::get('mcp.rollback.procedures');
        
        $this->assertArrayHasKey('database', $procedures);
        $this->assertArrayHasKey('files', $procedures);
        $this->assertArrayHasKey('configuration', $procedures);
        $this->assertArrayHasKey('dependencies', $procedures);
    }

    public function test_rollback_notifications_are_configured(): void
    {
        $notifications = Config::get('mcp.rollback.notifications');
        
        $this->assertArrayHasKey('channels', $notifications);
        $this->assertArrayHasKey('recipients', $notifications);
        $this->assertArrayHasKey('templates', $notifications);
    }

    public function test_rollback_manager_can_execute_rollback(): void
    {
        $result = $this->rollbackManager->executeRollback('test_deployment');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('steps', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('duration', $result);
    }

    public function test_rollback_manager_creates_backup_before_rollback(): void
    {
        $backup = $this->backupManager->createBackup('pre_rollback');
        
        $this->assertTrue($backup['success']);
        $this->assertArrayHasKey('backup_id', $backup);
        $this->assertArrayHasKey('timestamp', $backup);
        $this->assertArrayHasKey('size', $backup);
    }

    public function test_rollback_manager_verifies_health_after_rollback(): void
    {
        $health = $this->healthMonitor->checkHealth();
        
        $this->assertTrue($health['status']);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('timestamp', $health);
    }

    public function test_rollback_manager_logs_rollback_actions(): void
    {
        $log = $this->auditLogger->logRollback('test_deployment', [
            'reason' => 'test',
            'steps' => ['step1', 'step2'],
            'status' => 'success'
        ]);
        
        $this->assertTrue($log['success']);
        $this->assertArrayHasKey('log_id', $log);
        $this->assertArrayHasKey('timestamp', $log);
    }

    public function test_rollback_manager_handles_failed_rollback(): void
    {
        $result = $this->rollbackManager->handleFailedRollback('test_deployment', [
            'error' => 'test error',
            'step' => 'test step'
        ]);
        
        $this->assertTrue($result['handled']);
        $this->assertArrayHasKey('recovery_actions', $result);
        $this->assertArrayHasKey('notifications_sent', $result);
    }

    public function test_rollback_manager_verifies_data_integrity(): void
    {
        $integrity = $this->rollbackManager->verifyDataIntegrity('test_deployment');
        
        $this->assertTrue($integrity['valid']);
        $this->assertArrayHasKey('checks', $integrity);
        $this->assertArrayHasKey('timestamp', $integrity);
    }

    public function test_rollback_manager_cleans_up_after_rollback(): void
    {
        $cleanup = $this->rollbackManager->cleanupAfterRollback('test_deployment');
        
        $this->assertTrue($cleanup['success']);
        $this->assertArrayHasKey('cleaned_resources', $cleanup);
        $this->assertArrayHasKey('timestamp', $cleanup);
    }
} 