<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestReporter;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new TestReporter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->reporter->addCodeQualityMetric('memory_usage', memory_get_peak_usage(true));
    }

    /**
     * Assert that a model has the expected attributes
     *
     * @param array $expected
     * @param object $model
     * @return void
     */
    protected function assertModelHasAttributes(array $expected, object $model): void
    {
        foreach ($expected as $attribute => $value) {
            $this->assertEquals($value, $model->{$attribute});
        }
    }

    /**
     * Assert that a collection contains models with expected attributes
     *
     * @param array $expected
     * @param \Illuminate\Support\Collection $collection
     * @return void
     */
    protected function assertCollectionContainsModels(array $expected, \Illuminate\Support\Collection $collection): void
    {
        $this->assertCount(count($expected), $collection);
        
        foreach ($expected as $index => $attributes) {
            $this->assertModelHasAttributes($attributes, $collection[$index]);
        }
    }

    /**
     * Create a test instance with mocked dependencies
     *
     * @param string $class
     * @param array $dependencies
     * @return object
     */
    protected function createTestInstance(string $class, array $dependencies = []): object
    {
        $mocks = [];
        foreach ($dependencies as $dependency) {
            $mocks[$dependency] = $this->createMock($dependency);
        }
        
        return new $class(...array_values($mocks));
    }

    /**
     * Assert that an event was dispatched
     *
     * @param string $eventClass
     * @param callable $callback
     * @return void
     */
    protected function assertEventDispatched(string $eventClass, callable $callback = null): void
    {
        $dispatched = false;
        
        Event::fake([$eventClass]);
        
        Event::assertDispatched($eventClass, function ($event) use ($callback, &$dispatched) {
            if ($callback && $callback($event)) {
                $dispatched = true;
                return true;
            }
            return false;
        });
        
        if ($callback) {
            $this->assertTrue($dispatched, "Event {$eventClass} was not dispatched with expected conditions");
        }
    }
} 