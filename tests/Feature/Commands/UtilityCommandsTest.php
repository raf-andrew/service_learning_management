<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class UtilityCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $tempDir = base_path('.temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
    }

    public function test_reorganize_commands_command()
    {
        $this->artisan('commands:reorganize')
            ->expectsOutput('Reorganizing commands...')
            ->assertExitCode(0);
    }

    public function test_reorganize_commands_command_with_dry_run()
    {
        $this->artisan('commands:reorganize', [
            '--dry-run' => true
        ])
            ->expectsOutput('Reorganizing commands...')
            ->assertExitCode(0);
    }

    public function test_reorganize_commands_command_with_verbose()
    {
        $this->artisan('commands:reorganize', [
            '--verbose' => true
        ])
            ->expectsOutput('Reorganizing commands...')
            ->assertExitCode(0);
    }

    public function test_convert_shell_scripts_command()
    {
        $this->artisan('scripts:convert')
            ->expectsOutput('Converting shell scripts...')
            ->assertExitCode(0);
    }

    public function test_convert_shell_scripts_command_with_directory()
    {
        $this->artisan('scripts:convert', [
            '--directory' => base_path('.temp')
        ])
            ->expectsOutput('Converting shell scripts...')
            ->assertExitCode(0);
    }

    public function test_convert_shell_scripts_command_with_output()
    {
        $this->artisan('scripts:convert', [
            '--output' => base_path('.temp/converted')
        ])
            ->expectsOutput('Converting shell scripts...')
            ->assertExitCode(0);
    }

    public function test_update_command_namespaces_command()
    {
        $this->artisan('commands:update-namespaces')
            ->expectsOutput('Updating command namespaces...')
            ->assertExitCode(0);
    }

    public function test_update_command_namespaces_command_with_dry_run()
    {
        $this->artisan('commands:update-namespaces', [
            '--dry-run' => true
        ])
            ->expectsOutput('Updating command namespaces...')
            ->assertExitCode(0);
    }

    public function test_update_command_namespaces_command_with_pattern()
    {
        $this->artisan('commands:update-namespaces', [
            '--pattern' => 'App\\Console\\Commands\\*'
        ])
            ->expectsOutput('Updating command namespaces...')
            ->assertExitCode(0);
    }

    public function test_utility_commands_create_backup()
    {
        $this->artisan('commands:reorganize');
        
        $backupDir = base_path('.temp/backup');
        $this->assertTrue(File::exists($backupDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $tempDir = base_path('.temp');
        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }
        
        parent::tearDown();
    }
} 