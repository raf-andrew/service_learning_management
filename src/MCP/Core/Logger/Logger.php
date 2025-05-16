<?php

declare(strict_types=1);

namespace MCP\Core\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{
    protected string $name;
    protected string $logPath;
    protected string $logLevel;
    
    public function __construct(string $name, string $logPath)
    {
        $this->name = $name;
        $this->logPath = $logPath;
        $this->logLevel = LogLevel::DEBUG;
        
        if (!file_exists($logPath)) {
            mkdir($logPath, 0755, true);
        }
    }
    
    public function setLogLevel(string $level): void
    {
        $this->logLevel = $level;
    }
    
    public function emergency($message, array $context = array()): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    public function alert($message, array $context = array()): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    public function critical($message, array $context = array()): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    public function error($message, array $context = array()): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    public function warning($message, array $context = array()): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    public function notice($message, array $context = array()): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    public function info($message, array $context = array()): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    public function debug($message, array $context = array()): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    public function log($level, $message, array $context = array()): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $logEntry = $this->formatLogEntry($level, $message, $context);
        $logFile = $this->getLogFile();
        
        file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    protected function shouldLog(string $level): bool
    {
        $levels = [
            LogLevel::DEBUG => 0,
            LogLevel::INFO => 1,
            LogLevel::NOTICE => 2,
            LogLevel::WARNING => 3,
            LogLevel::ERROR => 4,
            LogLevel::CRITICAL => 5,
            LogLevel::ALERT => 6,
            LogLevel::EMERGENCY => 7
        ];
        
        return $levels[$level] >= $levels[$this->logLevel];
    }
    
    protected function formatLogEntry(string $level, string $message, array $context = []): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';
        
        return sprintf(
            '[%s] %s.%s: %s %s',
            $timestamp,
            $this->name,
            strtoupper($level),
            $message,
            $contextString
        );
    }
    
    protected function getLogFile(): string
    {
        return sprintf(
            '%s/%s_%s.log',
            rtrim($this->logPath, '/'),
            $this->name,
            date('Y-m-d')
        );
    }
} 