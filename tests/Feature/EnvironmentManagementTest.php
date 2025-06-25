<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EnvironmentVariable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class EnvironmentManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test .env.example
        File::put(base_path('.env.example'), "APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=base64:test\n");
        
        // Remove test .env if exists
        if (File::exists(base_path('.env'))) {
            File::delete(base_path('.env'));
        }
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

    public function test_env_sync_creates_env_from_example()
    {
        $this->assertFalse(File::exists(base_path('.env')));
        
        Artisan::call('env:sync');
        
        $this->assertTrue(File::exists(base_path('.env')));
        $this->assertEquals(
            File::get(base_path('.env.example')),
            File::get(base_path('.env'))
        );
    }

    public function test_env_sync_stores_variables_in_database()
    {
        Artisan::call('env:sync');
        
        $this->assertDatabaseHas('environment_variables', [
            'key' => 'APP_NAME',
            'value' => 'Laravel',
            'group' => 'app'
        ]);
        
        $this->assertDatabaseHas('environment_variables', [
            'key' => 'APP_ENV',
            'value' => 'local',
            'group' => 'app'
        ]);
    }

    public function test_env_restore_creates_env_from_database()
    {
        // First sync to create database entries
        Artisan::call('env:sync');
        
        // Delete .env file
        File::delete(base_path('.env'));
        
        // Restore from database
        Artisan::call('env:restore');
        
        $this->assertTrue(File::exists(base_path('.env')));
        $envContents = File::get(base_path('.env'));
        
        $this->assertStringContainsString('APP_NAME=Laravel', $envContents);
        $this->assertStringContainsString('APP_ENV=local', $envContents);
    }

    public function test_sensitive_variables_are_encrypted()
    {
        // Create a test variable
        EnvironmentVariable::create([
            'key' => 'DB_PASSWORD',
            'value' => 'secret',
            'group' => 'database',
            'is_encrypted' => true
        ]);
        
        $variable = EnvironmentVariable::where('key', 'DB_PASSWORD')->first();
        
        $this->assertNotEquals('secret', $variable->getRawOriginal('value'));
        $this->assertEquals('secret', $variable->value);
    }
} 