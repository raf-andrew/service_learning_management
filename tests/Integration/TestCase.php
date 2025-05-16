<?php

namespace Tests\Integration;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestReporter;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, DatabaseTransactions;

    protected $reporter;
    protected $testData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new TestReporter();
        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->reporter->addCodeQualityMetric('memory_usage', memory_get_peak_usage(true));
    }

    /**
     * Setup test data
     *
     * @return void
     */
    protected function setupTestData(): void
    {
        // Override in child classes to set up test data
    }

    /**
     * Create test data
     *
     * @param string $type
     * @param array $attributes
     * @return mixed
     */
    protected function createTestData(string $type, array $attributes = [])
    {
        $factory = "\\App\\Models\\" . ucfirst($type) . "::factory";
        return $factory::create($attributes);
    }

    /**
     * Assert that a service method returns the expected result
     *
     * @param string $service
     * @param string $method
     * @param array $parameters
     * @param mixed $expected
     * @return void
     */
    protected function assertServiceMethodResult(string $service, string $method, array $parameters, $expected): void
    {
        $result = app($service)->$method(...$parameters);
        $this->assertEquals($expected, $result);
    }

    /**
     * Assert that a service method throws the expected exception
     *
     * @param string $service
     * @param string $method
     * @param array $parameters
     * @param string $exception
     * @return void
     */
    protected function assertServiceMethodThrows(string $service, string $method, array $parameters, string $exception): void
    {
        $this->expectException($exception);
        app($service)->$method(...$parameters);
    }

    /**
     * Assert that a job was dispatched
     *
     * @param string $job
     * @param array $parameters
     * @return void
     */
    protected function assertJobDispatched(string $job, array $parameters = []): void
    {
        $this->assertDispatched($job, function ($dispatchedJob) use ($parameters) {
            return $this->jobParametersMatch($dispatchedJob, $parameters);
        });
    }

    /**
     * Check if job parameters match
     *
     * @param object $job
     * @param array $parameters
     * @return bool
     */
    protected function jobParametersMatch(object $job, array $parameters): bool
    {
        foreach ($parameters as $key => $value) {
            if ($job->$key !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assert that a notification was sent
     *
     * @param string $notification
     * @param mixed $notifiable
     * @param array $data
     * @return void
     */
    protected function assertNotificationSent(string $notification, $notifiable, array $data = []): void
    {
        $this->assertSentTo($notifiable, $notification, function ($sentNotification) use ($data) {
            return $this->notificationDataMatches($sentNotification, $data);
        });
    }

    /**
     * Check if notification data matches
     *
     * @param object $notification
     * @param array $data
     * @return bool
     */
    protected function notificationDataMatches(object $notification, array $data): bool
    {
        foreach ($data as $key => $value) {
            if ($notification->$key !== $value) {
                return false;
            }
        }
        return true;
    }
} 