<?php

namespace MCP\Exceptions;

/**
 * Authentication Exception
 * 
 * Thrown when authentication fails or encounters an error.
 * 
 * @package MCP\Exceptions
 */
class AuthenticationException extends \Exception
{
    /**
     * Create a new authentication exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "Authentication failed", int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 