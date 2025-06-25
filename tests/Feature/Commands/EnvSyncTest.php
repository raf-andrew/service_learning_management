<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class EnvSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test .env.example file
        $envExampleContent = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=base64:test\nAPP_DEBUG=true\nAPP_URL=http://localhost\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=laravel\nDB_USERNAME=root\nDB_PASSWORD=\n";
        File::put(base_path('.env.example'), $envExampleContent);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists(base_path('.env'))) {
            File::delete(base_path('.env'));
        }
        if (File::exists(base_path('.env.example'))) {
            File::delete(base_path('.env.example'));
        }
        
        parent::tearDown();
    }

    public function test_it_creates_env_from_example_when_not_exists()
    {
        $this->artisan('env:sync')
            ->expectsOutput('Created .env from .env.example')
            ->expectsOutput('Environment variables synced successfully!')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(base_path('.env')));
        $this->assertEquals(File::get(base_path('.env.example')), File::get(base_path('.env')));
    }

    public function test_it_handles_missing_env_example_file()
    {
        // Remove .env.example
        File::delete(base_path('.env.example'));

        $this->artisan('env:sync')
            ->expectsOutput('.env.example file not found!')
            ->assertExitCode(1);
    }

    public function test_it_uses_existing_env_when_present()
    {
        // Create a test .env file
        $envContent = "APP_NAME=ExistingApp\nAPP_ENV=production\n";
        File::put(base_path('.env'), $envContent);

        $this->artisan('env:sync')
            ->expectsOutput('Environment variables synced successfully!')
            ->assertExitCode(0);

        // Should not show "Created .env from .env.example" message
        $this->assertTrue(File::exists(base_path('.env')));
        $this->assertEquals($envContent, File::get(base_path('.env')));
    }

    public function test_it_parses_env_file_correctly()
    {
        // Create a test .env file with various formats
        $envContent = "APP_NAME=TestApp\n# This is a comment\n\nDB_CONNECTION=sqlite\nEMPTY_VALUE=\n";
        File::put(base_path('.env'), $envContent);

        $this->artisan('env:sync')
            ->expectsOutput('Environment variables synced successfully!')
            ->assertExitCode(0);

        // The command should complete without errors
        $this->assertTrue(File::exists(base_path('.env')));
    }

    public function test_it_handles_force_option()
    {
        // Create a test .env file
        $envContent = "APP_NAME=ExistingApp\n";
        File::put(base_path('.env'), $envContent);

        $this->artisan('env:sync', ['--force' => true])
            ->expectsOutput('Environment variables synced successfully!')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(base_path('.env')));
    }
} 