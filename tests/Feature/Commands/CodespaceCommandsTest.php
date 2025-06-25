<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class CodespaceCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $codespacesDir = base_path('.codespaces');
        if (!File::exists($codespacesDir)) {
            File::makeDirectory($codespacesDir, 0755, true);
        }
    }

    public function test_codespace_command_basic()
    {
        $this->artisan('codespace')
            ->expectsOutput('Codespace management started...')
            ->assertExitCode(0);
    }

    public function test_codespace_command_with_action()
    {
        $this->artisan('codespace', [
            'action' => 'create'
        ])
            ->expectsOutput('Codespace management started...')
            ->assertExitCode(0);
    }

    public function test_codespace_command_with_list_action()
    {
        $this->artisan('codespace', [
            'action' => 'list'
        ])
            ->expectsOutput('Codespace management started...')
            ->assertExitCode(0);
    }

    public function test_codespace_command_with_delete_action()
    {
        $this->artisan('codespace', [
            'action' => 'delete',
            '--name' => 'test-codespace'
        ])
            ->expectsOutput('Codespace management started...')
            ->assertExitCode(0);
    }

    public function test_codespace_command_with_environment_option()
    {
        $this->artisan('codespace', [
            'action' => 'create',
            '--environment' => 'development'
        ])
            ->expectsOutput('Codespace management started...')
            ->assertExitCode(0);
    }

    public function test_codespace_command_with_size_option()
    {
        $this->artisan('codespace', [
            'action' => 'create',
            '--size' => 'small'
        ])
            ->expectsOutput('Codespace management started...')
            ->assertExitCode(0);
    }

    public function test_codespace_command_creates_config()
    {
        $this->artisan('codespace', ['action' => 'create']);
        
        $configDir = base_path('.codespaces/config');
        $this->assertTrue(File::exists($configDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $codespacesDir = base_path('.codespaces');
        if (File::exists($codespacesDir)) {
            File::deleteDirectory($codespacesDir);
        }
        
        parent::tearDown();
    }
} 