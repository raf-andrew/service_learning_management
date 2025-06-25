<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CodespacesHealthService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CodespacesHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CodespacesHealthService $service;
    protected string $logPath;
    protected string $failuresPath;
    protected string $completePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logPath = base_path('.codespaces/log');
        $this->failuresPath = base_path('.codespaces/log/failures');
        $this->completePath = base_path('.codespaces/log/complete');
        
        $this->service = new CodespacesHealthService();
        
        // Clean up any existing test directories
        if (File::exists($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (File::exists($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }
        
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_required_directories_on_construction()
    {
        $this->assertDirectoryExists($this->logPath);
        $this->assertDirectoryExists($this->failuresPath);
        $this->assertDirectoryExists($this->completePath);
    }

    /** @test */
    public function it_returns_error_when_service_config_not_found()
    {
        Config::set('codespaces.services.test_service', null);
        
        $result = $this->service->checkServiceHealth('test_service');
        
        $this->assertFalse($result['healthy']);
        $this->assertEquals('Service configuration not found', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_service_is_disabled()
    {
        Config::set('codespaces.services.test_service', [
            'enabled' => false
        ]);
        
        $result = $this->service->checkServiceHealth('test_service');
        
        $this->assertFalse($result['healthy']);
        $this->assertEquals('Service is disabled', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_health_check_config_not_found()
    {
        Config::set('codespaces.services.test_service', [
            'enabled' => true
        ]);
        
        $result = $this->service->checkServiceHealth('test_service');
        
        $this->assertFalse($result['healthy']);
        $this->assertEquals('Health check configuration not found', $result['message']);
    }

    /** @test */
    public function it_checks_database_health()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andReturn(true);

        $result = $this->service->checkDatabase();

        $this->assertTrue($result['healthy']);
        $this->assertEquals('OK', $result['message']);
    }

    /** @test */
    public function it_detects_database_connection_failure()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $result = $this->service->checkDatabase();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Connection failed', $result['message']);
    }

    /** @test */
    public function it_checks_cache_health()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('health_check')
            ->andReturn(true);

        $result = $this->service->checkCache();

        $this->assertTrue($result['healthy']);
        $this->assertEquals('OK', $result['message']);
    }

    /** @test */
    public function it_detects_cache_failure()
    {
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache service unavailable'));

        $result = $this->service->checkCache();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Cache service unavailable', $result['message']);
    }

    /** @test */
    public function it_checks_redis_health()
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        $result = $this->service->checkRedis();

        $this->assertTrue($result['healthy']);
        $this->assertEquals('OK', $result['message']);
    }

    /** @test */
    public function it_detects_redis_failure()
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        $result = $this->service->checkRedis();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Redis connection failed', $result['message']);
    }

    /** @test */
    public function it_logs_successful_health_check()
    {
        Log::shouldReceive('channel')
            ->with('codespaces')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Service test_service is healthy' &&
                       $context['healthy'] === true;
            });

        $result = [
            'healthy' => true,
            'message' => 'Service is healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => 'test_service'
        ];

        $this->service->logSuccess('test_service', $result);

        $files = File::files($this->completePath);
        $this->assertCount(1, $files);
        
        $logContent = json_decode(File::get($files[0]), true);
        $this->assertEquals($result, $logContent);
    }

    /** @test */
    public function it_logs_failed_health_check()
    {
        Log::shouldReceive('channel')
            ->with('codespaces')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Service test_service is unhealthy') &&
                       $context['healthy'] === false;
            });

        $result = $this->service->logFailure('test_service', 'Test failure message');

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Test failure message', $result['message']);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('test_service', $result['service']);

        $files = File::files($this->failuresPath);
        $this->assertCount(1, $files);
        
        $logContent = json_decode(File::get($files[0]), true);
        $this->assertEquals($result, $logContent);
    }

    /** @test */
    public function it_checks_all_services()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('health_check')
            ->andReturn(true);

        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        $results = $this->service->checkAllServices();

        $this->assertArrayHasKey('database', $results);
        $this->assertArrayHasKey('cache', $results);
        $this->assertArrayHasKey('redis', $results);

        $this->assertTrue($results['database']['healthy']);
        $this->assertTrue($results['cache']['healthy']);
        $this->assertTrue($results['redis']['healthy']);
    }

    /** @test */
    public function it_handles_multiple_service_failures()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache service unavailable'));

        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        $results = $this->service->checkAllServices();

        $this->assertFalse($results['database']['healthy']);
        $this->assertEquals('Database connection failed', $results['database']['message']);

        $this->assertFalse($results['cache']['healthy']);
        $this->assertEquals('Cache service unavailable', $results['cache']['message']);

        $this->assertTrue($results['redis']['healthy']);
        $this->assertEquals('OK', $results['redis']['message']);
    }

    public function test_it_checks_database_health()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andReturn(true);

        $result = $this->service->checkDatabaseHealth();

        $this->assertTrue($result['healthy']);
        $this->assertEquals('Database is healthy', $result['message']);
    }

    public function test_it_detects_database_connection_failure()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('Connection failed'));

        $result = $this->service->checkDatabaseHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Database connection failed', $result['message']);
    }

    public function test_it_checks_cache_health()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('health_check')
            ->andReturn(true);

        $result = $this->service->checkCacheHealth();

        $this->assertTrue($result['healthy']);
        $this->assertEquals('Cache is healthy', $result['message']);
    }

    public function test_it_detects_cache_failure()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andThrow(new \Exception('Cache connection failed'));

        $result = $this->service->checkCacheHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Cache connection failed', $result['message']);
    }

    public function test_it_checks_redis_health()
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        $result = $this->service->checkRedisHealth();

        $this->assertTrue($result['healthy']);
        $this->assertEquals('Redis is healthy', $result['message']);
    }

    public function test_it_detects_redis_failure()
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        $result = $this->service->checkRedisHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Redis connection failed', $result['message']);
    }

    public function test_it_checks_all_services()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('health_check')
            ->andReturn(true);

        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        $result = $this->service->checkAllServices();

        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('cache', $result);
        $this->assertArrayHasKey('redis', $result);

        $this->assertTrue($result['database']['healthy']);
        $this->assertTrue($result['cache']['healthy']);
        $this->assertTrue($result['redis']['healthy']);

        $this->assertEquals('Database is healthy', $result['database']['message']);
        $this->assertEquals('Cache is healthy', $result['cache']['message']);
        $this->assertEquals('Redis is healthy', $result['redis']['message']);
    }

    public function test_it_handles_multiple_service_failures()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('Database connection failed'));

        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('health_check')
            ->andReturn(true);

        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        $result = $this->service->checkAllServices();

        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('cache', $result);
        $this->assertArrayHasKey('redis', $result);

        $this->assertFalse($result['database']['healthy']);
        $this->assertTrue($result['cache']['healthy']);
        $this->assertFalse($result['redis']['healthy']);

        $this->assertEquals('Database connection failed', $result['database']['message']);
        $this->assertEquals('Cache is healthy', $result['cache']['message']);
        $this->assertEquals('Redis connection failed', $result['redis']['message']);
    }

    public function test_it_handles_cache_get_failure()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('health_check')
            ->andReturn(null);

        $result = $this->service->checkCacheHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Cache read failed', $result['message']);
    }

    public function test_it_handles_cache_put_failure()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('health_check', true, 1)
            ->andReturn(false);

        $result = $this->service->checkCacheHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Cache write failed', $result['message']);
    }

    public function test_it_handles_redis_ping_timeout()
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \RedisException('Connection timed out'));

        $result = $this->service->checkRedisHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Redis connection timed out', $result['message']);
    }

    public function test_it_handles_database_connection_timeout()
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('Connection timed out'));

        $result = $this->service->checkDatabaseHealth();

        $this->assertFalse($result['healthy']);
        $this->assertEquals('Database connection timed out', $result['message']);
    }
} 