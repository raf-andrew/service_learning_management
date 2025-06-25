<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\GitHub\Config;
use App\Models\GitHub\Feature;
use App\Models\GitHub\Repository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class GitHubIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        putenv('GITHUB_TOKEN=test_token');
        putenv('GITHUB_REPOSITORY=test/repo');
    }

    protected function tearDown(): void
    {
        // Clean up
        Config::truncate();
        Feature::truncate();
        Repository::truncate();
        
        parent::tearDown();
    }

    public function test_github_config_sync()
    {
        Artisan::call('github:sync-config');
        
        $this->assertDatabaseHas('github_configs', [
            'key' => 'GITHUB_TOKEN',
            'group' => 'github',
            'is_encrypted' => true
        ]);
        
        $this->assertDatabaseHas('github_configs', [
            'key' => 'GITHUB_REPOSITORY',
            'value' => 'test/repo',
            'group' => 'github'
        ]);
    }

    public function test_feature_management()
    {
        // Add feature
        Artisan::call('github:feature', [
            'action' => 'add',
            'name' => 'test_feature',
            '--description' => 'Test feature',
            '--conditions' => ['type' => 'environment', 'value' => 'testing']
        ]);
        
        $this->assertDatabaseHas('github_features', [
            'name' => 'test_feature',
            'enabled' => false
        ]);
        
        // Enable feature
        Artisan::call('github:feature', [
            'action' => 'enable',
            'name' => 'test_feature'
        ]);
        
        $this->assertDatabaseHas('github_features', [
            'name' => 'test_feature',
            'enabled' => true
        ]);
        
        // List features
        $output = Artisan::call('github:feature', ['action' => 'list']);
        $this->assertStringContainsString('test_feature', $output);
        
        // Remove feature
        Artisan::call('github:feature', [
            'action' => 'remove',
            'name' => 'test_feature'
        ]);
        
        $this->assertDatabaseMissing('github_features', [
            'name' => 'test_feature'
        ]);
    }

    public function test_repository_sync()
    {
        // First sync config
        Artisan::call('github:sync-config');
        
        $repository = Repository::where('full_name', 'test/repo')->first();
        $this->assertNotNull($repository);
        
        // Test repository settings
        $this->assertIsArray($repository->settings);
        $this->assertIsArray($repository->permissions);
    }

    public function test_sensitive_data_encryption()
    {
        Artisan::call('github:sync-config');
        
        $config = Config::where('key', 'GITHUB_TOKEN')->first();
        $this->assertNotEquals('test_token', $config->getRawOriginal('value'));
        $this->assertEquals('test_token', $config->value);
    }
} 