<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class BaseCommandTestCase extends PHPUnitTestCase
{
    use InteractsWithConsole, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        // No database setup
    }
} 