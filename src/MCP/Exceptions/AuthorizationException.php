<?php

namespace MCP\Exceptions;

/**
 * Authorization Exception
 * 
 * Thrown when authorization fails or encounters an error.
 * 
 * @package MCP\Exceptions
 */
class AuthorizationException extends \Exception
{
    /**
     * Create a new authorization exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "Authorization failed", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 