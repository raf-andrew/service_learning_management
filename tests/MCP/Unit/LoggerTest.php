<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Logger;
use Psr\Log\LoggerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;

class LoggerTest extends TestCase
{
    private Logger $logger;
    private TestHandler $testHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testHandler = new TestHandler();
        $monolog = new MonologLogger('test');
        $monolog->pushHandler($this->testHandler);
        
        $this->logger = new Logger($monolog);
    }

    public function testLoggerCanBeCreated(): void
    {
        $this->assertInstanceOf(Logger::class, $this->logger);
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }

    public function testLoggerCanLogEmergency(): void
    {
        $this->logger->emergency('Test emergency message');
        
        $this->assertTrue($this->testHandler->hasEmergency('Test emergency message'));
    }

    public function testLoggerCanLogAlert(): void
    {
        $this->logger->alert('Test alert message');
        
        $this->assertTrue($this->testHandler->hasAlert('Test alert message'));
    }

    public function testLoggerCanLogCritical(): void
    {
        $this->logger->critical('Test critical message');
        
        $this->assertTrue($this->testHandler->hasCritical('Test critical message'));
    }

    public function testLoggerCanLogError(): void
    {
        $this->logger->error('Test error message');
        
        $this->assertTrue($this->testHandler->hasError('Test error message'));
    }

    public function testLoggerCanLogWarning(): void
    {
        $this->logger->warning('Test warning message');
        
        $this->assertTrue($this->testHandler->hasWarning('Test warning message'));
    }

    public function testLoggerCanLogNotice(): void
    {
        $this->logger->notice('Test notice message');
        
        $this->assertTrue($this->testHandler->hasNotice('Test notice message'));
    }

    public function testLoggerCanLogInfo(): void
    {
        $this->logger->info('Test info message');
        
        $this->assertTrue($this->testHandler->hasInfo('Test info message'));
    }

    public function testLoggerCanLogDebug(): void
    {
        $this->logger->debug('Test debug message');
        
        $this->assertTrue($this->testHandler->hasDebug('Test debug message'));
    }

    public function testLoggerCanLogWithContext(): void
    {
        $context = ['key' => 'value'];
        $this->logger->info('Test message with context', $context);
        
        $this->assertTrue($this->testHandler->hasInfo('Test message with context'));
        $this->assertEquals($context, $this->testHandler->getRecords()[0]['context']);
    }

    public function testLoggerCanLogWithException(): void
    {
        $exception = new \Exception('Test exception');
        $this->logger->error('Test message with exception', ['exception' => $exception]);
        
        $this->assertTrue($this->testHandler->hasError('Test message with exception'));
        $this->assertInstanceOf(\Exception::class, $this->testHandler->getRecords()[0]['context']['exception']);
    }

    public function testLoggerCanSetChannel(): void
    {
        $this->logger->setChannel('test-channel');
        
        $this->logger->info('Test message');
        
        $this->assertEquals('test-channel', $this->testHandler->getRecords()[0]['channel']);
    }
} 