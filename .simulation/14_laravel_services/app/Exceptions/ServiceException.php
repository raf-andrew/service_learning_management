<?php

namespace App\Exceptions;

use Exception;

class ServiceException extends Exception
{
    protected $context = [];

    public function __construct(string $message = "", int $code = 0, array $context = [])
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
} 