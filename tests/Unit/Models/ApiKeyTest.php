<?php

namespace Tests\Unit\Models;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_key_returns_32_character_string()
    {
        $key = ApiKey::generateKey();
        $this->assertEquals(32, strlen($key));
    }

    public function test_has_permission_returns_true_for_valid_permission()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'permissions' => ['read', 'write']
        ]);

        $this->assertTrue($apiKey->hasPermission('read'));
        $this->assertTrue($apiKey->hasPermission('write'));
        $this->assertFalse($apiKey->hasPermission('delete'));
    }

    public function test_is_expired_returns_true_for_expired_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'expires_at' => now()->subDay()
        ]);

        $this->assertTrue($apiKey->isExpired());
    }

    public function test_is_expired_returns_false_for_non_expired_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'expires_at' => now()->addDay()
        ]);

        $this->assertFalse($apiKey->isExpired());
    }

    public function test_is_valid_returns_true_for_valid_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'expires_at' => now()->addDay()
        ]);

        $this->assertTrue($apiKey->isValid());
    }

    public function test_is_valid_returns_false_for_inactive_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => false,
            'expires_at' => now()->addDay()
        ]);

        $this->assertFalse($apiKey->isValid());
    }

    public function test_is_valid_returns_false_for_expired_key()
    {
        $apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'expires_at' => now()->subDay()
        ]);

        $this->assertFalse($apiKey->isValid());
    }

    public function test_active_scope_returns_only_valid_keys()
    {
        ApiKey::create([
            'name' => 'Active Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'expires_at' => now()->addDay()
        ]);

        ApiKey::create([
            'name' => 'Inactive Key',
            'key' => ApiKey::generateKey(),
            'is_active' => false,
            'expires_at' => now()->addDay()
        ]);

        ApiKey::create([
            'name' => 'Expired Key',
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'expires_at' => now()->subDay()
        ]);

        $this->assertEquals(1, ApiKey::active()->count());
    }
} 