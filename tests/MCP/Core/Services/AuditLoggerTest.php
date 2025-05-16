<?php

namespace Tests\MCP\Core\Services;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuditLoggerTest extends BaseTestCase
{
    protected AuditLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mcp.logging.channel', 'audit');
        $this->logger = new AuditLogger();
    }

    public function test_can_log_and_retrieve_entry(): void
    {
        $this->logger->log('test_action', ['key' => 'value']);
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('test_action', $logs[0]['action']);
        $this->assertEquals(['key' => 'value'], $logs[0]['data']);
    }

    public function test_can_log_with_category(): void
    {
        $this->logger->log('test_action', ['key' => 'value'], 'test_category');
        $logs = $this->logger->getLogs('test_category');
        
        $this->assertCount(1, $logs);
        $this->assertEquals('test_category', $logs[0]['category']);
    }

    public function test_respects_max_memory_logs_limit(): void
    {
        $this->logger->setConfig(['max_memory_logs' => 2]);
        
        $this->logger->log('action1', []);
        $this->logger->log('action2', []);
        $this->logger->log('action3', []);
        
        $logs = $this->logger->getLogs();
        $this->assertCount(2, $logs);
        $this->assertEquals('action2', $logs[0]['action']);
        $this->assertEquals('action3', $logs[1]['action']);
    }

    public function test_can_clear_logs(): void
    {
        $this->logger->log('test_action', []);
        $this->logger->clearLogs();
        
        $this->assertEmpty($this->logger->getLogs());
    }

    public function test_can_get_logs_with_limit(): void
    {
        $this->logger->log('action1', []);
        $this->logger->log('action2', []);
        $this->logger->log('action3', []);
        
        $logs = $this->logger->getLogs(null, 2);
        $this->assertCount(2, $logs);
        $this->assertEquals('action2', $logs[0]['action']);
        $this->assertEquals('action3', $logs[1]['action']);
    }

    public function test_can_get_logs_by_category_with_limit(): void
    {
        $this->logger->log('action1', [], 'category1');
        $this->logger->log('action2', [], 'category2');
        $this->logger->log('action3', [], 'category1');
        
        $logs = $this->logger->getLogs('category1', 1);
        $this->assertCount(1, $logs);
        $this->assertEquals('action3', $logs[0]['action']);
    }

    public function test_can_set_and_get_config(): void
    {
        $config = [
            'enabled' => false,
            'store_in_memory' => false,
        ];
        
        $this->logger->setConfig($config);
        $newConfig = $this->logger->getConfig();
        
        $this->assertFalse($newConfig['enabled']);
        $this->assertFalse($newConfig['store_in_memory']);
    }

    public function test_does_not_log_when_disabled(): void
    {
        $this->logger->setConfig(['enabled' => false]);
        $this->logger->log('test_action', []);
        
        $this->assertEmpty($this->logger->getLogs());
    }

    public function test_log_entry_includes_user_context(): void
    {
        Auth::shouldReceive('id')->once()->andReturn(1);
        
        $this->logger->log('test_action', []);
        $logs = $this->logger->getLogs();
        
        $this->assertEquals(1, $logs[0]['user_id']);
    }

    public function test_can_log_message(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('audit')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', [
                'category' => 'test',
                'timestamp' => \Mockery::any(),
                'test' => 'data'
            ]);

        $this->logger->log('test', 'Test message', ['test' => 'data']);
    }

    public function test_can_set_context(): void
    {
        $context = ['user_id' => 1, 'action' => 'test'];
        $this->logger->setContext($context);
        $this->assertEquals($context, $this->logger->getContext());
    }

    public function test_can_clear_context(): void
    {
        $this->logger->setContext(['test' => 'data']);
        $this->logger->clearContext();
        $this->assertEquals([], $this->logger->getContext());
    }

    public function test_context_is_included_in_log(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('audit')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', [
                'category' => 'test',
                'timestamp' => \Mockery::any(),
                'user_id' => 1,
                'action' => 'test'
            ]);

        $this->logger->setContext(['user_id' => 1]);
        $this->logger->log('test', 'Test message', ['action' => 'test']);
    }
} 