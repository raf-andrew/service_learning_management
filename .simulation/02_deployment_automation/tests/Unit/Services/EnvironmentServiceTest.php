<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EnvironmentService;
use App\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class EnvironmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $environmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->environmentService = new EnvironmentService();
    }

    public function test_create_environment_creates_environment()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ];

        $environment = $this->environmentService->createEnvironment('test-environment', $config);

        $this->assertInstanceOf(Environment::class, $environment);
        $this->assertEquals('test-environment', $environment->name);
        $this->assertEquals('develop', $environment->branch);
        $this->assertEquals('http://test.example.com', $environment->url);
        $this->assertEquals($config['variables'], $environment->variables);
        $this->assertEquals('ready', $environment->status);
    }

    public function test_create_environment_fails_when_environment_exists()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ];

        $this->environmentService->createEnvironment('test-environment', $config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Environment test-environment already exists');

        $this->environmentService->createEnvironment('test-environment', $config);
    }

    public function test_update_environment_updates_environment()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ];

        $environment = $this->environmentService->createEnvironment('test-environment', $config);

        $updateConfig = [
            'branch' => 'main',
            'variables' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false'
            ]
        ];

        $updatedEnvironment = $this->environmentService->updateEnvironment('test-environment', $updateConfig);

        $this->assertEquals('main', $updatedEnvironment->branch);
        $this->assertEquals($updateConfig['variables'], $updatedEnvironment->variables);
        $this->assertEquals('http://test.example.com', $updatedEnvironment->url); // Unchanged
    }

    public function test_delete_environment_deletes_environment()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ];

        $this->environmentService->createEnvironment('test-environment', $config);
        $result = $this->environmentService->deleteEnvironment('test-environment');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('environments', ['name' => 'test-environment']);
    }

    public function test_delete_environment_fails_when_environment_not_found()
    {
        $this->expectException(\Exception::class);
        $this->environmentService->deleteEnvironment('non-existent-environment');
    }

    public function test_validate_environment_validates_environment()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ];

        $this->environmentService->createEnvironment('test-environment', $config);
        $result = $this->environmentService->validateEnvironment('test-environment');

        $this->assertTrue($result);
    }

    public function test_validate_environment_fails_when_missing_required_variables()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'http://test.example.com',
            'variables' => [
                'APP_ENV' => 'testing'
                // Missing APP_DEBUG
            ]
        ];

        $this->environmentService->createEnvironment('test-environment', $config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required environment variables: APP_DEBUG');

        $this->environmentService->validateEnvironment('test-environment');
    }

    public function test_validate_environment_fails_when_invalid_url()
    {
        $config = [
            'name' => 'test-environment',
            'branch' => 'develop',
            'url' => 'invalid-url',
            'variables' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true'
            ]
        ];

        $this->environmentService->createEnvironment('test-environment', $config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Valid URL configuration is required');

        $this->environmentService->validateEnvironment('test-environment');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 