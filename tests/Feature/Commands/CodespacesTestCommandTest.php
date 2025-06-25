<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;
use Illuminate\Support\Facades\Config;
use Mockery;

class CodespacesTestCommandTest extends TestCase
{
    protected $healthService;
    protected $testReporter;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->healthService = Mockery::mock(CodespacesHealthService::class);
        $this->testReporter = Mockery::mock(CodespacesTestReporter::class);
        $this->app->instance(CodespacesHealthService::class, $this->healthService);
        $this->app->instance(CodespacesTestReporter::class, $this->testReporter);
        $this->testReporter->shouldIgnoreMissing();
        $this->testReporter->shouldReceive('completeTest')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_error_when_codespaces_disabled()
    {
        Config::set('codespaces.enabled', false);
        $result = $this->artisan('codespaces:test')->run();
        $this->assertEquals(1, $result);
    }

    public function test_it_returns_error_when_services_unhealthy()
    {
        Config::set('codespaces.enabled', true);
        $this->healthService->shouldReceive('checkAllServices')
            ->andReturn([
                'database' => ['healthy' => false, 'message' => 'Database is down']
            ]);
        
        $result = $this->artisan('codespaces:test')->run();
        $this->assertEquals(1, $result);
    }

    public function test_it_handles_multiple_unhealthy_services()
    {
        Config::set('codespaces.enabled', true);
        $this->healthService->shouldReceive('checkAllServices')
            ->andReturn([
                'database' => ['healthy' => false, 'message' => 'Database is down'],
                'cache' => ['healthy' => false, 'message' => 'Cache is down']
            ]);
        
        $result = $this->artisan('codespaces:test')->run();
        $this->assertEquals(1, $result);
    }
} 