<?php

namespace App\Modules\E2ee\Exceptions;

use Exception;

class EncryptionException extends Exception
{
    /**
     * @var string
     */
    protected string $errorCode;

    /**
     * @var array
     */
    protected array $context;

    public function __construct(string $message = '', string $errorCode = 'ENCRYPTION_ERROR', array $context = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the error message with context
     */
    public function getFullMessage(): string
    {
        $message = $this->getMessage();
        
        if (!empty($this->context)) {
            $message .= ' Context: ' . json_encode($this->context);
        }
        
        return $message;
    }
} 