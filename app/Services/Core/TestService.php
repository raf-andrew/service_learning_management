<?php

namespace App\Services;

class TestService
{
    private $variable; // Violation: Missing docblock

    public function __construct()
    {
        $this->variable = 'test';
    }

    public function process($data) // Violation: Missing docblock and type hints
    {
        return $data;
    }

    public function badMethod($param) // Violation: Incorrect naming convention
    {
        return $param;
    }
} 