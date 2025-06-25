<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

abstract class UnitTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable events and queues by default
        Event::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock of the given class
     */
    protected function mock($abstract, ?\Closure $mock = null)
    {
        if ($mock) {
            return parent::mock($abstract, $mock);
        }
        return Mockery::mock($abstract);
    }

    /**
     * Create a spy of the given class
     */
    protected function spy($abstract, ?\Closure $mock = null)
    {
        if ($mock) {
            return parent::spy($abstract, $mock);
        }
        return Mockery::spy($abstract);
    }

    /**
     * Assert that the given method was called on the mock
     */
    protected function assertMethodCalled($mock, $method, $args = null)
    {
        if ($args === null) {
            $mock->shouldHaveReceived($method);
        } else {
            $mock->shouldHaveReceived($method)->with($args);
        }
    }

    /**
     * Assert that the given method was not called on the mock
     */
    protected function assertMethodNotCalled($mock, $method)
    {
        $mock->shouldNotHaveReceived($method);
    }

    /**
     * Create a partial mock of the given class
     */
    protected function partialMock($abstract, ?\Closure $mock = null)
    {
        if ($mock) {
            return parent::partialMock($abstract, $mock);
        }
        return Mockery::mock($abstract)->makePartial();
    }
} 