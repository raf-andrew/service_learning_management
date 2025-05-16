<?php

namespace MCP\Exceptions;

/**
 * XSS Exception
 * 
 * Thrown when XSS validation fails or encounters an error.
 * 
 * @package MCP\Exceptions
 */
class XssException extends \Exception
{
    /**
     * Create a new XSS exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "XSS validation failed", int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 