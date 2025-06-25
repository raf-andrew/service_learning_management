<?php

namespace Tests\Unit\Commands;

use Tests\Unit\UnitTestCase;
use App\Console\Commands\InfrastructureManagerCommand;
use Mockery;

class InfrastructureManagerCommandValidationTest extends UnitTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new InfrastructureManagerCommand();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_valid_action_returns_true_for_valid_actions()
    {
        $method = new \ReflectionMethod($this->command, 'isValidAction');
        $method->setAccessible(true);
        $validActions = ['status', 'start', 'stop', 'restart', 'cleanup'];
        foreach ($validActions as $action) {
            $this->assertTrue($method->invoke($this->command, $action));
        }
    }

    public function test_is_valid_action_returns_false_for_invalid_actions()
    {
        $method = new \ReflectionMethod($this->command, 'isValidAction');
        $method->setAccessible(true);
        $invalidActions = ['invalid', 'test', 'unknown', 'delete', 'create'];
        foreach ($invalidActions as $action) {
            $this->assertFalse($method->invoke($this->command, $action));
        }
    }
} 