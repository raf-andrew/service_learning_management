<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\CodespacesServiceHealth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

class CodespacesServiceTest extends TestCase
{
    use CodespacesServiceHealth;

    public function test_database_connection()
    {
        $this->assertTrue(DB::connection()->getPdo() instanceof \PDO);
    }

    public function test_redis_connection()
    {
        $this->assertEquals('PONG', Redis::ping());
    }

    public function test_cache_connection()
    {
        $key = 'test_key_' . uniqid();
        $value = 'test_value_' . uniqid();
        
        Cache::put($key, $value, 1);
        $this->assertEquals($value, Cache::get($key));
    }

    public function test_queue_connection()
    {
        $jobId = Queue::push(function() {
            return true;
        });
        
        $this->assertNotNull($jobId);
    }

    public function test_mail_connection()
    {
        $this->assertTrue(
            Mail::raw('Test email', function($message) {
                $message->to('test@example.com')
                        ->subject('Test');
            })
        );
    }

    public function test_codespaces_configuration()
    {
        $this->assertTrue(Config::get('codespaces.enabled'));
        $this->assertIsArray(Config::get('codespaces.services'));
        $this->assertIsArray(Config::get('codespaces.logging'));
        $this->assertIsArray(Config::get('codespaces.testing'));
    }
} 