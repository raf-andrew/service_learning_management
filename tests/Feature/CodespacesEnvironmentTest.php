<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CodespacesConfigManager;
use App\Services\CodespacesLifecycleManager;
use App\Services\CodespacesHealthMonitor;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class CodespacesEnvironmentTest extends TestCase
{
    protected $configManager;
    protected $lifecycleManager;
    protected $healthMonitor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->configManager = app(CodespacesConfigManager::class);
        $this->lifecycleManager = app(CodespacesLifecycleManager::class);
        $this->healthMonitor = app(CodespacesHealthMonitor::class);
    }

    public function test_can_initialize_environment()
    {
        $this->artisan('codespaces:env init')
            ->expectsOutput('Initializing Codespaces environment...')
            ->expectsOutput('Environment initialized successfully!')
            ->assertExitCode(0);

        $this->assertDirectoryExists(base_path('.codespaces/config/services'));
        $this->assertDirectoryExists(base_path('.codespaces/config/local'));
        $this->assertDirectoryExists(storage_path('logs/codespaces'));
    }

    public function test_can_toggle_environment()
    {
        // Start in local mode
        $this->configManager->setMode('local');
        $this->assertEquals('local', $this->configManager->getMode());

        // Toggle to Codespaces mode
        $this->artisan('codespaces:env toggle')
            ->expectsOutput('Switching to codespaces environment...')
            ->expectsOutput('Environment switched to codespaces mode')
            ->assertExitCode(0);

        $this->assertEquals('codespaces', $this->configManager->getMode());

        // Toggle back to local mode
        $this->artisan('codespaces:env toggle')
            ->expectsOutput('Switching to local environment...')
            ->expectsOutput('Environment switched to local mode')
            ->assertExitCode(0);

        $this->assertEquals('local', $this->configManager->getMode());
    }

    public function test_can_create_and_teardown_service()
    {
        $serviceName = 'database';
        $config = [
            'name' => $serviceName,
            'type' => 'mysql',
            'env' => [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'test_db',
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => 'root'
            ]
        ];

        // Create service
        $this->assertTrue($this->lifecycleManager->createService($serviceName, $config));
        $this->assertNotNull($this->configManager->getServiceConfig($serviceName));

        // Teardown service
        $this->assertTrue($this->lifecycleManager->teardownService($serviceName));
    }

    public function test_can_check_service_health()
    {
        $serviceName = 'database';
        $config = [
            'name' => $serviceName,
            'type' => 'mysql',
            'env' => [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'test_db',
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => 'root'
            ]
        ];

        // Create service
        $this->lifecycleManager->createService($serviceName, $config);

        // Check health
        $health = $this->healthMonitor->checkServiceHealth($serviceName);
        $this->assertIsArray($health);
        $this->assertArrayHasKey('healthy', $health);
        $this->assertArrayHasKey('timestamp', $health);
        $this->assertArrayHasKey('service', $health);

        // Cleanup
        $this->lifecycleManager->teardownService($serviceName);
    }

    public function test_can_rebuild_unhealthy_service()
    {
        $serviceName = 'database';
        $config = [
            'name' => $serviceName,
            'type' => 'mysql',
            'env' => [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'test_db',
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => 'root'
            ]
        ];

        // Create service
        $this->lifecycleManager->createService($serviceName, $config);

        // Simulate unhealthy service by modifying config
        $badConfig = $config;
        $badConfig['env']['DB_PASSWORD'] = 'wrong_password';
        $this->configManager->saveServiceConfig($serviceName, $badConfig);

        // Rebuild service
        $this->artisan('codespaces:env rebuild database')
            ->expectsOutput('Rebuilding service: database')
            ->assertExitCode(0);

        // Verify service is healthy
        $health = $this->healthMonitor->checkServiceHealth($serviceName);
        $this->assertTrue($health['healthy']);

        // Cleanup
        $this->lifecycleManager->teardownService($serviceName);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists(base_path('.codespaces/config'))) {
            File::deleteDirectory(base_path('.codespaces/config'));
        }
        if (File::exists(storage_path('logs/codespaces'))) {
            File::deleteDirectory(storage_path('logs/codespaces'));
        }

        parent::tearDown();
    }
} 