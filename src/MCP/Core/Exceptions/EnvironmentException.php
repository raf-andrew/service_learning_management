<?php

namespace App\MCP\Core\Exceptions;

use Exception;

class EnvironmentException extends Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 