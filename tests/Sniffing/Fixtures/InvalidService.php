<?php

namespace Tests\Sniffing\Fixtures;

class InvalidService
{
    private $variable;

    public function process($data)
    {
        return $data;
    }
} 