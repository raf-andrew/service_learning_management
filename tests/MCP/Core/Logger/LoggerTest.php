<?php

namespace Tests\MCP\Core\Logger;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\Logger\Logger;
use Psr\Log\LogLevel;

class LoggerTest extends BaseTestCase
{
    protected Logger $logger;
    protected string $logPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logPath = sys_get_temp_dir() . '/mcp_test_logs';
        $this->logger = new Logger('test', $this->logPath);
        
        // Clean up any existing log files
        if (file_exists($this->logPath)) {
            $this->cleanupLogDirectory($this->logPath);
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up log files after tests
        if (file_exists($this->logPath)) {
            $this->cleanupLogDirectory($this->logPath);
        }
    }
    
    public function test_logger_creates_log_directory(): void
    {
        $this->assertTrue(file_exists($this->logPath));
        $this->assertTrue(is_dir($this->logPath));
    }
    
    public function test_logger_writes_log_entries(): void
    {
        $message = 'Test log message';
        $context = ['key' => 'value'];
        
        $this->logger->info($message, $context);
        
        $logFile = $this->getLogFile();
        $this->assertTrue(file_exists($logFile));
        
        $content = file_get_contents($logFile);
        $this->assertStringContainsString($message, $content);
        $this->assertStringContainsString(json_encode($context), $content);
    }
    
    public function test_logger_respects_log_level(): void
    {
        $this->logger->setLogLevel(LogLevel::ERROR);
        
        $this->logger->debug('Debug message');
        $this->logger->info('Info message');
        $this->logger->error('Error message');
        
        $logFile = $this->getLogFile();
        $content = file_get_contents($logFile);
        
        $this->assertStringNotContainsString('Debug message', $content);
        $this->assertStringNotContainsString('Info message', $content);
        $this->assertStringContainsString('Error message', $content);
    }
    
    public function test_logger_formats_entries_correctly(): void
    {
        $message = 'Test message';
        $context = ['test' => true];
        
        $this->logger->info($message, $context);
        
        $logFile = $this->getLogFile();
        $content = file_get_contents($logFile);
        
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] test\.INFO: Test message {"test":true}/',
            $content
        );
    }
    
    public function test_logger_handles_all_log_levels(): void
    {
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ];
        
        foreach ($levels as $level) {
            $this->logger->log($level, "Test {$level} message");
        }
        
        $logFile = $this->getLogFile();
        $content = file_get_contents($logFile);
        
        foreach ($levels as $level) {
            $this->assertStringContainsString(strtoupper($level), $content);
            $this->assertStringContainsString("Test {$level} message", $content);
        }
    }
    
    protected function getLogFile(): string
    {
        return sprintf(
            '%s/test_%s.log',
            $this->logPath,
            date('Y-m-d')
        );
    }
    
    protected function cleanupLogDirectory(string $directory): void
    {
        $files = glob($directory . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($directory);
    }
} 