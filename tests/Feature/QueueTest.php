<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\TestJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CodespacesTestTrait;

class QueueTest extends TestCase
{
    use CodespacesTestTrait, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCodespacesTest();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownCodespacesTest();
        parent::tearDown();
    }

    /**
     * Test that jobs can be dispatched to the queue
     *
     * @return void
     */
    public function test_job_can_be_dispatched()
    {
        $this->addTestStep('queue_dispatch', 'running');
        $job = new TestJob();
        dispatch($job);
        Queue::assertPushed(TestJob::class);
        $this->addTestStep('queue_dispatch', 'completed');
        $this->linkTestToChecklist('queue-dispatch');
    }

    /**
     * Test that jobs are processed in the correct order
     *
     * @return void
     */
    public function test_jobs_are_processed_in_order()
    {
        $this->addTestStep('queue_order', 'running');
        $jobs = [];
        for ($i = 0; $i < 3; $i++) {
            $jobs[] = new TestJob($i);
            dispatch($jobs[$i]);
        }
        Queue::assertPushed(TestJob::class, 3);
        $this->addTestStep('queue_order', 'completed');
        $this->linkTestToChecklist('queue-order');
    }

    /**
     * Test that failed jobs are handled correctly
     *
     * @return void
     */
    public function test_failed_jobs_are_handled()
    {
        $this->addTestStep('queue_failed', 'running');
        $job = new TestJob();
        $job->onQueue('failed-jobs');
        
        dispatch($job);
        
        Queue::assertPushedOn('failed-jobs', TestJob::class);
        $this->addTestStep('queue_failed', 'completed');
        $this->linkTestToChecklist('queue-failed');
    }

    /**
     * Test that jobs can be delayed
     *
     * @return void
     */
    public function test_jobs_can_be_delayed()
    {
        $this->addTestStep('queue_delayed', 'running');
        $job = new TestJob();
        dispatch($job->delay(now()->addMinutes(5)));

        Queue::assertPushed(TestJob::class, function ($job) {
            return $job->delay->diffInMinutes(now()) === 5;
        });
        $this->addTestStep('queue_delayed', 'completed');
        $this->linkTestToChecklist('queue-delayed');
    }

    /**
     * Test that jobs can be retried
     *
     * @return void
     */
    public function test_jobs_can_be_retried()
    {
        $this->addTestStep('queue_retry', 'running');
        $job = new TestJob();
        $job->tries = 3;
        
        dispatch($job);

        Queue::assertPushed(TestJob::class, function ($job) {
            return $job->tries === 3;
        });
        $this->addTestStep('queue_retry', 'completed');
        $this->linkTestToChecklist('queue-retry');
    }
} 