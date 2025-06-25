<?php

namespace Tests\Sniffing\Fixtures;

use App\Services\Interfaces\ServiceInterface;

class ValidService implements ServiceInterface
{
    private $variable;

    public function __construct()
    {
        $this->variable = 'test';
    }

    /**
     * Process the given data
     *
     * @param mixed $data
     * @return mixed
     */
    public function process($data)
    {
        return $data;
    }
} 