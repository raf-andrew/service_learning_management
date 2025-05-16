<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Tests\Traits\CodespacesTestTrait;

class ServiceHealthTest extends TestCase
{
    use CodespacesTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCodespacesTest();
        $this->withoutExceptionHandling();
    }

    protected function tearDown(): void
    {
        $this->tearDownCodespacesTest();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_connect_to_database()
    {
        $this->addTestStep('database_connection', 'running');
        try {
            DB::connection()->getPdo();
            $this->addTestStep('database_connection', 'completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->addTestStep('database_connection', 'failed', $e->getMessage());
            $this->fail('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_can_connect_to_redis()
    {
        $this->addTestStep('redis_connection', 'running');
        try {
            Redis::ping();
            $this->addTestStep('redis_connection', 'completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->addTestStep('redis_connection', 'failed', $e->getMessage());
            $this->fail('Redis connection failed: ' . $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_can_send_mail()
    {
        $this->addTestStep('mail_send', 'running');
        try {
            Mail::raw('Test email', function($message) {
                $message->to('test@example.com')
                        ->subject('Test Subject');
            });
            $this->addTestStep('mail_send', 'completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->addTestStep('mail_send', 'failed', $e->getMessage());
            $this->fail('Mail sending failed: ' . $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_can_process_queue()
    {
        $this->addTestStep('queue_process', 'running');
        try {
            Queue::push(function() {
                Log::info('Test queue job');
            });
            $this->addTestStep('queue_process', 'completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->addTestStep('queue_process', 'failed', $e->getMessage());
            $this->fail('Queue processing failed: ' . $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_can_write_to_logs()
    {
        $this->addTestStep('log_write', 'running');
        try {
            Log::info('Test log entry');
            $this->addTestStep('log_write', 'completed');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->addTestStep('log_write', 'failed', $e->getMessage());
            $this->fail('Log writing failed: ' . $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_can_access_storage()
    {
        $this->addTestStep('storage_access', 'running');
        try {
            $path = storage_path('app/test.txt');
            file_put_contents($path, 'Test content');
            $this->assertFileExists($path);
            unlink($path);
            $this->addTestStep('storage_access', 'completed');
        } catch (\Exception $e) {
            $this->addTestStep('storage_access', 'failed', $e->getMessage());
            $this->fail('Storage access failed: ' . $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_can_access_codespaces_config()
    {
        $this->addTestStep('codespaces_config', 'running');
        $this->assertTrue(config('codespaces.enabled'));
        $this->assertIsArray(config('codespaces.services'));
        $this->assertIsArray(config('codespaces.services.database'));
        $this->assertIsArray(config('codespaces.services.redis'));
        $this->addTestStep('codespaces_config', 'completed');
        $this->linkTestToChecklist('service-health-check');
    }
} 