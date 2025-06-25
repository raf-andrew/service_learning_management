<?php

namespace App\Modules\E2ee\Exceptions;

use Exception;

class E2eeException extends Exception
{
    protected string $errorCode;
    protected array $context;

    public function __construct(string $message = "", string $errorCode = "E2EE_ERROR", array $context = [])
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }
} 