<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Has Logging Trait
 * 
 * Provides consistent logging functionality across classes.
 * This trait includes methods for structured logging with context.
 */
trait HasLogging
{
    /**
     * Log an info message with context
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log a warning message with context
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log an error message with context
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a debug message with context
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log an operation with context
     *
     * @param string $operation
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['class'] = static::class;
        $context['timestamp'] = now()->toISOString();
        
        $this->logInfo("Operation: {$operation}", $context);
    }

    /**
     * Log an error with exception details
     *
     * @param string $operation
     * @param \Throwable $exception
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logErrorWithException(string $operation, \Throwable $exception, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['exception'] = get_class($exception);
        $context['message'] = $exception->getMessage();
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();
        $context['trace'] = $exception->getTraceAsString();
        $context['class'] = static::class;
        $context['timestamp'] = now()->toISOString();
        
        $this->logError("Error in operation: {$operation}", $context);
    }

    /**
     * Log performance metrics
     *
     * @param string $operation
     * @param float $duration
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['duration_ms'] = round($duration * 1000, 2);
        $context['class'] = static::class;
        $context['timestamp'] = now()->toISOString();
        
        if ($duration > 1.0) {
            $this->logWarning("Slow operation: {$operation} took {$context['duration_ms']}ms", $context);
        } else {
            $this->logDebug("Operation completed: {$operation} took {$context['duration_ms']}ms", $context);
        }
    }

    /**
     * Log security event
     *
     * @param string $event
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logSecurityEvent(string $event, array $context = []): void
    {
        $context['security_event'] = $event;
        $context['class'] = static::class;
        $context['timestamp'] = now()->toISOString();
        $context['ip'] = request()->ip();
        $context['user_agent'] = request()->userAgent();
        $context['user_id'] = auth()->id();
        
        $this->logWarning("Security event: {$event}", $context);
    }

    /**
     * Log database operation
     *
     * @param string $operation
     * @param string $table
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logDatabaseOperation(string $operation, string $table, array $context = []): void
    {
        $context['db_operation'] = $operation;
        $context['table'] = $table;
        $context['class'] = static::class;
        $context['timestamp'] = now()->toISOString();
        
        $this->logDebug("Database operation: {$operation} on {$table}", $context);
    }

    /**
     * Log cache operation
     *
     * @param string $operation
     * @param string $key
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logCacheOperation(string $operation, string $key, array $context = []): void
    {
        $context['cache_operation'] = $operation;
        $context['cache_key'] = $key;
        $context['class'] = static::class;
        $context['timestamp'] = now()->toISOString();
        
        $this->logDebug("Cache operation: {$operation} for key {$key}", $context);
    }

    /**
     * Internal logging method
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void
    {
        // Add common context
        $context['class'] = $context['class'] ?? static::class;
        $context['timestamp'] = $context['timestamp'] ?? now()->toISOString();
        
        // Sanitize sensitive data
        $context = $this->sanitizeLogContext($context);
        
        Log::log($level, $message, $context);
    }

    /**
     * Sanitize log context to remove sensitive information
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeLogContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'token', 'secret', 'key', 'api_key', 'github_token',
            'authorization', 'cookie', 'session', 'csrf_token'
        ];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($context[$key])) {
                $context[$key] = '***HIDDEN***';
            }
        }
        
        return $context;
    }
} 