<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CodespacesConfigManager;
use Illuminate\Support\Facades\File;
use Mockery;

class CodespacesConfigManagerTest extends TestCase
{
    protected $configManager;
    protected $configPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->configPath = base_path('.codespaces/config');
        $this->configManager = new CodespacesConfigManager();
        
        // Create test directories
        if (!File::exists($this->configPath)) {
            File::makeDirectory($this->configPath, 0755, true);
        }
        if (!File::exists($this->configPath . '/services')) {
            File::makeDirectory($this->configPath . '/services', 0755, true);
        }
        if (!File::exists($this->configPath . '/local')) {
            File::makeDirectory($this->configPath . '/local', 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->configPath)) {
            File::deleteDirectory($this->configPath);
        }
        parent::tearDown();
    }

    public function test_it_creates_config_directories_if_not_exist()
    {
        $this->assertDirectoryExists($this->configPath);
        $this->assertDirectoryExists($this->configPath . '/services');
        $this->assertDirectoryExists($this->configPath . '/local');
    }

    public function test_it_loads_codespaces_configurations()
    {
        // Create test service config
        $serviceConfig = [
            'name' => 'test-service',
            'type' => 'web',
            'port' => 8080
        ];
        File::put(
            $this->configPath . '/services/test-service.json',
            json_encode($serviceConfig)
        );

        $config = $this->configManager->getServiceConfig('test-service');
        
        $this->assertEquals($serviceConfig, $config);
    }

    public function test_it_loads_local_configurations()
    {
        // Create test local config
        $localConfig = [
            'name' => 'test-service',
            'type' => 'web',
            'port' => 3000
        ];
        File::put(
            $this->configPath . '/local/test-service.json',
            json_encode($localConfig)
        );

        $this->configManager->setMode('local');
        $config = $this->configManager->getServiceConfig('test-service');
        
        $this->assertEquals($localConfig, $config);
    }

    public function test_it_returns_null_for_non_existent_service()
    {
        $config = $this->configManager->getServiceConfig('non-existent-service');
        $this->assertNull($config);
    }

    public function test_it_throws_exception_for_invalid_mode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mode. Must be either "local" or "codespaces"');
        
        $this->configManager->setMode('invalid-mode');
    }

    public function test_it_switches_between_modes()
    {
        // Create test configs
        $codespacesConfig = [
            'name' => 'test-service',
            'type' => 'web',
            'port' => 8080
        ];
        $localConfig = [
            'name' => 'test-service',
            'type' => 'web',
            'port' => 3000
        ];

        File::put(
            $this->configPath . '/services/test-service.json',
            json_encode($codespacesConfig)
        );
        File::put(
            $this->configPath . '/local/test-service.json',
            json_encode($localConfig)
        );

        // Test Codespaces mode
        $this->configManager->setMode('codespaces');
        $this->assertEquals('codespaces', $this->configManager->getMode());
        $this->assertEquals($codespacesConfig, $this->configManager->getServiceConfig('test-service'));

        // Test Local mode
        $this->configManager->setMode('local');
        $this->assertEquals('local', $this->configManager->getMode());
        $this->assertEquals($localConfig, $this->configManager->getServiceConfig('test-service'));
    }

    public function test_it_handles_invalid_json_config()
    {
        // Create invalid JSON file
        File::put(
            $this->configPath . '/services/test-service.json',
            'invalid-json'
        );

        $config = $this->configManager->getServiceConfig('test-service');
        $this->assertNull($config);
    }

    public function test_it_handles_empty_config_file()
    {
        // Create empty config file
        File::put(
            $this->configPath . '/services/test-service.json',
            ''
        );

        $config = $this->configManager->getServiceConfig('test-service');
        $this->assertNull($config);
    }

    public function test_it_handles_missing_config_directories()
    {
        // Remove config directories
        File::deleteDirectory($this->configPath . '/services');
        File::deleteDirectory($this->configPath . '/local');

        // Create new instance to trigger directory creation
        $configManager = new CodespacesConfigManager();

        $this->assertDirectoryExists($this->configPath . '/services');
        $this->assertDirectoryExists($this->configPath . '/local');
    }

    public function test_it_handles_permission_issues()
    {
        // Make config directory read-only
        chmod($this->configPath, 0444);

        // Create new instance to trigger directory creation
        $configManager = new CodespacesConfigManager();

        // Restore permissions
        chmod($this->configPath, 0755);

        $this->assertDirectoryExists($this->configPath);
    }
} 