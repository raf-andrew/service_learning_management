<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InfrastructureCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $infrastructureDir = base_path('.infrastructure');
        if (!File::exists($infrastructureDir)) {
            File::makeDirectory($infrastructureDir, 0755, true);
        }
    }

    public function test_infrastructure_manager_command_basic()
    {
        $this->artisan('infrastructure:manage')
            ->expectsOutput('Infrastructure management started...')
            ->assertExitCode(0);
    }

    public function test_infrastructure_manager_command_with_action()
    {
        $this->artisan('infrastructure:manage', [
            'action' => 'status'
        ])
            ->expectsOutput('Infrastructure management started...')
            ->assertExitCode(0);
    }

    public function test_infrastructure_manager_command_with_deploy_action()
    {
        $this->artisan('infrastructure:manage', [
            'action' => 'deploy'
        ])
            ->expectsOutput('Infrastructure management started...')
            ->assertExitCode(0);
    }

    public function test_infrastructure_manager_command_with_rollback_action()
    {
        $this->artisan('infrastructure:manage', [
            'action' => 'rollback'
        ])
            ->expectsOutput('Infrastructure management started...')
            ->assertExitCode(0);
    }

    public function test_infrastructure_manager_command_with_environment_option()
    {
        $this->artisan('infrastructure:manage', [
            'action' => 'status',
            '--environment' => 'staging'
        ])
            ->expectsOutput('Infrastructure management started...')
            ->assertExitCode(0);
    }

    public function test_infrastructure_manager_command_with_verbose_option()
    {
        $this->artisan('infrastructure:manage', [
            'action' => 'status',
            '--verbose' => true
        ])
            ->expectsOutput('Infrastructure management started...')
            ->assertExitCode(0);
    }

    public function test_infrastructure_manager_command_creates_logs()
    {
        $this->artisan('infrastructure:manage', ['action' => 'status']);
        
        $logsDir = base_path('.infrastructure/logs');
        $this->assertTrue(File::exists($logsDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $infrastructureDir = base_path('.infrastructure');
        if (File::exists($infrastructureDir)) {
            File::deleteDirectory($infrastructureDir);
        }
        
        parent::tearDown();
    }
} 