<?php

namespace Setup\Utils;

class Logger {
    private string $logFile;
    private string $logLevel;
    private bool $consoleOutput;
    private array $levels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];

    public function __construct(string $logFile = null, string $logLevel = 'info', bool $consoleOutput = true) {
        $this->logFile = $logFile ?? dirname(__DIR__, 2) . '/logs/setup.log';
        $this->logLevel = strtolower($logLevel);
        $this->consoleOutput = $consoleOutput;

        if (!isset($this->levels[$this->logLevel])) {
            throw new \InvalidArgumentException("Invalid log level: {$logLevel}");
        }

        $this->ensureLogDirectory();
    }

    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->log('critical', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void {
        if ($this->levels[$level] < $this->levels[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = $this->formatMessage($timestamp, $level, $message, $context);

        if ($this->consoleOutput) {
            $this->writeToConsole($level, $formattedMessage);
        }

        $this->writeToFile($formattedMessage);
    }

    private function formatMessage(string $timestamp, string $level, string $message, array $context): string {
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        return "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
    }

    private function writeToConsole(string $level, string $message): void {
        $colors = [
            'debug' => "\033[36m", // Cyan
            'info' => "\033[32m",  // Green
            'warning' => "\033[33m", // Yellow
            'error' => "\033[31m",   // Red
            'critical' => "\033[35m" // Magenta
        ];

        $reset = "\033[0m";
        echo $colors[$level] . $message . $reset;
    }

    private function writeToFile(string $message): void {
        if (!file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX)) {
            throw new \RuntimeException("Failed to write to log file: {$this->logFile}");
        }
    }

    private function ensureLogDirectory(): void {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                throw new \RuntimeException("Failed to create log directory: {$logDir}");
            }
        }
    }

    public function setLogFile(string $logFile): void {
        $this->logFile = $logFile;
        $this->ensureLogDirectory();
    }

    public function setLogLevel(string $level): void {
        if (!isset($this->levels[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }
        $this->logLevel = strtolower($level);
    }

    public function setConsoleOutput(bool $enabled): void {
        $this->consoleOutput = $enabled;
    }

    public function getLogFile(): string {
        return $this->logFile;
    }

    public function getLogLevel(): string {
        return $this->logLevel;
    }

    public function isConsoleOutputEnabled(): bool {
        return $this->consoleOutput;
    }

    public function clearLog(): void {
        if (file_exists($this->logFile)) {
            if (file_put_contents($this->logFile, '') === false) {
                throw new \RuntimeException("Failed to clear log file: {$this->logFile}");
            }
        }
    }

    public function getLogContents(): string {
        if (!file_exists($this->logFile)) {
            return '';
        }
        
        $contents = file_get_contents($this->logFile);
        if ($contents === false) {
            throw new \RuntimeException("Failed to read log file: {$this->logFile}");
        }
        
        return $contents;
    }

    public function getLogSize(): int {
        if (!file_exists($this->logFile)) {
            return 0;
        }
        
        $size = filesize($this->logFile);
        if ($size === false) {
            throw new \RuntimeException("Failed to get log file size: {$this->logFile}");
        }
        
        return $size;
    }

    public function rotateLog(int $maxSize = 5242880): void { // 5MB default
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $size = $this->getLogSize();
        if ($size < $maxSize) {
            return;
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $rotatedFile = $this->logFile . '.' . $timestamp;
        
        if (!rename($this->logFile, $rotatedFile)) {
            throw new \RuntimeException("Failed to rotate log file: {$this->logFile}");
        }
        
        $this->clearLog();
    }
} 