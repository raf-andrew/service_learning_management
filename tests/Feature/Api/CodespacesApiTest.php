<?php

namespace Tests\Feature\Api;

use Tests\Feature\TestCase;
use App\Services\CodespacesHealthService;
use App\Services\CodespacesTestReporter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class CodespacesApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('codespaces.enabled', true);
    }

    public function test_health_check_endpoint(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK'],
                    'redis' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $response = $this->getJson('/api/codespaces/health');

        $response->assertOk()
            ->assertJson([
                'database' => ['healthy' => true, 'message' => 'OK'],
                'cache' => ['healthy' => true, 'message' => 'OK'],
                'redis' => ['healthy' => true, 'message' => 'OK']
            ]);
    }

    public function test_run_tests_endpoint(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        Artisan::shouldReceive('call')
            ->once()
            ->with('test')
            ->andReturn(0);

        $response = $this->postJson('/api/codespaces/tests');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'results' => [
                    'passed' => 10,
                    'failed' => 0,
                    'skipped' => 0,
                    'duration' => 2.5,
                    'memory' => '64MB',
                    'suite' => 'all',
                    'details' => []
                ]
            ]);
    }

    public function test_run_tests_with_suite(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        Artisan::shouldReceive('call')
            ->once()
            ->with('test --testsuite=unit')
            ->andReturn(0);

        $response = $this->postJson('/api/codespaces/tests', [
            'suite' => 'unit'
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'results' => [
                    'suite' => 'unit'
                ]
            ]);
    }

    public function test_run_tests_with_filter(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        Artisan::shouldReceive('call')
            ->once()
            ->with('test --filter=testMethod')
            ->andReturn(0);

        $response = $this->postJson('/api/codespaces/tests', [
            'filter' => 'testMethod'
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'results' => [
                    'filter' => 'testMethod'
                ]
            ]);
    }

    public function test_generate_report_endpoint(): void
    {
        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('generateReport')
                ->once()
                ->andReturn('Test Report');
        });

        $response = $this->postJson('/api/codespaces/reports/generate', [
            'passed' => 10,
            'failed' => 2,
            'skipped' => 1,
            'duration' => 5.5,
            'memory' => '128MB',
            'timestamp' => now()->toIso8601String(),
            'suite' => 'unit',
            'details' => []
        ]);

        $response->assertOk()
            ->assertJson([
                'report' => 'Test Report'
            ]);
    }

    public function test_save_report_endpoint(): void
    {
        $this->mock(CodespacesTestReporter::class, function ($mock) {
            $mock->shouldReceive('saveReport')
                ->once()
                ->andReturn('test-reports/unit-test-report.html');
        });

        $response = $this->postJson('/api/codespaces/reports/save', [
            'passed' => 5,
            'failed' => 0,
            'skipped' => 0,
            'duration' => 2.5,
            'memory' => '64MB',
            'timestamp' => now()->toIso8601String(),
            'suite' => 'feature',
            'details' => []
        ]);

        $response->assertOk()
            ->assertJson([
                'filename' => 'test-reports/unit-test-report.html'
            ]);
    }

    public function test_health_check_fails_when_services_unhealthy(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => false, 'message' => 'Connection failed'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $response = $this->getJson('/api/codespaces/health');

        $response->assertOk()
            ->assertJson([
                'database' => ['healthy' => false, 'message' => 'Connection failed'],
                'cache' => ['healthy' => true, 'message' => 'OK']
            ]);
    }

    public function test_run_tests_fails_when_services_unhealthy(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => false, 'message' => 'Connection failed'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        $response = $this->postJson('/api/codespaces/tests');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Some services are unhealthy. Please fix them before running tests.'
            ]);
    }

    public function test_run_tests_fails_when_tests_fail(): void
    {
        $this->mock(CodespacesHealthService::class, function ($mock) {
            $mock->shouldReceive('checkAllServices')
                ->once()
                ->andReturn([
                    'database' => ['healthy' => true, 'message' => 'OK'],
                    'cache' => ['healthy' => true, 'message' => 'OK']
                ]);
        });

        Artisan::shouldReceive('call')
            ->once()
            ->with('test')
            ->andReturn(1);

        $response = $this->postJson('/api/codespaces/tests');

        $response->assertOk()
            ->assertJson([
                'success' => false,
                'results' => [
                    'passed' => 5,
                    'failed' => 2,
                    'skipped' => 1,
                    'duration' => 2.5,
                    'memory' => '64MB',
                    'suite' => 'all',
                    'details' => [
                        [
                            'name' => 'TestClass::testFailedMethod',
                            'status' => 'failed',
                            'duration' => 0.3,
                            'memory' => '1MB',
                            'error' => 'Assertion failed'
                        ]
                    ]
                ]
            ]);
    }
} 