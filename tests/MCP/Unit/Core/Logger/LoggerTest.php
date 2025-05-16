<?php

declare(strict_types=1);

namespace MCP\Tests\Unit\Core\Logger;

use MCP\Core\Logger\Logger;
use MCP\Tests\Helpers\TestCase;

class LoggerTest extends TestCase
{
    private Logger $logger;
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . '/mcp_test_logs';
        $this->logger = new Logger('test', $this->logPath);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->logPath)) {
            array_map('unlink', glob($this->logPath . '/*'));
            rmdir($this->logPath);
        }

        parent::tearDown();
    }

    public function testLoggerCanBeCreated(): void
    {
        $this->assertInstanceOf(Logger::class, $this->logger);
    }

    public function testLoggerCanLogMessages(): void
    {
        $message = 'Test log message';
        $context = ['test' => 'data'];

        $this->logger->log(Logger::INFO, $message, $context);

        $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';
        $this->assertFileExists($logFile);

        $content = file_get_contents($logFile);
        $this->assertStringContainsString($message, $content);
        $this->assertStringContainsString(json_encode($context), $content);
    }

    public function testLoggerThrowsExceptionForInvalidLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level: invalid');

        $this->logger->log('invalid', 'Test message');
    }

    public function testLoggerCanAddCustomHandler(): void
    {
        $messages = [];
        $handler = function ($level, $message, $context) use (&$messages) {
            $messages[] = [
                'level' => $level,
                'message' => $message,
                'context' => $context
            ];
        };

        $this->logger->addHandler($handler);
        $this->logger->info('Test message', ['test' => 'data']);

        $this->assertCount(1, $messages);
        $this->assertEquals(Logger::INFO, $messages[0]['level']);
        $this->assertEquals('Test message', $messages[0]['message']);
        $this->assertEquals(['test' => 'data'], $messages[0]['context']);
    }

    public function testLoggerCanRemoveHandler(): void
    {
        $messages = [];
        $handler = function ($level, $message, $context) use (&$messages) {
            $messages[] = [
                'level' => $level,
                'message' => $message,
                'context' => $context
            ];
        };

        $this->logger->addHandler($handler);
        $this->logger->info('Test message 1');
        $this->logger->removeHandler($handler);
        $this->logger->info('Test message 2');

        $this->assertCount(1, $messages);
        $this->assertEquals('Test message 1', $messages[0]['message']);
    }

    public function testLoggerCanLogAtDifferentLevels(): void
    {
        $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';

        $this->logger->emergency('Emergency message');
        $this->logger->alert('Alert message');
        $this->logger->critical('Critical message');
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logger->notice('Notice message');
        $this->logger->info('Info message');
        $this->logger->debug('Debug message');

        $content = file_get_contents($logFile);

        $this->assertStringContainsString('EMERGENCY: Emergency message', $content);
        $this->assertStringContainsString('ALERT: Alert message', $content);
        $this->assertStringContainsString('CRITICAL: Critical message', $content);
        $this->assertStringContainsString('ERROR: Error message', $content);
        $this->assertStringContainsString('WARNING: Warning message', $content);
        $this->assertStringContainsString('NOTICE: Notice message', $content);
        $this->assertStringContainsString('INFO: Info message', $content);
        $this->assertStringContainsString('DEBUG: Debug message', $content);
    }

    public function testLoggerCreatesLogDirectoryIfNotExists(): void
    {
        $this->assertDirectoryExists($this->logPath);
    }

    public function testLoggerCreatesLogFileWithCorrectFormat(): void
    {
        $message = 'Test log message';
        $context = ['test' => 'data'];

        $this->logger->info($message, $context);

        $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';
        $content = file_get_contents($logFile);

        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] INFO: Test log message {"test":"data"}\n$/',
            $content
        );
    }
} 