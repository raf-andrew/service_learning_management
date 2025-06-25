<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class SniffingCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $sniffingDir = base_path('.sniffing');
        if (!File::exists($sniffingDir)) {
            File::makeDirectory($sniffingDir, 0755, true);
        }
    }

    public function test_sniffing_rules_command_list()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'list'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_add()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'add',
            '--name' => 'test-rule',
            '--pattern' => 'test-pattern'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_remove()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'remove',
            '--name' => 'test-rule'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_update()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'update',
            '--name' => 'test-rule',
            '--pattern' => 'updated-pattern'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_validate()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'validate'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_export()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'export',
            '--format' => 'json'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_import()
    {
        $this->artisan('sniffing:rules', [
            'action' => 'import',
            '--file' => 'rules.json'
        ])
            ->expectsOutput('Processing sniffing rules action...')
            ->assertExitCode(0);
    }

    public function test_sniffing_rules_command_create_rules_directory()
    {
        $this->artisan('sniffing:rules', ['action' => 'list']);
        
        $rulesDir = base_path('.sniffing/rules');
        $this->assertTrue(File::exists($rulesDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $sniffingDir = base_path('.sniffing');
        if (File::exists($sniffingDir)) {
            File::deleteDirectory($sniffingDir);
        }
        
        parent::tearDown();
    }
} 