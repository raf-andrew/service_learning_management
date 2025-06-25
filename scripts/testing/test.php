
<?php

namespace AppServices;

class TestService
{
    private $variable;

    public function __construct()
    {
        $this->variable = 'test';
    }

    public function process($data)
    {
        return $data;
    }
}
