<?php
use PHPUnit\Framework\TestCase;

class SanityTest extends TestCase
{
    public function test_phpunit_runs()
    {
        echo "SANITY TEST EXECUTED\n";
        $this->assertTrue(true);
    }
} 