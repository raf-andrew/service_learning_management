<?php

namespace MCP\Exceptions;

/**
 * CSRF Exception
 * 
 * Thrown when CSRF validation fails or encounters an error.
 * 
 * @package MCP\Exceptions
 */
class CsrfException extends \Exception
{
    /**
     * Create a new CSRF exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "CSRF validation failed", int $code = 419, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 