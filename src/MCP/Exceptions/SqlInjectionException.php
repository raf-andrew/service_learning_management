<?php

namespace MCP\Exceptions;

/**
 * SQL Injection Exception
 * 
 * Exception thrown when a potential SQL injection attack is detected.
 * 
 * @package MCP\Exceptions
 */
class SqlInjectionException extends \Exception
{
    /**
     * Create a new SQL injection exception
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = "SQL injection attempt detected", int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 