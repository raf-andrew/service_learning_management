<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ConfigCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $configDir = base_path('.config');
        if (!File::exists($configDir)) {
            File::makeDirectory($configDir, 0755, true);
        }
    }

    public function test_config_commands_list()
    {
        $this->artisan('config:commands', [
            'action' => 'list'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_show()
    {
        $this->artisan('config:commands', [
            'action' => 'show'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_add()
    {
        $this->artisan('config:commands', [
            'action' => 'add'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_remove()
    {
        $this->artisan('config:commands', [
            'action' => 'remove'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_sync()
    {
        $this->artisan('config:commands', [
            'action' => 'sync'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_show_config()
    {
        $this->artisan('config:commands', [
            'action' => 'show-config'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_validate()
    {
        $this->artisan('config:commands', [
            'action' => 'validate'
        ])
            ->expectsOutput('Processing config commands action...')
            ->assertExitCode(0);
    }

    public function test_config_commands_create_config_files()
    {
        $this->artisan('config:commands', ['action' => 'list']);
        
        $configDir = base_path('.config/commands');
        $this->assertTrue(File::exists($configDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $configDir = base_path('.config');
        if (File::exists($configDir)) {
            File::deleteDirectory($configDir);
        }
        
        parent::tearDown();
    }
}