<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;
use App\Providers\DockerServiceProvider;
use App\Services\DockerManager;
use App\Services\NetworkManager;
use App\Services\VolumeManager;
use App\Services\CodespaceConfigurationManager;
use App\Services\CodespaceInfrastructureManager;
use Illuminate\Support\Facades\Config;
use Mockery;

class DockerServiceProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new DockerServiceProvider($this->app);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_registers_docker_manager()
    {
        $this->provider->register();

        $this->assertInstanceOf(
            DockerManager::class,
            $this->app->make(DockerManager::class)
        );
    }

    public function test_it_registers_network_manager()
    {
        $this->provider->register();

        $this->assertInstanceOf(
            NetworkManager::class,
            $this->app->make(NetworkManager::class)
        );
    }

    public function test_it_registers_volume_manager()
    {
        $this->provider->register();

        $this->assertInstanceOf(
            VolumeManager::class,
            $this->app->make(VolumeManager::class)
        );
    }

    public function test_it_registers_codespace_configuration_manager()
    {
        $this->provider->register();

        $this->assertInstanceOf(
            CodespaceConfigurationManager::class,
            $this->app->make(CodespaceConfigurationManager::class)
        );
    }

    public function test_it_registers_codespace_infrastructure_manager()
    {
        $this->provider->register();

        $this->assertInstanceOf(
            CodespaceInfrastructureManager::class,
            $this->app->make(CodespaceInfrastructureManager::class)
        );
    }

    public function test_it_merges_docker_config()
    {
        Config::set('docker', [
            'networks' => ['test-network'],
            'volumes' => ['test-volume']
        ]);

        $this->provider->register();

        $this->assertContains('test-network', Config::get('docker.networks'));
        $this->assertContains('test-volume', Config::get('docker.volumes'));
    }

    public function test_it_merges_codespaces_config()
    {
        Config::set('codespaces', [
            'enabled' => true,
            'services' => ['test-service']
        ]);

        $this->provider->register();

        $this->assertTrue(Config::get('codespaces.enabled'));
        $this->assertContains('test-service', Config::get('codespaces.services'));
    }

    public function test_it_publishes_configs()
    {
        $this->provider->boot();

        $this->assertFileExists(config_path('docker.php'));
        $this->assertFileExists(config_path('codespaces.php'));
    }

    public function test_it_handles_missing_config_files()
    {
        if (file_exists(config_path('docker.php'))) {
            unlink(config_path('docker.php'));
        }
        if (file_exists(config_path('codespaces.php'))) {
            unlink(config_path('codespaces.php'));
        }

        $this->provider->boot();

        $this->assertFileExists(config_path('docker.php'));
        $this->assertFileExists(config_path('codespaces.php'));
    }

    public function test_it_handles_invalid_docker_config()
    {
        // Copy invalid stub to config location
        copy(base_path('tests/stubs/invalid-docker.php'), config_path('docker.php'));
        
        $this->provider->register();

        $this->assertIsArray(Config::get('docker'));

        // Clean up
        unlink(config_path('docker.php'));
    }

    public function test_it_handles_invalid_codespaces_config()
    {
        // Copy invalid stub to config location
        copy(base_path('tests/stubs/invalid-codespaces.php'), config_path('codespaces.php'));
        
        $this->provider->register();

        $this->assertIsArray(Config::get('codespaces'));

        // Clean up
        unlink(config_path('codespaces.php'));
    }

    public function test_it_handles_missing_docker_manager()
    {
        $this->app->forgetInstance(DockerManager::class);

        $this->provider->register();

        $this->assertInstanceOf(
            DockerManager::class,
            $this->app->make(DockerManager::class)
        );
    }

    public function test_it_handles_missing_network_manager()
    {
        $this->app->forgetInstance(NetworkManager::class);

        $this->provider->register();

        $this->assertInstanceOf(
            NetworkManager::class,
            $this->app->make(NetworkManager::class)
        );
    }

    public function test_it_handles_missing_volume_manager()
    {
        $this->app->forgetInstance(VolumeManager::class);

        $this->provider->register();

        $this->assertInstanceOf(
            VolumeManager::class,
            $this->app->make(VolumeManager::class)
        );
    }

    public function test_it_handles_missing_codespace_configuration_manager()
    {
        $this->app->forgetInstance(CodespaceConfigurationManager::class);

        $this->provider->register();

        $this->assertInstanceOf(
            CodespaceConfigurationManager::class,
            $this->app->make(CodespaceConfigurationManager::class)
        );
    }

    public function test_it_handles_missing_codespace_infrastructure_manager()
    {
        $this->app->forgetInstance(CodespaceInfrastructureManager::class);

        $this->provider->register();

        $this->assertInstanceOf(
            CodespaceInfrastructureManager::class,
            $this->app->make(CodespaceInfrastructureManager::class)
        );
    }

    public function test_it_handles_permission_issues()
    {
        if (file_exists(config_path('docker.php'))) {
            chmod(config_path('docker.php'), 0444);
        }
        if (file_exists(config_path('codespaces.php'))) {
            chmod(config_path('codespaces.php'), 0444);
        }

        $this->provider->boot();

        if (file_exists(config_path('docker.php'))) {
            chmod(config_path('docker.php'), 0644);
        }
        if (file_exists(config_path('codespaces.php'))) {
            chmod(config_path('codespaces.php'), 0644);
        }

        $this->assertTrue(true); // If we get here, no exception was thrown
    }
} 