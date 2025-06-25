<?php

namespace Modules\Api\Exceptions;

use Exception;

class ApiRateLimitException extends Exception
{
    /**
     * The error code.
     */
    protected $errorCode;

    /**
     * The retry after time.
     */
    protected $retryAfter;

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Rate limit exceeded', int $code = 429, int $retryAfter = 60, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $code;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Get the retry after time.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
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
            'retry_after' => $this->getRetryAfter(),
            'timestamp' => now()->toISOString(),
        ], $this->getErrorCode());
    }
} 