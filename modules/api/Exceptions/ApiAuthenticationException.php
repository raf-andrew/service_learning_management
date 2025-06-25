<?php

namespace Modules\Api\Exceptions;

use Exception;

class ApiAuthenticationException extends Exception
{
    /**
     * The error code.
     */
    protected $errorCode;

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Authentication failed', int $code = 401, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $code;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->getErrorCode(),
            'timestamp' => now()->toISOString(),
        ], $this->getErrorCode());
    }
} 